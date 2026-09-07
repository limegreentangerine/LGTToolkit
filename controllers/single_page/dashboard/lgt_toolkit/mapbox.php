<?php

namespace Concrete\Package\LgtToolkit\Controller\SinglePage\Dashboard\LgtToolkit;

use Concrete\Core\Entity\Package;
use Concrete\Core\Page\Controller\DashboardPageController;

class Mapbox extends DashboardPageController
{
    protected Package $pkg;
    protected $helpers = [
        'form',
    ];

    public function on_start()
    {
        parent::on_start();

        $this->pkg = $this->app->make('Concrete\Core\Package\PackageService')->getByHandle('lgt-toolkit');
        $this->set('pkg', $this->pkg);
    }

    public function save()
    {
        if ($this->request->isPost()) {
            // TODO: build mapbox token save function
            die('save');
        }
        return $this->buildRedirect('/dashboard/lgt_toolkit/mapbox');

    }
}
