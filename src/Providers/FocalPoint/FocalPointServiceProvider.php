<?php

namespace LgtToolkit\Providers\FocalPoint;

use Concrete\Core\Foundation\Service\Provider;

/**
 * Registers the focal-point service within the application container.
 */
class FocalPointServiceProvider extends Provider
{
    /**
     * Registers the focal-point service binding.
     */
    public function register()
    {
        $this->app->singleton(
            'focal_point',
            \LgtToolkit\Providers\FocalPoint\FocalPointService::class,
        );
    }
}
