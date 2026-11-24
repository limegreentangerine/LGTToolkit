<?php

namespace LgtToolkit\Support;

/**
 * Strips likely secrets and personal data from log lines and transport errors.
 */
final class Redactor
{
    public static function redact(string $text, int $max = 500): string
    {
        $text = mb_scrub($text, 'UTF-8');

        // Bearer first, so "Authorization: Bearer x" can't leave the token behind.
        $text = preg_replace('/Bearer\s+\S+/i', 'Bearer [redacted]', $text) ?? '';
        $text = preg_replace('/(password|passwd|pwd|secret|token|api[_-]?key|authorization)\s*[:=]\s*\S+/i', '$1=[redacted]', $text) ?? '';
        $text = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[email]', $text) ?? '';
        $text = preg_replace('/(?<!\d)\d(?:[ -]?\d){12,18}(?!\d)/', '[number]', $text) ?? '';

        return mb_substr($text, 0, $max);
    }
}
