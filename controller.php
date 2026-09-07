<?php

namespace Concrete\Package\LgtToolkit;

use Core;
use Route;
use Events;
use LgtToolkit\Package\PageTrait;
use Concrete\Core\Package\Package;
use LgtToolkit\Package\BlockTrait;
use LgtToolkit\Events\File as FileEvent;
use LgtToolkit\Events\Page as PageEvent;
use LgtToolkit\Events\Cache as CacheEvent;
use Concrete\Core\Command\Task\Manager as TaskManager;

class Controller extends Package
{
    use BlockTrait;
    use PageTrait;

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
    protected $pkgVersion = '1.0.0-beta.5';

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
        \LgtToolkit\Providers\LgtMail\LgtMailServiceProvider::class,
        \LgtToolkit\Providers\AutoCache\AutoCacheServiceProvider::class,
        \LgtToolkit\Providers\FocalPoint\FocalPointServiceProvider::class,
        \LgtToolkit\Providers\Express\Debugger\ExpressDebuggerServiceProvider::class,
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
    protected $bindings = [
        \Concrete\Core\Area\GlobalArea::class => \LgtToolkit\Area\GlobalArea::class,
        \Concrete\Core\Page\PageList::class => \LgtToolkit\Page\PageList::class,
        \Concrete\Core\Page\Theme\Theme::class => \LgtToolkit\Page\Theme\Theme::class,
    ];

    /**
     * Concrete Interface overrides to be copied to /application
     *
     * @var array
     */
    protected $applicationOverrides = [
        '/single_pages/dashboard/files/details.php',
    ];

    /**
     * Register URL Routes
     */
    private function registerRoutes()
    {
        /**
         * Duplicate Express Objects Routes
         */
        Route::register('/duplicate/express', 'LgtToolkit\Express\DuplicateExpressObjects::convert');

        /**
         * Get Mapbox API Key from Settings
         */
        Route::register('/mapbox/init', '\LgtToolkit\Ajax\Mapbox::getApiKey');

        /**
         * Block Ajax Routes
         */
        Route::register('/ajax/lgt/file-list', 'Concrete\Package\LgtToolkit\Block\LgtAjaxFileList\Controller::getFiles');
        Route::register('/ajax/lgt/page-list', 'Concrete\Package\LgtToolkit\Block\LgtAjaxPageList\Controller::getNextPage');

        /**
         * Image Focal Point Routes
         */
        Route::register('/lgt-toolkit/focal_point', '\Concrete\Package\LgtToolkit\Controller\Dialog\FocalPoint::view');
        Route::register('/lgt-toolkit/focal_point/submit', '\Concrete\Package\LgtToolkit\Controller\Dialog\FocalPoint::submit');
    }

    /**
     * Register Events
     */
    private function registerEvents()
    {
        Events::addListener('on_before_render', function ($event) {
            PageEvent::redirector();
            PageEvent::processCookiePolicy();
        });

        Events::addListener('on_user_logout', function () {
            CacheEvent::disableDevMode();
        });

        Events::addListener('on_cache_flush', function () {
            CacheEvent::forceCacheClear();
        });

        Events::addListener('on_file_delete', function ($event) {
            FileEvent::removeFocalPoint($event);
        });
    }

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
        // Add Single Pages
        $this->addSinglePage('/dashboard/lgt_toolkit', $pkg, t('LGT Toolkit'));
        $this->addSinglePage('/dashboard/lgt_toolkit/cookie_popup', $pkg, t('Cookie Popup'), t('Cookie Popup settings.'));
        $this->addSinglePage('/dashboard/lgt_toolkit/cloudflare', $pkg, t('Cloudflare'), t('Cloudflare API settings.'));
        $this->addSinglePage('/dashboard/lgt_toolkit/mapbox', $pkg, t('Mapbox'), t('Mapbox API settings.'));
        $this->addSinglePage('/dashboard/lgt_toolkit/uaccess', $pkg, t('UAccess'), t('UAccess Code.'));
        $this->addSinglePage('/dashboard/lgt_toolkit/duplicate_express', $pkg, t('Duplicate Express Objects'), t('Duplicate Express Objects'));

        // Install Blocks
        $this->autoInstallBlocks($pkg);

        // Install Interface Overrides to /application
        $this->installApplicationOverrides();
    }

    protected function registerBindings(): void
    {
        foreach ($this->bindings as $core => $override) {
            $this->app->bind($core, $override);
        }
    }

    protected function registerServiceProviders(): void
    {
        foreach ($this->providers as $class) {
            (new $class($this->app))->register();
        }
    }

    protected function installApplicationOverrides(bool $overwrite = false): void
    {
        foreach ($this->applicationOverrides as $path) {
            $source = DIR_PACKAGES . '/' . $this->pkgHandle . '/overrides' . $path;
            $destination = DIR_APPLICATION . $path;

            if (!file_exists($source)) {
                throw new \RuntimeException(sprintf(
                    'Application override not found: %s',
                    $source,
                ));
            }

            if (!$overwrite && file_exists($destination)) {
                continue;
            }

            if (!is_dir(dirname($destination))) {
                mkdir(dirname($destination), 0755, true);
            }

            if (!copy($source, $destination)) {
                throw new \RuntimeException(sprintf(
                    'Unable to install application override: %s',
                    $destination,
                ));
            }
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
        $this->registerBindings();
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
