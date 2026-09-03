<?php

namespace LgtToolkit\AutoCache;

use Concrete\Core\Foundation\Service\Provider;

class AutoCacheServiceProvider extends Provider
{
    public function register()
    {
        $this->app->singleton(
            'autocache',
            \LgtToolkit\AutoCache\AutoCacheService::class,
        );
    }
}
