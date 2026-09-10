<?php

namespace LgtToolkit\Events;

use Log;
use Package;
use LgtToolkit\Cloudflare\Api as CloudflareApi;

class Cache
{
    /**
     * Get the value of config
     */
    public static function getConfig()
    {
        $pkg = Package::getByHandle('lgt_toolkit');
        if (is_object($pkg)) {
            return $pkg->getFileConfig();
        }

        return false;
    }

    /**
     * Get the value of api
     */
    public static function getApi()
    {
        return new CloudflareApi();
    }

    /**
     * Get the value of activate
     */
    public static function getActivate()
    {
        return (self::getConfig() !== false) ? self::getConfig()->get('lgt_toolkit.cloudflare.activate') : false;
    }

    public static function enableDevMode()
    {
        if (self::getActivate()) {
            $response = self::getApi()->setDevelopmentMode('on');
            $body = $response->getBodyDecoded();
            if ($body->success) {
                Log::addInfo('Cloudflare development mode activated.');
            } else {
                Log::addWarning('Cloudflare development mode failed to activate.');
            }
        }
    }

    public static function disableDevMode()
    {
        if (self::getActivate()) {
            $response = self::getApi()->setDevelopmentMode('off');
            $body = $response->getBodyDecoded();
            if ($body->success) {
                Log::addInfo('Cloudflare development mode deactivated.');
            } else {
                Log::addWarning('Cloudflare development mode failed to deactivate.');
            }
        }
    }

    public static function forceCacheClear()
    {
        if (self::getActivate()) {
            $response = self::getApi()->purgeCache();
            $body = $response->getBodyDecoded();
            if ($body->success) {
                Log::addInfo('Cloudflare cache successfully cleared by force.');
            } else {
                Log::addWarning('Cloudflare cache failed to be cleared by force.');
                Log::addInfo(json_encode($body));
            }
        }
    }
}
