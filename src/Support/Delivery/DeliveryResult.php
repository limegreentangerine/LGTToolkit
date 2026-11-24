<?php

namespace LgtToolkit\Support\Delivery;

/**
 * The outcome of one send. Never holds the API key or request headers.
 */
final readonly class DeliveryResult
{
    /**
     * @param list<string> $droppedAttachments "name (size)" of files removed after a 413
     */
    public function __construct(
        public DeliveryAction $action,
        public int $statusCode,
        public ?int $ticket = null,
        public ?string $reference = null,
        public ?string $error = null,
        public ?int $retryAfter = null,
        public array $droppedAttachments = [],
    ) {}

    public function isDelivered(): bool
    {
        return $this->action === DeliveryAction::Delivered;
    }

    /**
     * Should the ticket go into (or stay in) the queue for another try?
     */
    public function shouldRetry(): bool
    {
        return in_array($this->action, [DeliveryAction::ConfigError, DeliveryAction::RateLimited, DeliveryAction::Retryable], true);
    }

    /**
     * The request may have reached the portal even though we saw no 201.
     */
    public function possibleDuplicate(): bool
    {
        return $this->action === DeliveryAction::Retryable;
    }
}
