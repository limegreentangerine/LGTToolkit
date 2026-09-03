<?php

namespace LgtToolkit\AutoCache;

class AutoCacheService
{
    public function autocache(string $themePath, string $filePath): string
    {
        $themeUrl = sprintf('%s/%s', $themePath, $filePath);
        $path = $_SERVER['DOCUMENT_ROOT'] . $themeUrl;
        return file_exists($path) ? sprintf('%s?%s', $themeUrl, filemtime($path)) : $themeUrl;
    }
}
