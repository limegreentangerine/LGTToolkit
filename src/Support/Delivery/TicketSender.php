<?php

namespace LgtToolkit\Support\Delivery;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use LgtToolkit\Support\Redactor;
use ClassKit\Api\Response\Response;
use LgtToolkit\Support\SupportSettings;

/**
 * Sends one ticket and classifies the outcome. Never throws, and never puts the key in a result or log.
 */
class TicketSender
{
    private const ERROR_MAX = 2000;
    public const INTERACTIVE_TIMEOUT = 15;
    public const CRON_TIMEOUT = 30;

    /**
     * @param ?callable $handler Guzzle handler for tests
     */
    public function __construct(
        private readonly SupportSettings $settings,
        private readonly LoggerInterface $logger = new NullLogger(),
        private readonly mixed $handler = null,
    ) {}

    /**
     * @param list<string> $dropped
     */
    private function attempt(PortalConnection $connection, array $payload, string $key, array $dropped = []): DeliveryResult
    {
        $response = $connection->postTicket($payload, $key);
        $status = $response->getStatusCode();
        $action = OutcomeClassifier::classify($status);
        $body = json_decode($response->getBody(), true);

        if ($action === DeliveryAction::Delivered) {
            $ticket = is_array($body) && is_numeric($body['ticket'] ?? null) ? (int) $body['ticket'] : null;
            $reference = is_array($body) && is_string($body['reference'] ?? null) ? mb_substr($body['reference'], 0, 50) : null;
            if ($ticket === null) {
                $this->logger->warning('Support ticket delivered (201) but the portal response could not be read.');
            }

            return new DeliveryResult($action, $status, $ticket, $reference, droppedAttachments: $dropped);
        }

        return new DeliveryResult(
            $action,
            $status,
            error: $this->scrub($this->errorText($response, $body), $key),
            retryAfter: $action === DeliveryAction::RateLimited
                ? BackoffPolicy::parseRetryAfter($response->headers->get('Retry-After'), new \DateTimeImmutable())
                : null,
            droppedAttachments: $dropped,
        );
    }

    private function errorText(Response $response, mixed $body): string
    {
        $status = $response->getStatusCode();
        if ($status >= 300 && $status < 400) {
            return sprintf('HTTP %d redirect (not followed); check LGT_SUPPORT_URL.', $status);
        }

        if (is_array($body)) {
            foreach (['errors', 'error', 'message'] as $field) {
                $value = $body[$field] ?? null;
                if (is_array($value)) {
                    return implode('; ', array_map(static fn($v) => is_scalar($v) ? (string) $v : json_encode($v), $value));
                }
                if (is_string($value)) {
                    return $value;
                }
            }
        }

        return sprintf('HTTP %d: %s', $status, $response->getBody());
    }

    /**
     * Removes attachments after a 413 and notes what was dropped at the end of the message.
     *
     * @return array{0: array, 1: list<string>}
     */
    private function withoutAttachments(array $payload): array
    {
        $dropped = array_map(
            static fn(array $a) => sprintf('%s (%s KB)', $a['filename'], number_format(strlen($a['content_base64']) * 3 / 4 / 1024)),
            $payload['attachments'],
        );

        unset($payload['attachments']);
        $payload['message'] .= "\n--- Attachments not sent ---\nThe helpdesk rejected the attachments as too large: " . implode(', ', $dropped) . "\n";

        return [$payload, $dropped];
    }

    private function scrub(string $text, #[\SensitiveParameter] string $key): string
    {
        return Redactor::redact(str_replace($key, '[redacted]', $text), self::ERROR_MAX);
    }

    private function log(DeliveryResult $result): void
    {
        $context = ['status' => $result->statusCode, 'error' => $result->error];

        match ($result->action) {
            DeliveryAction::Delivered => null,
            DeliveryAction::PermanentFailure => $this->logger->error('Support ticket rejected by the helpdesk portal.', $context),
            DeliveryAction::ConfigError => $this->logger->error('Support ticket refused: check LGT_SUPPORT_API_KEY / LGT_SUPPORT_URL.', $context),
            default => $this->logger->warning('Support ticket could not be delivered; it will be retried.', $context),
        };
    }

    public function send(array $payload, int $timeout = self::INTERACTIVE_TIMEOUT): DeliveryResult
    {
        $key = $this->settings->apiKey();
        if ($key === null) {
            return new DeliveryResult(DeliveryAction::ConfigError, 0, error: 'LGT_SUPPORT_API_KEY is not set.');
        }

        try {
            $connection = new PortalConnection($this->settings->url(), $timeout, $this->handler);
            $result = $this->attempt($connection, $payload, $key);

            if ($result->action === DeliveryAction::TooLarge && !empty($payload['attachments'])) {
                [$payload, $dropped] = $this->withoutAttachments($payload);
                $result = $this->attempt($connection, $payload, $key, $dropped);
            }

            if ($result->action === DeliveryAction::TooLarge) {
                $result = new DeliveryResult(DeliveryAction::PermanentFailure, 413, error: $result->error, droppedAttachments: $result->droppedAttachments);
            }
        } catch (\JsonException $e) {
            $this->logger->error('Support ticket could not be JSON encoded; nothing was sent.', ['error' => $e->getMessage()]);

            return new DeliveryResult(DeliveryAction::PermanentFailure, 0, error: 'The ticket could not be encoded.');
        } catch (\Throwable $e) {
            $result = new DeliveryResult(DeliveryAction::Retryable, 0, error: $this->scrub($e->getMessage(), $key));
        }

        $this->log($result);

        return $result;
    }
}
