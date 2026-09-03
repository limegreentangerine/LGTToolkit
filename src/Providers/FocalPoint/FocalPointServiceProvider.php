<?php

namespace LgtToolkit\Providers\FocalPoint;

use Concrete\Core\Foundation\Service\Provider;

class FocalPointServiceProvider extends Provider
{
    public function register()
    {
        $this->app->singleton(
            'focal_point',
            \LgtToolkit\Providers\FocalPoint\FocalPointService::class,
        );
    }
}
