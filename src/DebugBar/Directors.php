<?php

namespace LgtToolkit\DebugBar;

use Concrete\Core\Application\Application;
use Concrete\Core\Entity\Express\Entity;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DebugBar;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Directors
{
    protected Application $application;
    protected DebugBar $debugbar;

    public function __construct(Application $app, DebugBar $debugbar)
    {
        $this->application = $app;
        $this->debugbar = $debugbar;
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
