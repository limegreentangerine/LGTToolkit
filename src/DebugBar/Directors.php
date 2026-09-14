<?php

namespace LgtToolkit\DebugBar;

use DebugBar\DebugBar;
use Doctrine\DBAL\Logging\DebugStack;
use Concrete\Core\Entity\Express\Entity;
use Concrete\Core\Application\Application;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\RequestDataCollector;
use Concrete\Core\Database\Connection\Connection;
use Symfony\Component\EventDispatcher\EventDispatcher;
use LgtToolkit\DebugBar\DataCollector\LogDataCollector as ConcreteLogDataCollector;
use LgtToolkit\DebugBar\DataCollector\DoctrineCollector;
use LgtToolkit\DebugBar\DataCollector\SessionDataCollector as ConcreteSessionDataCollector;
use LgtToolkit\DebugBar\DataCollector\EnvironmentDataCollector as ConcreteEnvironmentDataCollector;
use LgtToolkit\DebugBar\DataCollector\RequestDataCollector as ConcreteRequestDataCollector;

class Directors
{
    protected Application $application;
    protected DebugBar $debugbar;

    public function __construct(Application $app, DebugBar $debugbar)
    {
        $this->application = $app;
        $this->debugbar = $debugbar;
    }

    public function addStandardCollectors()
    {
        if (!$this->debugbar->hasCollector('memory')) {
            $this->debugbar->addCollector(new MemoryCollector());
        }

        if (!$this->debugbar->hasCollector('messages')) {
            $this->debugbar->addCollector(new MessagesCollector());
        }

        if (!$this->debugbar->hasCollector('php')) {
            $this->debugbar->addCollector(new PhpInfoCollector());
        }

        if (!$this->debugbar->hasCollector('request')) {
            $this->debugbar->addCollector(new RequestDataCollector());
        }

        if (!$this->debugbar->hasCollector('time')) {
            $this->debugbar->addCollector(new TimeDataCollector());
        }

        if (!$this->debugbar->hasCollector('exceptions')) {
            $this->debugbar->addCollector(new ExceptionsCollector());
        }
    }

    public function addConcreteCollectors()
    {
        if (!$this->debugbar->hasCollector('concrete_environment')) {
            $this->debugbar->addCollector(new ConcreteEnvironmentDataCollector());
        }

        if (!$this->debugbar->hasCollector('concrete_log')) {
            $this->debugbar->addCollector(new ConcreteLogDataCollector());
        }

        if (!$this->debugbar->hasCollector('concrete_request')) {
            $this->debugbar->addCollector(new ConcreteRequestDataCollector());
        }

        if (!$this->debugbar->hasCollector('concrete_session')) {
            $this->debugbar->addCollector(new ConcreteSessionDataCollector());
        }
    }

    public function addDoctineCollectors()
    {
        $doctrineDebugStack = new DebugStack();
        $connection = $this->application->make(Connection::class);
        $connection->getConfiguration()->setSQLLogger($doctrineDebugStack);
        $this->debugbar->addCollector(new DoctrineCollector($doctrineDebugStack));
    }

    public function renderer()
    {
        /** @var EventDispatcher $director */
        $director = $this->application->make('director');

        /** @var TimeDataCollector $timeCollector */
        $timeCollector = $this->debugbar->getCollector('time');

        $director->addListener('on_before_dispatch', function ($event) use ($timeCollector) {
            $timeCollector->startMeasure('dispatch', t('Run App'));
        });

        $director->addListener('on_page_view', function ($event) use ($timeCollector) {
            $timeCollector->startMeasure('page_view', t('Render Page'));
        });

        $director->addListener('on_start', function ($event) use ($timeCollector) {
            $timeCollector->startMeasure('page_view', t('Render Page'));
        });

        $director->addListener('on_before_render', function ($event) use ($timeCollector) {
            $timeCollector->startMeasure('render_template', t('Render Template'));
        });

        $director->addListener('on_render_complete', function ($event) use ($timeCollector) {
            if ($timeCollector->hasStartedMeasure('render_view')) {
                $timeCollector->stopMeasure('render_view');
            }

            if ($timeCollector->hasStartedMeasure('render_template')) {
                $timeCollector->stopMeasure('render_template');
            }
        });

        $director->addListener('on_shutdown', function ($event) use ($timeCollector) {
            if ($timeCollector->hasStartedMeasure('page_view')) {
                $timeCollector->stopMeasure('page_view');
            }
            if ($timeCollector->hasStartedMeasure('dispatch')) {
                $timeCollector->stopMeasure('dispatch');
            }
        });

        $director->addListener('on_block_load', function ($event) use ($timeCollector) {
            $bID = $event->getArgument('bID');
            $btHandle = $event->getArgument('btHandle');
            $timeCollector->startMeasure(sprintf('load_block_%d', $bID), sprintf('Render %s block (bID: %d)', $btHandle, $bID));
        });

        $director->addListener('on_block_before_render', static function ($event) use ($timeCollector) {
            /** @var \Concrete\Core\Block\Block $b */
            $b = $event->getBlock();
            if ($b) {
                $bID = $b->getBlockID();
                $btHandle = $b->getBlockTypeHandle();
                $timeCollector->startMeasure(sprintf('render_block_%d', $bID), sprintf('Render %s block template (bID: %d)', $btHandle, $bID));
            }
        });

        $director->addListener('on_block_output', static function ($event) use ($timeCollector) {
            /** @var \Concrete\Core\Block\Block $b */
            $b = $event->getBlock();
            if ($b) {
                $bID = $b->getBlockID();
                if ($timeCollector->hasStartedMeasure(sprintf('load_block_%d', $bID))) {
                    $timeCollector->stopMeasure(sprintf('load_block_%d', $bID), [
                        'arHandle' => $b->getAreaHandle(),
                    ]);
                }
                if ($timeCollector->hasStartedMeasure(sprintf('render_block_%d', $bID))) {
                    $timeCollector->stopMeasure(sprintf('render_block_%d', $bID), [
                        'template' => $b->getBlockFilename(),
                    ]);
                }
            }
        });
    }

    public function expressDebugging(Entity $express)
    {
        /** @var MessagesCollector $messagesCollector */
        $messagesCollector = $this->debugbar->getCollector('messages');

        $messagesCollector->info(t('Available Attributes on Entity: %s', $express->getName()));

        foreach ($express->getAttributes() as $attribute) {
            $messagesCollector->info(t('%s: can be access using getAttribute("%s") on this object. AttributeType: %s', $attribute->getAttributeKeyName(), $attribute->getAttributeKeyHandle(), $attribute->getAttributeTypeHandle()));
        }

        foreach ($express->getAssociations() as $association) {
            $messagesCollector->info(t('An object association was found, this can be access using getAssociation("%s") on this object.', $association->getComputedTargetPropertyName()));
        }
    }
}
