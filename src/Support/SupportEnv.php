<?php

namespace LgtToolkit\Support;

use ClassKit\Environment\Environment;

/**
 * The support ticket settings. They live in the site's .env file, not the database or file config.
 * Mirrors ZumikaSocialWall\Config\ZumikaEnv.
 */
final class SupportEnv
{
    /**
     * Secret: never log, render, store or include in messages.
     */
    public const API_KEY = 'LGT_SUPPORT_API_KEY';
    public const URL = 'LGT_SUPPORT_URL';
    public const ENABLED_NON_PRODUCTION = 'LGT_SUPPORT_ENABLED_NON_PRODUCTION';
    public const FALLBACK_EMAIL = 'LGT_SUPPORT_FALLBACK_EMAIL';

    /**
     * Used when LGT_SUPPORT_URL is unset or is not a valid address.
     */
    public const DEFAULT_URL = 'https://portal.limegreentangerine.co.uk/api/tickets.php';

    private static function get(string $name): ?string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * Whether the site runs in development mode; only then is a plain-http localhost URL accepted.
     */
    private static function isLocal(): bool
    {
        try {
            return Environment::isLocal();
        } catch (\Throwable) {
            return false;
        }
    }

    public static function apiKey(): ?string
    {
        return self::get(self::API_KEY);
    }

    /**
     * The portal endpoint, or null when unset or invalid.
     * Only https is accepted: the portal redirects http and the POST body is lost.
     */
    public static function url(): ?string
    {
        $value = self::get(self::URL);
        if ($value === null) {
            return null;
        }

        $path = '(?::\d{1,5})?(?:/[A-Za-z0-9._~/-]*)?';
        if (preg_match('#^https://[A-Za-z0-9.-]+' . $path . '$#', $value)) {
            return $value;
        }

        if (self::isLocal() && preg_match('#^http://(?:localhost|127\.0\.0\.1)' . $path . '$#', $value)) {
            return $value;
        }

        return null;
    }

    public static function urlOrDefault(): string
    {
        return self::url() ?? self::DEFAULT_URL;
    }

    public static function enabledNonProduction(): bool
    {
        return in_array(strtolower(self::get(self::ENABLED_NON_PRODUCTION) ?? ''), ['1', 'true', 'yes', 'on'], true);
    }

    public static function fallbackEmail(): ?string
    {
        $value = self::get(self::FALLBACK_EMAIL);

        return $value !== null && filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
    }

    public static function isConfigured(): bool
    {
        return self::apiKey() !== null;
    }
}
