<?php

namespace LgtToolkit\ConcreteDebugbar\DataCollector;

use Concrete\Core\Logging\LogList;
use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\DataCollector;

class LogDataCollector extends DataCollector implements Renderable
{
    /**
     * @inheritDoc
     */
    public function collect()
    {
        $list = new LogList();
        $list->sortBy('l.time', 'desc');
        $list->setItemsPerPage(20);
        $pagination = $list->getPagination();
        $logs = $pagination->getCurrentPageResults();

        $data = [];
        foreach ($logs as $log) {
            $data[$log->getDisplayTimestamp()] = $this->getDataFormatter()->formatVar($log->getMessage());
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'concrete_log';
    }

    /**
     * @inheritDoc
     */
    public function getWidgets()
    {
        return [
            'logs' => [
                'icon' => 'fas fa-bars',
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'concrete_log',
                'default' => '{}',
            ],
        ];
    }

}
