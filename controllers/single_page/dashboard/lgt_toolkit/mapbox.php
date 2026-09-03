<?php
namespace Concrete\Package\LgtToolkit\Controller\SinglePage\Dashboard\LgtToolkit;

use Package;
use Concrete\Core\Page\Controller\DashboardPageController;

class Mapbox extends DashboardPageController
{
    protected $pkg;
    protected $helpers = [
        'form'
    ];

    public function on_start()
    {
        parent::on_start();

        $this->pkg = Package::getByHandle('lgt-toolkit');
        $this->set('pkg', $this->pkg);
    }

    public function save()
    {
        if ($this->request->isPost()) {
            die('save');
        } else {
            return $this->redirect('/dashboard/lgt_toolkit/mapbox');
        }
    }
}
