<?php

namespace Concrete\Package\LgtToolkit\Controller\SinglePage\Dashboard\LgtToolkit;

use Concrete\Core\Entity\Package;
use Concrete\Core\Error\UserMessageException;
use LgtToolkit\Cloudflare\Api as CloudflareApi;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Controller\DashboardPageController;

class Cloudflare extends DashboardPageController
{
    protected Package $pkg;
    protected $helpers = [
        'form',
    ];

    protected function validate($request)
    {
        $vstrings = $this->app->make('helper/validation/strings');
        $vnumbers = $this->app->make('helper/validation/numbers');

        if (!$vstrings->notempty($request->request('base_url'))) {
            $this->error->add(t('Please enter a URL'), 'base_url');
        }

        if (!$vstrings->notempty($request->request('zone_id'))) {
            $this->error->add(t('Please enter Zone ID'), 'zone_id');
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
        if ($this->request->isPost()) {
            if (!$this->token->validate('submit')) {
                $this->error->add($this->token->getErrorMessage());
            }

            if (!is_object($this->pkg)) {
                return new UserMessageException(t('LGT Toolkit Package not found'));
            }
            $config = $this->pkg->getFileConfig();


            $this->validate($this->request);

            if (!$this->error->has()) {
                if ($this->request->request('activate') !== null) {
                    $config->save('lgt_toolkit.cloudflare.activate', true);
                } else {
                    $config->save('lgt_toolkit.cloudflare.activate', false);
                }

                $config->save('lgt_toolkit.cloudflare.base_url', $this->request->request('base_url'));
                $config->save('lgt_toolkit.cloudflare.zone_id', $this->request->request('zone_id'));
                $config->save('lgt_toolkit.cloudflare.token', $this->request->request('token'));

                $this->flash('success', t('Cloudflare settings saved.'));
                return $this->redirect('/dashboard/lgt_toolkit/cloudflare');
            }
            $this->set('formContent', $this->request->request());

        } else {
            return $this->redirect('/dashboard/lgt_toolkit/cloudflare');
        }
    }

    public function clear()
    {
        $rf = $this->app->make(ResponseFactoryInterface::class);

        if (!$this->token->validate('delete-cloudflare-settings')) {
            $this->error->add($this->token->getErrorMessage());
        } else {
            if (is_object($this->pkg) && $config = $this->pkg->getFileConfig()) {
                $config->save('lgt_toolkit.cloudflare.activate', false);
                $config->save('lgt_toolkit.cloudflare.zone_id', '');
                $config->save('lgt_toolkit.cloudflare.token', '');

                return $rf->json(true);
            }
            $this->error->add(t('LGT Toolkit not found.'));

        }

        return $rf->json($this->error->jsonSerialize());
    }

    public function getCloudflareDevelopmentMode(): string
    {
        $api = new CloudflareApi();
        $response = $api->getDevelopmentMode();

        if ($response->getStatusCode() !== 200) {
            $error_string = $response->getUrl() . ' - (' . $response->getStatusCode() . ' ' . $response->getStatusText($response->getStatusCode()) . ')';

            $body = $response->getBodyDecoded();
            foreach ($body->errors as $error) {
                $error_string = $error_string . ' ' . $error->message;
            }

            return $error_string;
        }

        if ($r = $response->getBodyDecoded()) {
            if ($r->result == null) {
                return $response->body;
            }

            if ($r->result->id !== 'development_mode') {
                return $response->body;
            }

            if (!$r->result->editable) {
                return t('Not editable, check token permissions');
            }

            return strtoupper($r->result->value);
        }
        return t('An Unknown Error Occurred');

    }
}
