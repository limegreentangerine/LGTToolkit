<?php

namespace Concrete\Package\LgtToolkit\Controller\SinglePage\Dashboard\LgtToolkit;

use Concrete\Core\Entity\Package;
use Concrete\Core\Cache\Page\PageCache;
use Concrete\Core\Page\Controller\DashboardPageController;

class Uaccess extends DashboardPageController
{
    protected $helpers = [
        'form',
    ];

    protected $codePlacement = [
        'header' => 'Header',
        'footer' => 'Footer',
    ];
    protected Package $pkg;

    public function on_start()
    {
        parent::on_start();

        $this->pkg = $this->app->make('Concrete\Core\Package\PackageService')->getByHandle('lgt-toolkit');
        $this->set('pkg', $this->pkg);
        $this->set('codePlacement', $this->codePlacement);
    }

    public function save()
    {
        if ($this->isPost()) {
            $post = $this->post();

            if ($this->token->validate('submit')) {
                $config = $this->pkg->getFileConfig();
                $config->save('lgt_toolkit.uaccess.code', $this->post('code'));
                $config->save('lgt_toolkit.uaccess.placement', $this->post('placement'));

                $pageCache = PageCache::getLibrary();
                if (is_object($pageCache)) {
                    $pageCache->flush();
                }

                $this->set('message', implode(PHP_EOL, [
                    t('UAccess code settings updated successfully.'),
                    t('Cached files removed.'),
                ]));
            } else {
                $this->error->add($this->token->getErrorMessage());
            }
        }
    }
}
