<?php

namespace LgtToolkit\Redirections;

use Concrete\Core\Application\Application;
use Symfony\Component\HttpFoundation\Request;
use Concrete\Core\Http\Middleware\DelegateInterface;
use Concrete\Core\Http\Middleware\MiddlewareInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Answers requests that match a declared redirection before core can issue its own
 * hard-coded 301 for additional PagePaths, so 302 rules can be honoured.
 */
class RedirectionMiddleware implements MiddlewareInterface
{
    public function __construct(protected Application $app) {}

    protected function match(Request $request): ?RedirectResponse
    {
        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return null;
        }

        $rawPath = method_exists($request, 'getPath') ? $request->getPath() : $request->getPathInfo();
        $path = PathNormaliser::normalise((string) $rawPath);

        if ($path === null || PathNormaliser::isReserved($path)) {
            return null;
        }

        // Fail open: a missing table or database hiccup must never take the site down.
        try {
            $service = $this->app->make(RedirectionService::class);
            $redirection = $service->findBySourcePath($path);
            $page = $redirection ? $service->resolveDestination($redirection) : null;
        } catch (\Throwable) {
            return null;
        }

        if (!$redirection || !$page) {
            return null;
        }

        $url = (string) $page->getCollectionLink();
        $query = $request->getQueryString();

        if ($query) {
            $url .= (str_contains($url, '?') ? '&' : '?') . $query;
        }

        return new RedirectResponse($url, $redirection->getStatusCode());
    }

    public function process(Request $request, DelegateInterface $frame)
    {
        return $this->match($request) ?? $frame->next($request);
    }
}
