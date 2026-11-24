<?php

namespace LgtToolkit\Support\Delivery;

use GuzzleHttp\HandlerStack;
use ClassKit\Api\Enum\ResponseType;
use ClassKit\Api\Response\Response;
use GuzzleHttp\Client as HttpClient;
use ClassKit\Api\ConnectionController;

/**
 * The helpdesk portal API. Redirects are never followed (the portal drops POST bodies on an http redirect,
 * and any 3xx means the endpoint is misconfigured). TLS verification stays at Guzzle's default (on).
 */
class PortalConnection extends ConnectionController
{
    /**
     * @param ?callable $handler Guzzle handler; tests pass a MockHandler
     */
    public function __construct(string $url, int $timeout, ?callable $handler = null)
    {
        parent::__construct($url, ResponseType::JSON->value, ['Accept' => 'application/json']);

        $this->timeout = $timeout;
        $this->connectTimeout = 5;
        // ClassKit's client is protected, so this replaces it rather than patching ClassKit.
        $this->client = new HttpClient([
            'allow_redirects' => false,
            'handler' => $handler instanceof HandlerStack ? $handler : HandlerStack::create($handler),
        ]);
    }

    /**
     * Throws \JsonException instead of ever sending an empty body.
     */
    protected function constructRequestBody(array $data): ?string
    {
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * The key is passed per request so it is never held in a property that could be dumped or logged.
     */
    public function postTicket(array $payload, #[\SensitiveParameter] string $apiKey): Response
    {
        return $this->makeRequest('POST', $this->getBaseUrl(), $payload, ['Authorization' => 'Bearer ' . $apiKey]);
    }
}
