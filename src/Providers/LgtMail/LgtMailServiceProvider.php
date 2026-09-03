<?php

namespace LgtToolkit\Providers\LgtMail;

use Concrete\Core\Foundation\Service\Provider as ServiceProvider;

class LgtMailServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(
            'lgt_mail',
            \LgtToolkit\Providers\LgtMail\LgtMailService::class,
        );
    }
}
