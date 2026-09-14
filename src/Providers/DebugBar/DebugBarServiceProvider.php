<?php

namespace LgtToolkit\Providers\DebugBar;

use Concrete\Core\Foundation\Service\Provider;

class DebugBarServiceProvider extends Provider
{
    public function register()
    {
        $this->app->singleton(
            'debugbar',
            \LgtToolkit\Providers\DebugBar\DebugBarService::class,
        );
    }
}
