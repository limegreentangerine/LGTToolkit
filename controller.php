<?php

namespace Concrete\Package\LgtToolkit;

use Concrete\Core\Command\Task\Manager as TaskManager;
use Concrete\Core\Events\EventDispatcher;
use Concrete\Core\Package\Package;
use Concrete\Core\Production\Modes;
use Core;
use Page;
use LgtToolkit\ConcreteDebugbar\Debugbar as ConcreteDebugbar;
use LgtToolkit\Package\BlockTrait;

class Controller extends Package
{
    use BlockTrait;

    /**
     * Placeholder for Debugbar Injection
     * @var string
     */
    const PLACEHOLDER_TEXT = '<!-- debugbar:placeholder -->';

    /**
     * The packages handle.
     * Note that this must be unique in the
     * entire concrete5 package ecosystem.
     *
     * @var string
     */
    protected $pkgHandle = 'lgt-toolkit';

    /**
     * The packages version.
     *
     * @var string
     */
    protected $pkgVersion = '1.0.0-beta.1';

    /**
     * The minimum Concrete version compatible with the package.
     * Override this value according to the minimum required version for your package.
     *
     * @var string
     */
    protected $appVersionRequired = '9.5.0';

    /**
     * The minimum PHP version compatible with the package.
     * Override this value according to the minimum required version for your package.
     *
     * @var string
     * @var string
     */
    protected $phpVersionRequired = '8.4';

    /**
     * Package service providers to register.
     *
     * eg. 'Concrete\Package\PackageHandle\Src\Providers\PackageServiceProvider'
     *
     * @var array
     */
    protected $providers = [
        'lgt_mail' => '\LgtToolkit\Providers\LgtMail\LgtMailServiceProvider',
        'autocache' => '\LgtToolkit\Providers\AutoCache\AutoCacheServiceProvider',
        'focal_point' => '\LgtToolkit\Providers\FocalPoint\FocalPointServiceProvider',
        'express_debugger' => '\LgtToolkitProviders\Express\Debugger\ExpressDebuggerServiceProvider',
    ];

    /**
     * An array describing the package dependencies.
     * Keys are package handles.
     * Values may be:
     * - false: this package can't be installed if the other package is already installed.
     * - true: this package can't be installed of the other package is not installed
     * - a string: this package can't be installed of the other package is not installed or it's installed with an older version
     * - an array with two strings, representing the minimum and the maximum version of the other package to be installed.
     *
     * @var array
     *
     * @example [
     *     // This package can't be installed if a package with handle other_package_1 is already installed.
     *     'other_package_1' => false,
     *     // This package can't be installed if a package with handle other_package_2 is not installed.
     *     'other_package_2' => true,
     *     // This package can't be installed if a package with handle other_package_3 is not installed, or it has a version prior to 1.0
     *     'other_package_3' => '1.0',
     *     // This package can't be installed if a package with handle other_package_4 is not installed, or it has a version prior to 2.0, or it has a version after 2.9
     *     'other_package_4' => ['2.0', '2.9'],
     * ]
     */
    protected $packageDependencies = [];

    /**
     * Package class autoloader registrations
     * The package install helper class, included with this boilerplate,
     * is activated by default.
     *
     * @see https://goo.gl/4wyRtH
     * @var array
     */
    protected $pkgAutoloaderRegistries = [
        'src' => '\LgtToolkit',
    ];

    /**
     * Package tasks to register.
     *
     * eg. 'task_handle' => \PackageHandle\Command\Task\Controller\TaskHandleController::class,
     *
     * @var array
     */
    protected $tasks = [];

    /**
     * Package classes to override core concrete classes
     *
     * eg. \Concrete\Core\SomeClass::class => \PackageHandle\SomeClass:class
     *
     * @var array
     */
    protected $overrides = [
        \Concrete\Core\Area\GlobalArea::class => \LgtToolkit\Area\GlobalArea::class,
        \Concrete\Core\Page\PageList::class => \LgtToolkit\Page\PageList::class,
        \Concrete\Core\Page\Theme\Theme::class => \LgtToolkit\Page\Theme\Theme::class,
    ];

    /**
     * Register URL Routes
     */
    private function registerRoutes() {}

    /**
     * Register Events
     */
    private function registerEvents() {}

    /**
     * Register Package Tasks
     */
    private function registerTasks(): void
    {
        $manager = $this->app->make(TaskManager::class);

        foreach ($this->tasks as $handle => $class) {
            $manager->extend($handle, function () use ($class) {
                return $this->app->make($class);
            });
        }
    }

    /**
     * Install or Upgrade
     *
     * @var $pkg Package
     */
    protected function installOrUpgrade(\Concrete\Core\Entity\Package $pkg): void
    {
        // Install Blocks
        $this->autoInstallBlocks($pkg);

        // Install Jobs/Tasks
        $this->installContentFile('tasks.xml');
    }

    protected function registerOverrides(): void
    {
        foreach ($this->overrides as $core => $override) {
            $this->app->bind($core, $override);
        }
    }

    protected function registerServiceProviders(): void
    {
        foreach ($this->providers as $class) {
            (new $class($this->app))->register();
        }
    }

    public function getPackageName()
    {
        return t('LGT Toolkit');
    }

    public function getPackageDescription()
    {
        return t('LGT tools and defaults for Concrete CMS');
    }

    public function on_start()
    {
        $pkg = Core::make('Concrete\Core\Package\PackageService')->getByHandle($this->pkgHandle);
        $config = $pkg->getFileConfig();

        $this->registerServiceProviders();
        $this->registerOverrides();
        $this->registerRoutes();
        $this->registerEvents();
        $this->registerTasks();

        if (
            $config->get('lgt_toolkit.debug') === true &&
            Core::make('config')->get('concrete.security.production.mode') === Modes::MODE_DEVELOPMENT
        ) {
            $this->showDebugBar();
        }
    }

    /**
     * The packages install routine.
     */
    public function install()
    {
        $pkg = parent::install();
        $this->installDatabase();
        $this->installOrUpgrade($pkg);
    }

    /**
     * The packages upgrade routine.
     */
    public function upgrade()
    {
        $pkg = Core::make('Concrete\Core\Package\PackageService')->getByHandle($this->pkgHandle);
        parent::upgrade();
        $this->installOrUpgrade($pkg);
    }

    protected function showDebugBar(): void {
        $app = $this->getApplication();

        $app->singleton('debugbar', ConcreteDebugbar::class);
        $app->bind('debugbar/renderer', function () use ($app) {
            /** @var ConcreteDebugbar $debugbar */
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
