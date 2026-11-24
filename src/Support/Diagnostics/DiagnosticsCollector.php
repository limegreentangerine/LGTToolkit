<?php

namespace LgtToolkit\Support\Diagnostics;

use LgtToolkit\Support\Redactor;
use LgtToolkit\Support\SupportDefaults;

/**
 * Gathers the context attached to every ticket. Deliberately an allow-list: never config, env, cookies,
 * session, request bodies or the API key. A failing source drops its section; it never blocks a ticket.
 */
class DiagnosticsCollector
{
    public const ERROR_LIMIT = 15;
    public const USER_AGENT_MAX = 512;

    public function __construct(
        private readonly DiagnosticsSource $source,
        private readonly bool $includeErrorLog = SupportDefaults::INCLUDE_ERROR_LOG,
    ) {}

    private function recentErrors(): string
    {
        $entries = $this->attempt(fn() => $this->source->recentErrors(self::ERROR_LIMIT));
        if ($entries === null) {
            return 'unavailable';
        }

        if ($entries === []) {
            return 'none';
        }

        return implode("\n", array_map(
            static fn(array $e) => Redactor::redact(sprintf('[%s] %s: %s', $e['time'], $e['channel'], $e['message'])),
            array_slice($entries, 0, self::ERROR_LIMIT),
        ));
    }

    /**
     * @template T
     *
     * @param callable(): T $fn
     *
     * @return ?T
     */
    private function attempt(callable $fn): mixed
    {
        try {
            return $fn();
        } catch (\Throwable) {
            return null;
        }
    }

    public function collect(?int $pageID = null, ?int $pageVersionID = null): Diagnostics
    {
        $sections = [];

        $site = $this->attempt(fn() => $this->source->site());
        if ($site !== null) {
            $sections['Site'] = [
                'Name' => $site['name'],
                'Canonical URL' => $site['url'] ?? 'not set',
                'Environment' => $site['mode'],
            ];
        }

        $page = $pageID !== null ? $this->attempt(fn() => $this->source->page($pageID, $pageVersionID)) : null;
        if ($page !== null) {
            $sections['Page'] = array_filter([
                'cID' => (string) $page['cID'],
                'cvID' => $page['cvID'] !== null ? (string) $page['cvID'] : null,
                'Path' => $page['path'],
                'Name' => $page['name'],
            ], static fn($v) => $v !== null);
        }

        $user = $this->attempt(fn() => $this->source->user());
        if ($user !== null) {
            $sections['User'] = [
                'uID' => (string) $user['uID'],
                'Username' => $user['username'],
                'Groups' => $user['groups'] !== [] ? implode(', ', $user['groups']) : 'none',
            ];
        }

        $versions = $this->attempt(fn() => $this->source->versions());
        if ($versions !== null) {
            $packages = [];
            foreach ($versions['packages'] as $handle => $version) {
                $packages[] = $handle . ' ' . $version;
            }
            $sections['Versions'] = [
                'Concrete' => $versions['concrete'],
                'PHP' => $versions['php'],
                'Packages' => $packages !== [] ? implode(', ', $packages) : 'none',
            ];
        }

        $agent = $this->attempt(fn() => $this->source->userAgent());
        if ($agent !== null && $agent !== '') {
            $sections['Client'] = ['User-Agent' => mb_substr(mb_scrub($agent, 'UTF-8'), 0, self::USER_AGENT_MAX)];
        }

        return new Diagnostics($sections, $this->includeErrorLog ? $this->recentErrors() : 'not included');
    }
}
