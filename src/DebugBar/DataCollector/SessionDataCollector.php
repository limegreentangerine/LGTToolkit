<?php

namespace LgtToolkit\DebugBar\DataCollector;

use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\DataCollector;
use Concrete\Core\Support\Facade\Application;
use Symfony\Component\HttpFoundation\Session\Session;

class SessionDataCollector extends DataCollector implements Renderable
{
    /**
     * @inheritDoc
     */
    public function collect(): array
    {
        $app = Application::getFacadeApplication();
        /** @var Session $session */
        $session = $app->make('session');

        return $session->all();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'concrete_session';
    }

    /**
     * @inheritDoc
     */
    public function getWidgets(): array
    {
        return [
            'session' => [
                'icon' => 'user',
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'concrete_session',
                'default' => '{}',
            ],
        ];
    }

}
