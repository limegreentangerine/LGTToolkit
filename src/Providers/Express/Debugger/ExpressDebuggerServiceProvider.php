<?php

namespace LgtToolkit\Providers\Express\Debugger;

use Concrete\Core\Foundation\Service\Provider;

/**
 * Registers the Express debugger service provider.
 */
class ExpressDebuggerServiceProvider extends Provider
{
    /**
     * Registers the Express debugger service binding.
     */
    public function register()
    {
        $this->app->singleton(
            'express_debugger',
            \LgtToolkit\Providers\Express\Debugger\ExpressDebuggerService::class,
        );
    }
}
