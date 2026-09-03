<?php

namespace LgtToolkit\ConcreteDebugbar;

use Page;
use Concrete\Core\Events\EventDispatcher;

trait DebugBarTrait
{
    /**
     * Placeholder for Debugbar Injection
     * @var string
     */
    public const PLACEHOLDER_TEXT = '<!-- debugbar:placeholder -->';

    protected function showDebugBar(): void
    {
        $app = $this->getApplication();

        $app->singleton('debugbar', Debugbar::class);
        $app->bind('debugbar/renderer', function () use ($app) {
            /** @var Debugbar $debugbar */
            $debugbar = $app->make('debugbar');

            return $debugbar->getJavascriptRenderer($this->getRelativePath() . '/vendor/maximebf/debugbar/src/DebugBar/Resources');
        });
        $app->bind('debugbar/messages', function () use ($app) {
            $debugbar = $app->make('debugbar');

            return $debugbar['messages'];
        });
        $app->bind('debugbar/time', function () use ($app) {
            $debugbar = $app->make('debugbar');

            return $debugbar['time'];
        });

        /** @var EventDispatcher $director */
        $director = $app->make('director');

        $director->addListener('on_before_dispatch', function ($event) use ($app) {
            $app->make('debugbar/time')->startMeasure('dispatch', t('Run App'));
        });

        $director->addListener('on_page_view', function ($event) use ($app) {
            $app->make('debugbar/time')->startMeasure('page_view', t('Render Page'));
        });

        $director->addListener('on_start', function ($event) use ($app) {
            $app->make('debugbar/time')->startMeasure('render_view', t('Render View'));
        });

        $director->addListener('on_before_render', function ($event) use ($app) {
            $debugbarRenderer = $app->make('debugbar/renderer');
            $v = $event->getArgument('view');
            $v->addHeaderItem($debugbarRenderer->renderHead());
            $v->addFooterItem(self::PLACEHOLDER_TEXT);
            $app->make('debugbar/time')->startMeasure('render_template', t('Render Template'));
        });

        $director->addListener('on_render_complete', function ($event) use ($app) {
            if ($app->make('debugbar/time')->hasStartedMeasure('render_view')) {
                $app->make('debugbar/time')->stopMeasure('render_view');
            }
            if ($app->make('debugbar/time')->hasStartedMeasure('render_template')) {
                $app->make('debugbar/time')->stopMeasure('render_template');
            }
        });

        $director->addListener('on_shutdown', function ($event) use ($app) {
            if ($app->make('debugbar/time')->hasStartedMeasure('page_view')) {
                $app->make('debugbar/time')->stopMeasure('page_view');
            }
            if ($app->make('debugbar/time')->hasStartedMeasure('dispatch')) {
                $app->make('debugbar/time')->stopMeasure('dispatch');
            }
        });

        $director->addListener('on_block_load', function ($event) use ($app) {
            $bID = $event->getArgument('bID');
            $btHandle = $event->getArgument('btHandle');
            $app->make('debugbar/time')->startMeasure(sprintf('load_block_%d', $bID), sprintf('Render %s block (bID: %d)', $btHandle, $bID));
        });

        $director->addListener('on_block_before_render', static function ($event) use ($app) {
            /** @var \Concrete\Core\Block\Block $b */
            $b = $event->getBlock();
            if ($b) {
                $bID = $b->getBlockID();
                $btHandle = $b->getBlockTypeHandle();
                $app->make('debugbar/time')->startMeasure(sprintf('render_block_%d', $bID), sprintf('Render %s block template (bID: %d)', $btHandle, $bID));
            }
        });

        $director->addListener('on_block_output', static function ($event) use ($app) {
            /** @var \Concrete\Core\Block\Block $b */
            $b = $event->getBlock();
            if ($b) {
                $bID = $b->getBlockID();
                if ($app->make('debugbar/time')->hasStartedMeasure(sprintf('load_block_%d', $bID))) {
                    $app->make('debugbar/time')->stopMeasure(sprintf('load_block_%d', $bID), [
                        'arHandle' => $b->getAreaHandle(),
                    ]);
                }
                if ($app->make('debugbar/time')->hasStartedMeasure(sprintf('render_block_%d', $bID))) {
                    $app->make('debugbar/time')->stopMeasure(sprintf('render_block_%d', $bID), [
                        'template' => $b->getBlockFilename(),
                    ]);
                }
            }
        });

        $director->addListener('on_page_output', function ($event) use ($app) {
            $page = Page::getCurrentPage();
            if (!$page->isAdminArea()) {
                $debugbarRenderer = $app->make('debugbar/renderer');
                $contents = $event->getArgument('contents');
                $contents = str_replace(self::PLACEHOLDER_TEXT, $debugbarRenderer->render(), $contents);
                $event->setArgument('contents', $contents);
            }
        });
    }
}
