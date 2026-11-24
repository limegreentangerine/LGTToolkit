<?php

namespace LgtToolkit\Support;

/**
 * Per-user submission limit in a fixed one-hour window. Best effort: the portal also caps each site at 30/hour.
 * Storage is injected so the logic is testable; the service provider wires it to Concrete's cache.
 */
class UserRateLimiter
{
    public const WINDOW_SECONDS = 3600;

    /**
     * @param \Closure(string): int            $get read the counter (0 when missing)
     * @param \Closure(string, int, int): void $put write the counter with a TTL in seconds
     */
    public function __construct(
        private readonly \Closure $get,
        private readonly \Closure $put,
        private readonly int $limit = SupportDefaults::USER_RATE_LIMIT_PER_HOUR,
    ) {}

    private function key(int $uID, \DateTimeImmutable $now): string
    {
        return sprintf('lgt_support/rate/%d/%d', $uID, intdiv($now->getTimestamp(), self::WINDOW_SECONDS));
    }

    public function isAllowed(int $uID, \DateTimeImmutable $now): bool
    {
        return ($this->get)($this->key($uID, $now)) < $this->limit;
    }

    public function hit(int $uID, \DateTimeImmutable $now): void
    {
        $key = $this->key($uID, $now);
        ($this->put)($key, ($this->get)($key) + 1, self::WINDOW_SECONDS);
    }
}
