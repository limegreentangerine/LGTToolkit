<?php

namespace LgtToolkit\Providers\AutoCache;

/**
 * Builds cache-busting URLs for package assets.
 */
class AutoCacheService
{
    /**
     * Produces a versioned asset URL for a file within a package theme.
     *
     * @param string $themePath The base theme path.
     * @param string $filePath The asset file path.
     *
     * @return string A cache-busting URL for the asset.
     */
    public function autocache(string $themePath, string $filePath): string
    {
        $themeUrl = sprintf('%s/%s', $themePath, $filePath);
        $path = $_SERVER['DOCUMENT_ROOT'] . $themeUrl;
        return file_exists($path) ? sprintf('%s?%s', $themeUrl, filemtime($path)) : $themeUrl;
    }
}
