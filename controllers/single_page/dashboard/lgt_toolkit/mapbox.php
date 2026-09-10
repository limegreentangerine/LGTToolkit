<?php

namespace Concrete\Package\LgtToolkit\Controller\SinglePage\Dashboard\LgtToolkit;

use Concrete\Core\Entity\Package;
use Concrete\Core\Page\Controller\DashboardPageController;

class Mapbox extends DashboardPageController
{
    protected $helpers = [
        'form',
    ];
    protected Package $pkg;

    protected function validateSubmit(array $args)
    {
        $vstrings = $this->app->make('helper/validation/strings');

        if (!$vstrings->notempty($args['apiKey'])) {
            $this->error->add(t('An API Key is required'), 'apiKey');
        }
    }

    public function on_start()
    {
        parent::on_start();

        $this->pkg = $this->app->make('Concrete\Core\Package\PackageService')->getByHandle('lgt_toolkit');
        $this->set('pkg', $this->pkg);
    }

    public function save()
    {
        if ($this->post()) {
            if (!$this->token->validate('submit')) {
                $this->error->add($this->token->getErrorMessage());
            }

            $this->validateSubmit($this->post());

            if (!$this->error->has() && is_object($this->pkg)) {
                $config = $this->pkg->getFileConfig();
                $config->save('lgt_toolkit.mapbox.apiKey', $this->post('apiKey'));
                return $this->buildRedirect('/dashboard/lgt_toolkit/mapbox');
            }
            $this->set('errors', $this->error);
            $this->set('formContent', $this->post());

        } else {
            return $this->buildRedirect('/dashboard/lgt_toolkit/mapbox');
        }
    }
}
