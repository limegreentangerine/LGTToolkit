<?php

namespace LgtToolkit\DebugBar\DataCollector;

use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\DataCollector;
use Concrete\Core\Support\Facade\Application;

class EnvironmentDataCollector extends DataCollector implements Renderable
{
    public function collect(): array
    {
        $app = Application::getFacadeApplication();

        return [
            'environment' => $app->environment(),
            'variables' => $this->formatVarCollapsed(get_defined_vars()),
            'server' => $this->formatVarCollapsed($_SERVER),
            'classes' => $this->formatVarCollapsed(get_declared_classes()),
            'functions' =>$this->formatVarCollapsed(get_defined_functions()),
            'constants' => $this->formatVarCollapsed(get_defined_constants()),
        ];
    }

    private function formatVarCollapsed(mixed $value): string
    {
        return str_replace(
            'sf-dump-expanded',
            'sf-dump-compact',
            $this->getDataFormatter()->formatVar($value)
        );
    }

    public function getName(): string
    {
        return 'concrete_environment';
    }

    public function getWidgets(): array
    {
        return [
            'environment' => [
                'icon' => 'fas fa-server',
                'widget' => 'PhpDebugBar.Widgets.HtmlVariableListWidget',
                'map' => 'concrete_environment',
                'default' => '{}',
            ],
        ];
    }
}
