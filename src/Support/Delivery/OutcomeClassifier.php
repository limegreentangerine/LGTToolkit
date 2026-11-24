<?php

namespace LgtToolkit\Support\Delivery;

/**
 * Maps a portal HTTP status to what we do next (spec §7).
 * ClassKit reports transport failures as 500 (connection) or 504 (timeout), which land in Retryable.
 */
final class OutcomeClassifier
{
    public static function classify(int $status): DeliveryAction
    {
        return match (true) {
            $status === 201 => DeliveryAction::Delivered,
            $status === 413 => DeliveryAction::TooLarge,
            $status === 429 => DeliveryAction::RateLimited,
            $status >= 300 && $status < 400,
            in_array($status, [401, 403, 404, 405], true) => DeliveryAction::ConfigError,
            $status >= 400 && $status < 500 => DeliveryAction::PermanentFailure,
            // 5xx, plus anything unexpected (0, other 2xx): unsure if it arrived, so retry cautiously.
            default => DeliveryAction::Retryable,
        };
    }
}
