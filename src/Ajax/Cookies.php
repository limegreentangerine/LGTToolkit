<?php

namespace LgtToolkit\Ajax;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Handles AJAX responses for cookie consent actions.
 */
class Cookies
{
    private int $cookieTTL = 365 * 24 * 60 * 60; // 1 year in seconds
    public string $cookieName = 'cookie_consent';

    /**
     * setConsentCookie
     *
     * @param string $value
     */
    private function setConsentCookie(string $value): void
    {
        setcookie($this->cookieName, $value, [
            'expires' => time() + $this->cookieTTL,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Stores the user's cookie consent choice in the active session.
     *
     * @return Response JSON response indicating the consent result.
     */
    public function allowCookies(): Response
    {
        $this->setConsentCookie('accepted');
        return new JsonResponse(['status' => 'accepted']);
    }

    /**
     * Stores the user's cookie refusal in the active session.
     *
     * @return Response JSON response indicating the refusal result.
     */
    public function disallowCookies(): Response
    {
        $this->setConsentCookie('declined');
        return new JsonResponse(['status' => 'declined']);
    }
}
