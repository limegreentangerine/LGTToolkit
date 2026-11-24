<?php

namespace LgtToolkit\Support\Delivery;

/**
 * When to try a queued ticket again (spec §9).
 */
final class BackoffPolicy
{
    /** Delay after the 1st, 2nd, 3rd and 4th failure; hourly after that. */
    public const SCHEDULE = [300, 900, 1800, 3600];
    public const CONFIG_ERROR_DELAY = 3600;
    public const RATE_LIMIT_DELAY = 900;
    public const RATE_LIMIT_CAP = 3600;
    public const EXPIRY_SECONDS = 72 * 3600;
    public const FAILED_RETENTION_SECONDS = 30 * 86400;

    /**
     * @param int $failures failed attempts so far, including the one just made
     */
    public static function nextAttempt(
        DeliveryAction $action,
        int $failures,
        \DateTimeImmutable $now,
        ?int $retryAfter = null,
    ): \DateTimeImmutable {
        $delay = match ($action) {
            DeliveryAction::ConfigError => self::CONFIG_ERROR_DELAY,
            DeliveryAction::RateLimited => min($retryAfter ?? self::RATE_LIMIT_DELAY, self::RATE_LIMIT_CAP),
            default => self::SCHEDULE[max(0, $failures - 1)] ?? 3600,
        };

        return $now->modify('+' . max(0, $delay) . ' seconds');
    }

    public static function isExpired(\DateTimeImmutable $createdAt, \DateTimeImmutable $now): bool
    {
        return $now->getTimestamp() - $createdAt->getTimestamp() > self::EXPIRY_SECONDS;
    }

    /**
     * Seconds to wait from a Retry-After header (delta-seconds or HTTP-date), or null if absent/invalid.
     */
    public static function parseRetryAfter(?string $header, \DateTimeImmutable $now): ?int
    {
        $header = trim((string) $header);
        if ($header === '') {
            return null;
        }

        if (ctype_digit($header)) {
            return (int) $header;
        }

        $date = \DateTimeImmutable::createFromFormat('D, d M Y H:i:s \G\M\T', $header, new \DateTimeZone('UTC'));
        if ($date === false) {
            return null;
        }

        return max(0, $date->getTimestamp() - $now->getTimestamp());
    }
}
