<?php

namespace LgtToolkit\Providers\Express\Debugger;

use Concrete\Core\Foundation\Service\Provider;

class ExpressDebuggerServiceProvider extends Provider
{
    public function register()
    {
        $this->app->singleton(
            'express_debugger',
            \LgtToolkit\Providers\Express\Debugger\ExpressDebuggerService::class,
        );
    }
}
