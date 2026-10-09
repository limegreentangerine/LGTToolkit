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
     * @param string $filePath  The asset file path.
     * @return string A cache-busting URL for the asset.
     */
    public function autocache(string $filePath): string
    {
        return file_exists(DIR_BASE . $filePath) ? sprintf('%s?%s', $filePath, filemtime(DIR_BASE . $filePath)) : $filePath;
    }
}
