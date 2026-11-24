<?php

namespace LgtToolkit\Support;

/**
 * Local ticket references such as Q-20261124-K7QD, shown before the portal has assigned a number.
 */
final class LocalReference
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generate(\DateTimeInterface $now): string
    {
        $bits = unpack('N', "\0" . random_bytes(3))[1];
        $suffix = '';
        for ($i = 0; $i < 4; $i++) {
            $suffix .= self::ALPHABET[($bits >> ($i * 5)) & 31];
        }

        return 'Q-' . $now->format('Ymd') . '-' . $suffix;
    }
}
