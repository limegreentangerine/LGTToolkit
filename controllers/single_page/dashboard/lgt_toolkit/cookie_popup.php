<?php
namespace Concrete\Package\LgtToolkit\Controller\SinglePage\Dashboard\LgtToolkit;

use Package;
use Concrete\Core\Page\Controller\DashboardPageController;

class CookiePopup extends DashboardPageController
{
    protected $helpers = [
        'form',
        'concrete/ui',
        'form/page_selector'
    ];
    protected $pkg;
    protected $locales;
    protected $formContent;
    protected $errors;

    public function on_start()
    {
        parent::on_start();

        $this->pkg = Package::getByHandle('lgt_toolkit');
        $this->set('pkg', $this->pkg);

        $site = $this->app->make('site')->getActiveSiteForEditing();
        $this->locales = $site->getLocales();
        $this->set('locales', $this->locales);
    }

    public function save()
    {
        $this->formContent = $this->post();

        if ($this->post()) {
            if (!$this->token->validate('submit')) {
                $this->error->add($this->token->getErrorMessage());
            }

            $this->formContent['styles']['roundedCorners'] = (isset($this->formContent['styles']['roundedCorners']) ? 1 : 0);
            $this->formContent['styles']['shadow'] = (isset($this->formContent['styles']['shadow']) ? 1 : 0);


            $this->validateSubmit($this->formContent);

            if (!$this->error->has()) {
                if (is_object($this->pkg)) {
                    $config = $this->pkg->getFileConfig();
                    if (isset($this->formContent['activate'])) {
                        $config->save('lgt_toolkit.cookie_popup.activate', true);
                    } else {
                        $config->save('lgt_toolkit.cookie_popup.activate', false);
                    }

                    foreach ($this->formContent['data'] as $language => $data) {
                        foreach ($data as $key => $value) {
                            $configKey = sprintf('lgt_toolkit.cookie_popup.%s.%s', $language, $key);
                            $config->save($configKey, $value);
                        }
                    }

                    foreach ($this->formContent['styles'] as $style => $value) {
                        $styleKey = sprintf('lgt_toolkit.cookie_popup.styles.%s', $style, $key);
                        $config->save($styleKey, $value);
                    }

                    $this->flash('success', t('Cookie popup settings saved.'));
                } else {
                    $this->set('formContent', $this->formContent);
                }
            } else {
                $this->set('errors', $this->error);
                $this->set('formContent', $this->formContent);
            }
        }

        $this->set('token', $this->token);
    }

    protected function validateSubmit($args)
    {
        $vstrings = $this->app->make('helper/validation/strings');
        $vnumbers = $this->app->make('helper/validation/numbers');

        if (!$vstrings->notempty($args['data']['en']['title'])) {
            $this->error->add(t('A title is required'), 'title');
        }

        if (!$vstrings->notempty($args['data']['en']['content'])) {
            $this->error->add('Content is required', 'content');
        }

        if (!$vnumbers->integer($args['data']['en']['linkCID']) || $args['data']['en']['linkCID'] < 1) {
            $this->error->add(t('Policy page must be selected'), 'linkCID');
        }

        if (count($this->formContent['styles']) < 8) {
            $this->error->add(t('All styles need a value'));
        }
    }

    public function getColourOptions()
    {
        return [
            '' => t('Choose a colour...'),
            'primary' => t('Primary'),
            'secondary' => t('Secondary'),
            'light' => t('Light'),
            'dark' => t('Dark')
        ];
    }
}
