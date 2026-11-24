<?php

namespace LgtToolkit\Tests\Unit;

use Core;
use GuzzleHttp\Middleware;
use Psr\Log\AbstractLogger;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use LgtToolkit\Support\SupportEnv;
use Concrete\Core\Production\Modes;
use GuzzleHttp\Handler\MockHandler;
use LgtToolkit\Support\SupportSettings;
use GuzzleHttp\Exception\ConnectException;
use LgtToolkit\Support\Delivery\TicketSender;
use LgtToolkit\Support\Delivery\DeliveryAction;
use LgtToolkit\Support\Delivery\DeliveryResult;

final class TicketSenderTest extends TestCase
{
    private const KEY = 'sk_live_SECRET_12345';

    /** @var list<array{request: Request}> */
    private array $history = [];

    private object $logger;

    private function send(array $responses, array $payload = []): DeliveryResult
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        $sender = new TicketSender(new SupportSettings(Modes::MODE_PRODUCTION), $this->logger, $stack);
        $result = $sender->send($payload + ['email' => 'a@example.com', 'subject' => 'S', 'message' => 'M'], 15);

        // The key must never escape into results or logs.
        $this->assertStringNotContainsString(self::KEY, serialize($result));
        $this->assertStringNotContainsString(self::KEY, implode("\n", $this->logger->lines));

        return $result;
    }

    private function attachments(): array
    {
        return ['attachments' => [['filename' => 'big.png', 'content_base64' => base64_encode(str_repeat('x', 3072))]]];
    }

    public function testCreatedWithReference(): void
    {
        $result = $this->send([new Response(201, [], '{"ticket": 1234, "reference": "#1234"}')]);

        $this->assertTrue($result->isDelivered());
        $this->assertSame(1234, $result->ticket);
        $this->assertSame('#1234', $result->reference);

        $request = $this->history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(SupportEnv::DEFAULT_URL, (string) $request->getUri());
        $this->assertSame('Bearer ' . self::KEY, $request->getHeaderLine('Authorization'));
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame(['email' => 'a@example.com', 'subject' => 'S', 'message' => 'M'], json_decode((string) $request->getBody(), true));
    }

    public function testPortalUrlKeptOnlyOnPortalHost(): void
    {
        $ok = $this->send([new Response(201, [], '{"ticket": 1234, "reference": "#1234", "url": "https://portal.limegreentangerine.co.uk/ticket.php?id=1234"}')]);
        $this->assertSame('https://portal.limegreentangerine.co.uk/ticket.php?id=1234', $ok->url);

        foreach (['javascript:alert(1)', 'https://evil.example.com/ticket.php?id=1', 'http://portal.limegreentangerine.co.uk/ticket.php?id=1', 'https://user:pass@portal.limegreentangerine.co.uk/'] as $url) {
            $result = $this->send([new Response(201, [], json_encode(['ticket' => 1, 'url' => $url]))]);
            $this->assertTrue($result->isDelivered());
            $this->assertNull($result->url, $url);
        }
    }

    public function testCreatedWithUnreadableBodyStillDelivered(): void
    {
        $result = $this->send([new Response(201, [], 'OK')]);

        $this->assertTrue($result->isDelivered());
        $this->assertNull($result->reference);
        $this->assertStringContainsString('warning', $this->logger->lines[0]);
    }

    public function testBadRequestIsPermanentWithPortalErrors(): void
    {
        $result = $this->send([new Response(400, [], '{"errors": ["email is required", "subject is too long"]}')]);

        $this->assertSame(DeliveryAction::PermanentFailure, $result->action);
        $this->assertSame('email is required; subject is too long', $result->error);
        $this->assertStringStartsWith('error', $this->logger->lines[0]);
    }

    public function testUnauthorisedIsConfigError(): void
    {
        $result = $this->send([new Response(401, [], '{"error": "Invalid key ' . self::KEY . '"}')]);

        $this->assertSame(DeliveryAction::ConfigError, $result->action);
        $this->assertSame('Invalid key [redacted]', $result->error);
    }

    public function testRedirectIsNotFollowed(): void
    {
        $result = $this->send([
            new Response(302, ['Location' => 'https://elsewhere.example.com/']),
            new Response(201, [], '{"ticket": 1}'),
        ]);

        $this->assertSame(DeliveryAction::ConfigError, $result->action);
        $this->assertSame(302, $result->statusCode);
        $this->assertCount(1, $this->history);
    }

    public function testTooLargeResendsOnceWithoutAttachments(): void
    {
        $result = $this->send([new Response(413), new Response(201, [], '{"ticket": 9, "reference": "#9"}')], $this->attachments());

        $this->assertTrue($result->isDelivered());
        $this->assertSame(['big.png (3 KB)'], $result->droppedAttachments);
        $this->assertCount(2, $this->history);

        $second = json_decode((string) $this->history[1]['request']->getBody(), true);
        $this->assertArrayNotHasKey('attachments', $second);
        $this->assertStringContainsString('too large: big.png (3 KB)', $second['message']);
    }

    public function testTooLargeTwiceIsPermanent(): void
    {
        $result = $this->send([new Response(413), new Response(413)], $this->attachments());

        $this->assertSame(DeliveryAction::PermanentFailure, $result->action);
        $this->assertCount(2, $this->history);
    }

    public function testRateLimitedReadsRetryAfter(): void
    {
        $result = $this->send([new Response(429, ['Retry-After' => '120'])]);

        $this->assertSame(DeliveryAction::RateLimited, $result->action);
        $this->assertSame(120, $result->retryAfter);
    }

    public function testServerErrorIsRetryablePossibleDuplicate(): void
    {
        $result = $this->send([new Response(500, [], 'Internal error')]);

        $this->assertSame(DeliveryAction::Retryable, $result->action);
        $this->assertTrue($result->possibleDuplicate());
    }

    public function testConnectionFailureAndTimeoutAreRetryable(): void
    {
        $request = new Request('POST', SupportEnv::DEFAULT_URL, ['Authorization' => 'Bearer ' . self::KEY]);

        $refused = $this->send([new ConnectException('Connection refused Authorization: Bearer ' . self::KEY, $request)]);
        $this->assertSame(DeliveryAction::Retryable, $refused->action);
        $this->assertSame(500, $refused->statusCode);

        $timeout = $this->send([new ConnectException('Operation timed out', $request, null, ['errno' => 28])]);
        $this->assertSame(DeliveryAction::Retryable, $timeout->action);
        $this->assertSame(504, $timeout->statusCode);
    }

    public function testMissingKeyIsConfigErrorWithoutSending(): void
    {
        unset($_ENV[SupportEnv::API_KEY]);

        $result = $this->send([new Response(201)]);

        $this->assertSame(DeliveryAction::ConfigError, $result->action);
        $this->assertCount(0, $this->history);
    }

    public function testUnencodablePayloadSendsNothing(): void
    {
        $result = $this->send([new Response(201)], ['message' => NAN]);

        $this->assertSame(DeliveryAction::PermanentFailure, $result->action);
        $this->assertCount(0, $this->history);
    }

    protected function setUp(): void
    {
        $_ENV[SupportEnv::API_KEY] = self::KEY;
        unset($_ENV[SupportEnv::URL]);
        $this->history = [];
        $this->logger = new class extends AbstractLogger {
            public array $lines = [];

            public function log($level, $message, array $context = [])
            {
                $this->lines[] = $level . ' ' . $message . ' ' . json_encode($context);
            }
        };
    }

    protected function tearDown(): void
    {
        unset($_ENV[SupportEnv::API_KEY]);
        Core::reset();
    }
}
