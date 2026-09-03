<?php

namespace LgtToolkit\Providers\AutoCache;

use Concrete\Core\Foundation\Service\Provider;

class AutoCacheServiceProvider extends Provider
{
    public function register()
    {
        $this->app->singleton(
            'autocache',
            \LgtToolkit\Providers\AutoCache\AutoCacheService::class,
        );
    }
}
