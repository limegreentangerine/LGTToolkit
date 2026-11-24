<?php

namespace LgtToolkit\Support\Diagnostics;

use Concrete\Core\Page\Page;
use Concrete\Core\User\User;
use Concrete\Core\Http\Request;
use Concrete\Core\Permission\Checker;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Application\Application;
use Concrete\Core\Database\Connection\Connection;

class ConcreteDiagnosticsSource implements DiagnosticsSource
{
    /** Monolog ERROR and above. */
    private const MIN_LEVEL = 400;

    public function __construct(private readonly Application $app) {}

    public function site(): array
    {
        $site = $this->app->make('site')->getSite();

        return [
            'name' => (string) $site->getSiteName(),
            'url' => self::canonicalUrl($this->app),
            'mode' => (string) ($this->app->make('config')->get('concrete.security.production.mode') ?? 'unknown'),
        ];
    }

    /**
     * The site's configured canonical URL; never derived from the request host.
     */
    public static function canonicalUrl(Application $app): ?string
    {
        $url = trim((string) $app->make('site')->getSite()->getConfigRepository()->get('seo.canonical_url'));

        return $url !== '' ? $url : null;
    }

    public function page(int $cID, ?int $cvID): ?array
    {
        $page = Page::getByID($cID, $cvID ?? 'ACTIVE');
        if (!$page || $page->isError() || $page->isInTrash() || $page->isSystemPage()) {
            return null;
        }

        if (!(new Checker($page))->canViewPage()) {
            return null;
        }

        $version = $page->getVersionObject();

        return [
            'cID' => (int) $page->getCollectionID(),
            'cvID' => $version && !$version->isError() ? (int) $version->getVersionID() : null,
            'path' => (string) ($page->getCollectionPath() ?: '/'),
            'name' => (string) $page->getCollectionName(),
        ];
    }

    public function user(): ?array
    {
        $user = $this->app->make(User::class);
        if (!$user->isRegistered()) {
            return null;
        }

        return [
            'uID' => (int) $user->getUserID(),
            'username' => (string) $user->getUserName(),
            'groups' => array_values(array_map(static fn($g) => (string) $g->getGroupName(), $user->getUserGroupObjects())),
        ];
    }

    public function versions(): array
    {
        $packages = [];
        foreach ($this->app->make(PackageService::class)->getInstalledList() as $pkg) {
            $packages[(string) $pkg->getPackageHandle()] = (string) $pkg->getPackageVersion();
        }
        ksort($packages);

        return [
            'concrete' => (string) $this->app->make('config')->get('concrete.version'),
            'php' => PHP_VERSION,
            'packages' => $packages,
        ];
    }

    public function userAgent(): ?string
    {
        return $this->app->make(Request::class)->headers->get('User-Agent');
    }

    /**
     * Reads the core Logs table. Sites that log only to files return an empty list.
     */
    public function recentErrors(int $limit): array
    {
        $rows = $this->app->make(Connection::class)->fetchAllAssociative(
            'SELECT time, channel, message FROM Logs WHERE level >= ? ORDER BY logID DESC LIMIT ' . max(1, $limit),
            [self::MIN_LEVEL],
        );

        return array_map(static fn(array $row) => [
            'time' => date(DATE_ATOM, (int) $row['time']),
            'channel' => (string) $row['channel'],
            'message' => (string) $row['message'],
        ], $rows);
    }
}
