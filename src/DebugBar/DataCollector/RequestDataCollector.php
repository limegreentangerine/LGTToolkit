<?php

namespace LgtToolkit\DebugBar\DataCollector;

use Concrete\Core\Http\Request;
use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\DataCollector;
use Concrete\Core\Support\Facade\Application;

class RequestDataCollector extends DataCollector implements Renderable
{
    /**
     * @inheritDoc
     */
    public function collect(): array
    {
        $app = Application::getFacadeApplication();
        /** @var Request $request */
        $request = $app->make(Request::class);

        $data = [];
        $data['path'] = $this->getDataFormatter()->formatVar($request->getPath());
        $data['query'] = $this->getDataFormatter()->formatVar($request->query);
        $data['cookies'] = $this->getDataFormatter()->formatVar($request->cookies);
        $data['headers'] = $this->getDataFormatter()->formatVar($request->headers);
        $data['host'] = $this->getDataFormatter()->formatVar($request->getHost());
        $data['post'] = $this->getDataFormatter()->formatVar($request->getPort());
        $data['clientip'] = $this->getDataFormatter()->formatVar($request->getClientIp());

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'concrete_request';
    }

    /**
     * @inheritDoc
     */
    public function getWidgets(): array
    {
        return [
            'request' => [
                'icon' => 'user',
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'concrete_request',
                'default' => '{}',
            ],
        ];
    }

}
