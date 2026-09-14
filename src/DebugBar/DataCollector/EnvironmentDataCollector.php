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
        $data['environment'] = $this->getDataFormatter()->formatVar($app->environment());
        $data['variables'] = $this->getDataFormatter()->formatVar(get_defined_vars());
        $data['server'] = $this->getDataFormatter()->formatVar($_SERVER);
        $data['classes'] = $this->getDataFormatter()->formatVar(get_declared_classes());
        $data['functions'] = $this->getDataFormatter()->formatVar(get_defined_functions());
        $data['constants'] = $this->getDataFormatter()->formatVar(get_defined_constants());

        return $data;
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
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'concrete_environment',
                'default' => '{}',
            ],
        ];
    }
}
