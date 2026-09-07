<?php

namespace LgtToolkit\Ajax;

use Core;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class Mapbox
{
    public function getApiKey(): Response
    {
        $pkg = Core::make('Concrete\Core\Package\PackageService')->getByHandle('lgt-toolkit');
        $config = $pkg->getFileConfig();
        return new JsonResponse([
            'apiKey' => $config->get('lgt_toolkit.mapbox.apiKey'),
        ]);
    }
}
