<?php

namespace LgtToolkit\File\FocalPoint;

use Concrete\Core\Foundation\Service\Provider;

class FocalPointServiceProvider extends Provider
{
    public function register()
    {
        $this->app->singleton(
            'focal_point',
            FocalPointService::class,
        );
    }
}
