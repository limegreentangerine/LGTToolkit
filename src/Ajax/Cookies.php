<?php

namespace LgtToolkit\Ajax;

use Core;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class Cookies
{
    public function allowCookies(): Response
    {
        $session = Core::make('session');
        $session->set('site-cookies', true);
        return new JsonResponse([ 'success' => true ]);
    }

    public function disallowCookies(): Response
    {
        $session = Core::make('session');
        $session->set('site-cookies', false);
        return new JsonResponse([ 'success' => true ]);
    }

    public function checkCookies(): Response
    {
        $session = Core::make('session');
        if ($session->get('site-cookies') !== null) {
            return new JsonResponse(false);
        }
        return new JsonResponse(true);

    }
}
