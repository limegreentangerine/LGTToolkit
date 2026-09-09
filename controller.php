<?php

namespace Concrete\Package\LgtToolkit;

use Core;
use User;
use View;
use Route;
use Events;
use LgtToolkit\Package\PageTrait;
use Concrete\Core\Package\Package;
use LgtToolkit\Package\BlockTrait;
use Concrete\Core\Production\Modes;
use LgtToolkit\Package\AttributeTrait;
use Concrete\Core\Attribute\Key\FileKey;
use Concrete\Core\Attribute\Key\SiteKey;
use LgtToolkit\Events\File as FileEvent;
use LgtToolkit\Events\Page as PageEvent;
use LgtToolkit\Events\Cache as CacheEvent;
use Concrete\Core\Attribute\Key\CollectionKey;
use Concrete\Core\Command\Task\Manager as TaskManager;

class Controller extends Package
{
    use AttributeTrait;
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
    protected $pkgVersion = '1.0.0-beta.16';

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
    protected $aliases = [
        'GlobalArea' => \LgtToolkit\Area\GlobalArea::class,
        'Page' => \LgtToolkit\Page\Page::class,
        'PageList' => \LgtToolkit\Page\PageList::class,
        'Theme' => \LgtToolkit\Page\Theme\Theme::class,
    ];

    /**
     * Concrete Interface overrides to be copied to /application
     *
     * @var array
     */
    protected $applicationOverrides = [
        'single_pages/dashboard/files/details.php',
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

        /**
         * Placeholders
         *
         * @deprecated
         */
        Route::register('/ajax/lgt_toolkit/blocks/content_site_attribute/get_dummy_text', '\LgtToolkit\Ajax\PlaceholderText::getDummyText');

        /**
         * Cookie Routes
         */
        Route::register('/ajax/allow-cookies', '\LgtToolkit\Ajax\Cookies::allowCookies');
        Route::register('/ajax/disallow-cookies', '\LgtToolkit\Ajax\Cookies::disallowCookies');
        Route::register('/ajax/check-cookies', '\LgtToolkit\Ajax\Cookies::checkCookies');
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

        // Attribute Setup
        $this->setupAttributes($pkg);

        // Install Blocks
        $this->autoInstallBlocks($pkg);

        // Install Interface Overrides to /application
        $this->installApplicationOverrides();

        // Set Core configs
        $this->setCoreConfigSettings();
    }

    protected function setupAttributes(\Concrete\Core\Entity\Package $pkg): void
    {
        // Add/Create Attribute Types
        $this->addAttributeType('country', t('Country'), $pkg);
        $this->addAttributeType('lgt_colour_picker', t('Colour Picker'), $pkg);
        $this->addAttributeType('lgt_file_set', t('File Set'), $pkg);
        $this->addAttributeType('lgt_page_redirector', t('Page Redirector'), $pkg);

        // Add/Get attribute sets
        $siteAttrSet = $this->addAttributeSet('site', 'site_attributes', t('Site Attributes'), $pkg);
        $seoAttrSet = $this->addAttributeSet('collection', 'seo', t('SEO'), $pkg);
        $navAttrSet = $this->addAttributeSet('collection', 'navigation', t('Navigation and Indexing'), $pkg);

        // Add Attributes
        $this->addAttribute('page_banner', t('Page Banner'), 'image_file', CollectionKey::class, null, $pkg);
        $this->addAttribute('seo_header', t('SEO Header'), 'text', CollectionKey::class, $seoAttrSet, $pkg);
        $this->addAttribute('page_redirector', t('Page Redirector'), 'lgt_page_redirector', CollectionKey::class, $navAttrSet, $pkg);
        $this->addAttribute('file_categories', t('File Categories'), 'topics', FileKey::class, null, $pkg);
        $this->addAttribute('site_company_name', t('Company Name'), 'text', SiteKey::class, $siteAttrSet, $pkg);
        $this->addAttribute('site_company_phone', t('Company Phone Number'), 'text', SiteKey::class, $siteAttrSet, $pkg);
        $this->addAttribute('site_company_email', t('Company Email'), 'email', SiteKey::class, $siteAttrSet, $pkg);
        $this->addAttribute('site_company_address', t('Company Address'), 'address', SiteKey::class, $siteAttrSet, $pkg);
        $this->addAttribute('opengraph_default_image', t('Default Sharing Image'), 'image_file', SiteKey::class, $siteAttrSet, $pkg);
    }

    protected function setCoreConfigSettings(): void
    {
        $config = $this->app->make('config');

        // disabled adding of marketplace blocks and themes
        $config->save('concrete.marketplace.enabled', false);

        // not sure what these are but we have them set in old builds
        $config->save('concrete.external.intelligent_search_help', true);
        $config->save('concrete.external.news_overlay', false);
        $config->save('concrete.external.news', false);

        // accessibility
        $config->save('concrete.accessibility.toolbar_titles', true);

        // white labelling
        $config->save('concrete.white_label.name', 'limegreentangerine');
        $config->save('concrete.white_label.logo', false);
        $config->save('concrete.white_label.background_image', 'none');

        // session handlers
        $config->save('concrete.session.name', 'LGTCMSSESSION');
        $config->save('concrete.session.handler', 'database');

        // seo defaults
        $config->save('concrete.seo.url_rewriting', true);
        $config->save('concrete.seo.url_rewriting_all', true);
        $config->save('concrete.seo.trailing_slash', true);
        $config->save('concrete.seo.title_format', '%1$s | %2$s');
        $config->save('concrete.seo.title_segment_separator', ' | ');

        // design
        $config->save('concrete.design.enable_custom', false);
        $config->save('concrete.design.enable_layouts', false);

        // security
        $config->save('concrete.security.session.invalidate_on_ip_mismatch', true);

        //social media
        $config->save('concrete.social.additional_services', [
            ['facebook', 'Facebook', 'fab fa-facebook', '<i class="bi bi-facebook"></i>'],
            ['twitter', 'Twitter/X', 'fab fa-twitter', '<i class="bi bi-twitter-x"></i>'],
            ['instagram', 'Instagram', 'fab fa-instagram', '<i class="bi bi-instagram"></i>'],
            ['github', 'Github', 'fab fa-github-square', '<i class="bi bi-github"></i>'],
            ['dribbble', 'Dribbble', 'fab fa-dribbble', '<i class="bi bi-dribbble"></i>'],
            ['youtube', 'Youtube', 'fab fa-youtube', '<i class="bi bi-youtube"></i>'],
            ['linkedin', 'LinkedIn', 'fab fa-linkedin', '<i class="bi bi-linkedin"></i>'],
            ['reddit', 'Reddit', 'fab fa-reddit', '<i class="bi bi-reddit"></i>'],
            ['steam', 'Steam', 'fab fa-steam', '<i class="bi bi-steam"></i>'],
            ['twitch', 'Twitch', 'fab fa-twitch', '<i class="bi bi-twitch"></i>'],
            ['skype', 'Skype', 'fab fa-skype', '<i class="bi bi-skype"></i>'],
            ['personal_website', 'Personal Website', 'fa fa-external-link-alt', '<i class="bi bi-link-45deg"></i>'],
            ['email', 'Email', 'fa fa-envelope', '<i class="bi bi-envelope-at"></i>'],
            ['phone', 'Phone', 'fa fa-phone-square', '<i class="bi bi-telephone"></i>'],
            ['tiktok', 'TikTok', 'fa fa-tiktok', '<i class="bi bi-tiktok"></i>'],
        ]);

        $this->registerAliases($config);
    }

    protected function registerAliases(mixed $config): void
    {
        $aliases = $config->get('app.aliases');
        if ($aliases !== null) {
            foreach ($this->aliases as $key => $value) {
                $aliases[$key] = $value;
            }
        }

        $config->save('app.aliases', $aliases);
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
            $source = sprintf('%s/%s/overrides/%s', DIR_PACKAGES, $this->pkgHandle, $path);
            $destination = sprintf('%s/%s', DIR_APPLICATION, $path);

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
        $this->registerServiceProviders();
        $this->registerRoutes();
        $this->registerEvents();
        $this->registerTasks();

        $pkg = Core::make('Concrete\Core\Package\PackageService')->getByHandle($this->pkgHandle);
        $config = $pkg->getFileConfig();

        //TODO: maybe improve this uaccess invocation (move to event??)
        if (
            strlen($config->get('lgt_toolkit.uaccess.code')) > 0
            && !User::isLoggedIn()
            && Core::make('config')->get('concrete.security.production.mode') !== Modes::MODE_DEVELOPMENT
        ) {
            $v = View::getInstance();
            if ($config->get('lgt_toolkit.uaccess.placement') == 'header') {
                $v->addHeaderItem($config->get('lgt_toolkit.uaccess.code'));
            } elseif ($config->get('lgt_toolkit.uaccess.placement') == 'footer') {
                $v->addFooterItem($config->get('lgt_toolkit.uaccess.code'));
            }
        }

        //TODO: investigate debug bar problems
        // if (
        //     $config->get('lgt_toolkit.debug') === true &&
        //     Core::make('config')->get('concrete.security.production.mode') === Modes::MODE_DEVELOPMENT
        // ) {
        //     $this->showDebugBar();
        // }
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
