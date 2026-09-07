<?php
namespace Concrete\Package\LgtToolkit\Controller\SinglePage\Dashboard;

use Page;
use PageList;
use Concrete\Core\Entity\Package;
use Concrete\Core\Page\Controller\DashboardPageController;

class LgtToolkit extends DashboardPageController
{
    protected Package $pkg;

    public function on_start()
    {
        parent::on_start();

        $this->pkg = $this->app->make('Concrete\Core\Package\PackageService')->getByHandle('lgt-toolkit');
    }

    public function view()
    {
        $c = Page::getCurrentPage();

        $pl = new PageList();
        $pl->filterByParentID($c->getCollectionID());
        $pl->includeSystemPages();
        $this->set('pages', $pl->get());

        $this->set('checked', false);
    }

    public function getPageThumbnail(string $handle)
    {
        return sprintf('%s/images/thumbnails/%s.png', $this->pkg->getRelativePath(), $handle);
    }

    public function toggle_debugbar()
    {
        $status = $this->getDebugBarStatus();
        $config = $this->pkg->getFileConfig();
        $newStatus = ($status === null || $status === false) ? true : false;
        $config->save('lgt_toolkit.debug', $newStatus);
        return $this->redirect('/dashboard/lgt_toolkit');
    }

    public function getDebugBarStatus()
    {
        $config = $this->pkg->getFileConfig();
        $status = $config->get('lgt_toolkit.debug');
        return ($status === null) ? false : $status;
    }
}
