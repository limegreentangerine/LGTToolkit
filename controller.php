<?php

namespace Concrete\Package\LgtToolkit;

use Core;
use Concrete\Core\Package\Package;
use LgtToolkit\Package\BlockTrait;
use Concrete\Core\Command\Task\Manager as TaskManager;

class Controller extends Package
{
    use BlockTrait;

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
        // 'lgt_mail'          => '\Application\LgtMail\LgtMailServiceProvider',
        'autocache' => '\LgtToolkit\AutoCache\AutoCacheServiceProvider',
        'focal_point' => '\LgtToolkit\File\FocalPoint\FocalPointServiceProvider',
        // 'express_debugger'  => '\Application\Express\Debugger\ExpressDebuggerServiceProvider'
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
        $this->registerServiceProviders();
        $this->registerOverrides();
        $this->registerRoutes();
        $this->registerEvents();
        $this->registerTasks();
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
}
