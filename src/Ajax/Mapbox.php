<?php

namespace LgtToolkit\Ajax;

use Core;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides the configured Mapbox API key for front-end integrations.
 */
class Mapbox
{
    /**
     * Retrieves the current Mapbox API key from the package configuration.
     *
     * @return Response JSON response containing the API key.
     */
    public function getApiKey(): Response
    {
        $pkg = Core::make('Concrete\Core\Package\PackageService')->getByHandle('lgt-toolkit');
        $config = $pkg->getFileConfig();
        return new JsonResponse([
            'apiKey' => $config->get('lgt_toolkit.mapbox.apiKey'),
        ]);
    }
}
