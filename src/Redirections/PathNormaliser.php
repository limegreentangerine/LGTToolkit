<?php

namespace LgtToolkit\Redirections;

/**
 * Normalises user-entered URLs into the form stored and matched by redirections.
 */
class PathNormaliser
{
    /**
     * Paths that must never be redirected away from.
     */
    public const RESERVED_PREFIXES = [
        '/ccm',
        '/concrete',
        '/dashboard',
        '/login',
        '/logout',
        '/index.php',
        '/application',
        '/packages',
        '/updates',
        '/debugbar',
        '/robots.txt',
        '/sitemap.xml',
    ];

    /**
     * Returns a lowercase, leading-slash, no-trailing-slash path with the query string and
     * fragment removed, or null when the input is empty, unusable, or points at another host.
     */
    public static function normalise(string $input, ?string $siteHost = null): ?string
    {
        $input = trim($input);

        if ($input === '') {
            return null;
        }

        if (preg_match('#^([a-z][a-z0-9+.\-]*:)?//#i', $input)) {
            $parts = parse_url(str_starts_with($input, '//') ? 'http:' . $input : $input);

            if ($parts === false || empty($parts['host'])) {
                return null;
            }

            if ($siteHost !== null && strcasecmp($parts['host'], $siteHost) !== 0) {
                return null;
            }

            $path = $parts['path'] ?? '';
        } else {
            $path = $input;
        }

        $path = preg_split('/[?#]/', $path, 2)[0];
        $path = rawurldecode($path);

        if (preg_match('/[\x00-\x1F\x7F\s]/u', $path) || !preg_match('//u', $path)) {
            return null;
        }

        $path = '/' . trim(preg_replace('#/+#', '/', $path), '/');

        if ($path === '/') {
            return null;
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '.' || $segment === '..') {
                return null;
            }
        }

        return mb_strtolower($path);
    }

    /**
     * Whether a normalised path is, or sits beneath, a core/reserved route.
     */
    public static function isReserved(string $path): bool
    {
        foreach (self::RESERVED_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }
}
