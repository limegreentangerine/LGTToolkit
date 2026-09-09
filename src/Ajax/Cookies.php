<?php

namespace LgtToolkit\Ajax;

use Core;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Handles AJAX responses for cookie consent actions.
 */
class Cookies
{
    /**
     * Stores the user's cookie consent choice in the active session.
     *
     * @return Response JSON response indicating the consent result.
     */
    public function allowCookies(): Response
    {
        $session = Core::make('session');
        $session->set('site-cookies', true);
        return new JsonResponse([ 'success' => true ]);
    }

    /**
     * Stores the user's cookie refusal in the active session.
     *
     * @return Response JSON response indicating the refusal result.
     */
    public function disallowCookies(): Response
    {
        $session = Core::make('session');
        $session->set('site-cookies', false);
        return new JsonResponse([ 'success' => true ]);
    }

    /**
     * Determines whether the current visitor has already accepted cookies.
     *
     * @return Response JSON response containing the current cookie state.
     */
    public function checkCookies(): Response
    {
        $session = Core::make('session');
        if ($session->get('site-cookies') !== null) {
            return new JsonResponse(false);
        }
        return new JsonResponse(true);

    }
}
