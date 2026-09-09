<?php

namespace LgtToolkit\Providers\AutoCache;

use Concrete\Core\Foundation\Service\Provider;

/**
 * Registers the auto-cache service within the application container.
 */
class AutoCacheServiceProvider extends Provider
{
    /**
     * Registers the autocache service binding.
     */
    public function register()
    {
        $this->app->singleton(
            'autocache',
            \LgtToolkit\Providers\AutoCache\AutoCacheService::class,
        );
    }
}
