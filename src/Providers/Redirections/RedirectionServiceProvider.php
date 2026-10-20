<?php

namespace LgtToolkit\Providers\Redirections;

use Concrete\Core\Foundation\Service\Provider;
use LgtToolkit\Redirections\RedirectionService;

/**
 * Registers the redirections service within the application container.
 */
class RedirectionServiceProvider extends Provider
{
    /**
     * Registers the redirections service binding.
     */
    public function register()
    {
        $this->app->singleton(RedirectionService::class);
        $this->app->alias(RedirectionService::class, 'lgt_redirections');
    }
}
