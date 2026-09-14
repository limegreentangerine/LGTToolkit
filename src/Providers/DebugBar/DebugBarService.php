<?php

namespace LgtToolkit\Providers\DebugBar;

use Core;
use Exception;
use DebugBar\DebugBar;
use Concrete\Core\Entity\Package;
use LgtToolkit\DebugBar\Directors;
use Concrete\Core\Production\Modes;
use DebugBar\DataCollector\DataCollector;
use Concrete\Core\Application\Application;

class DebugBarService
{
    protected Application $application;
    protected Package $pkg;
    protected DebugBar $debugbar;

    public function __construct()
    {
        $this->application = Application::getInstance();
        if (!$this->application) {
            return;
        }

        $this->pkg = $this->application->make('Concrete\Core\Package\PackageService')->getByHandle('lgt_toolkit');
        if (!$this->pkg) {
            return;
        }

        if (!$this->useDebugBar() || !$this->isDev()) {
            return;
        }

        $this->debugbar = $this->pkg->getDebugBar() ?? new DebugBar();
        if (!$this->debugbar) {
            return;
        }
    }

    protected function useDebugBar()
    {
        $pkgConfig = $this->pkg->getFileConfig();
        return $pkgConfig->get('lgt_toolkit.debug') === true;
    }

    protected function isDev()
    {
        $siteConfig = Core::make('config');
        return $siteConfig->get('concrete.security.production.mode') === Modes::MODE_DEVELOPMENT;
    }

    public function getDirector()
    {
        return new Directors($this->application, $this->debugbar);
    }

    public function addStandardCollectors()
    {
        $this->getDirector()->addStandardCollectors();
    }

    public function getCollector(string $name): DataCollector
    {
        return $this->debugbar->getCollector($name) ?? throw new Exception(t('Data Collector with name %s not found', $name));
    }

    public function addCollector(DataCollector $collector)
    {
        $this->debugbar->addCollector(new $collector());
    }
}
