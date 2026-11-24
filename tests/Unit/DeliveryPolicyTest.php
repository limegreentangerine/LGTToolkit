<?php

namespace LgtToolkit\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LgtToolkit\Support\Delivery\BackoffPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use LgtToolkit\Support\Delivery\DeliveryAction;
use LgtToolkit\Support\Delivery\DeliveryResult;
use LgtToolkit\Support\Delivery\OutcomeClassifier;

final class DeliveryPolicyTest extends TestCase
{
    public static function statusProvider(): array
    {
        return [
            '201' => [201, DeliveryAction::Delivered],
            '400' => [400, DeliveryAction::PermanentFailure],
            '401' => [401, DeliveryAction::ConfigError],
            '413' => [413, DeliveryAction::TooLarge],
            '429' => [429, DeliveryAction::RateLimited],
            '301' => [301, DeliveryAction::ConfigError],
            '302' => [302, DeliveryAction::ConfigError],
            '308' => [308, DeliveryAction::ConfigError],
            '403' => [403, DeliveryAction::ConfigError],
            '404' => [404, DeliveryAction::ConfigError],
            '405' => [405, DeliveryAction::ConfigError],
            '409' => [409, DeliveryAction::PermanentFailure],
            '422' => [422, DeliveryAction::PermanentFailure],
            '500' => [500, DeliveryAction::Retryable],
            '502' => [502, DeliveryAction::Retryable],
            '503' => [503, DeliveryAction::Retryable],
            '504 timeout' => [504, DeliveryAction::Retryable],
            '200 unexpected' => [200, DeliveryAction::Retryable],
        ];
    }

    #[DataProvider('statusProvider')]
    public function testClassify(int $status, DeliveryAction $expected): void
    {
        $this->assertSame($expected, OutcomeClassifier::classify($status));
    }

    public function testResultFlags(): void
    {
        $this->assertTrue((new DeliveryResult(DeliveryAction::Retryable, 500))->possibleDuplicate());
        $this->assertFalse((new DeliveryResult(DeliveryAction::ConfigError, 401))->possibleDuplicate());
        $this->assertTrue((new DeliveryResult(DeliveryAction::ConfigError, 401))->shouldRetry());
        $this->assertFalse((new DeliveryResult(DeliveryAction::PermanentFailure, 400))->shouldRetry());
        $this->assertTrue(DeliveryAction::RateLimited->stopsBatch());
        $this->assertFalse(DeliveryAction::Retryable->stopsBatch());
    }

    public function testBackoffSchedule(): void
    {
        $now = new \DateTimeImmutable('2026-11-24 10:00:00');
        $delays = array_map(
            fn(int $n) => BackoffPolicy::nextAttempt(DeliveryAction::Retryable, $n, $now)->getTimestamp() - $now->getTimestamp(),
            [1, 2, 3, 4, 5, 9],
        );

        $this->assertSame([300, 900, 1800, 3600, 3600, 3600], $delays);
        $this->assertSame(3600, BackoffPolicy::nextAttempt(DeliveryAction::ConfigError, 1, $now)->getTimestamp() - $now->getTimestamp());
    }

    public function testRateLimitUsesRetryAfterCappedAtAnHour(): void
    {
        $now = new \DateTimeImmutable('2026-11-24 10:00:00');
        $delay = fn(?int $after) => BackoffPolicy::nextAttempt(DeliveryAction::RateLimited, 1, $now, $after)->getTimestamp() - $now->getTimestamp();

        $this->assertSame(120, $delay(120));
        $this->assertSame(3600, $delay(99999));
        $this->assertSame(900, $delay(null));
    }

    public function testParseRetryAfter(): void
    {
        $now = new \DateTimeImmutable('Tue, 24 Nov 2026 10:00:00 GMT');

        $this->assertSame(120, BackoffPolicy::parseRetryAfter('120', $now));
        $this->assertSame(300, BackoffPolicy::parseRetryAfter('Tue, 24 Nov 2026 10:05:00 GMT', $now));
        $this->assertSame(0, BackoffPolicy::parseRetryAfter('Tue, 24 Nov 2026 09:00:00 GMT', $now));
        $this->assertNull(BackoffPolicy::parseRetryAfter('soon', $now));
        $this->assertNull(BackoffPolicy::parseRetryAfter(null, $now));
    }

    public function testExpiry(): void
    {
        $created = new \DateTimeImmutable('2026-11-20 10:00:00');

        $this->assertFalse(BackoffPolicy::isExpired($created, $created->modify('+72 hours')));
        $this->assertTrue(BackoffPolicy::isExpired($created, $created->modify('+72 hours +1 second')));
    }
}
