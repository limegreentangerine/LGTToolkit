<?php

namespace LgtToolkit\Providers\LgtMail;

use Concrete\Core\Foundation\Service\Provider as ServiceProvider;

/**
 * Registers the package mail service with the application.
 */
class LgtMailServiceProvider extends ServiceProvider
{
    /**
     * Registers the package mail service binding.
     */
    public function register()
    {
        $this->app->singleton(
            'lgt_mail',
            \LgtToolkit\Providers\LgtMail\LgtMailService::class,
        );
    }
}
