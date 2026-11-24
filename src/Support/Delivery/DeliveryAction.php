<?php

namespace LgtToolkit\Support\Delivery;

enum DeliveryAction: string
{
    /**
     * Systemic outcomes that end a retry run early (sending more would fail the same way).
     */
    public function stopsBatch(): bool
    {
        return $this === self::ConfigError || $this === self::RateLimited;
    }
    /** 201: never send this item again. */
    case Delivered = 'delivered';
    /** 400 and other 4xx: the request itself is wrong; retrying won't help. */
    case PermanentFailure = 'permanent';
    /** 401, 3xx, 403, 404, 405: key or endpoint is wrong; retry hourly until fixed. */
    case ConfigError = 'config';
    /** 429: wait for Retry-After. */
    case RateLimited = 'rate_limited';
    /** 413: resend once without attachments. */
    case TooLarge = 'too_large';
    /** 5xx, timeout, connection failure: the request may have arrived, so retry flags a possible duplicate. */
    case Retryable = 'retryable';
}
