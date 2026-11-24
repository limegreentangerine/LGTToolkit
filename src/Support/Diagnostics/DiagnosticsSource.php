<?php

namespace LgtToolkit\Support\Diagnostics;

/**
 * Where diagnostics come from. The Concrete implementation is thin; the collector holds the rules.
 */
interface DiagnosticsSource
{
    /**
     * @return array{name: string, url: ?string, mode: string}
     */
    public function site(): array;

    /**
     * The page, or null when it doesn't exist, is trashed/system, or the current user can't view it.
     *
     * @return ?array{cID: int, cvID: ?int, path: string, name: string}
     */
    public function page(int $cID, ?int $cvID): ?array;

    /**
     * @return ?array{uID: int, username: string, groups: list<string>}
     */
    public function user(): ?array;

    /**
     * @return array{concrete: string, php: string, packages: array<string, string>}
     */
    public function versions(): array;

    public function userAgent(): ?string;

    /**
     * Newest first, raw (the collector redacts).
     *
     * @return list<array{time: string, channel: string, message: string}>
     */
    public function recentErrors(int $limit): array;
}
