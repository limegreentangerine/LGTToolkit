<?php

namespace LgtToolkit\Tests\Unit;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use LgtToolkit\Support\Attachment;
use LgtToolkit\Support\Queue\QueueService;
use LgtToolkit\Support\Delivery\DeliveryAction;
use LgtToolkit\Support\Delivery\DeliveryResult;

/**
 * Runs the real SQL against in-memory SQLite (schema mirrors the two entities).
 */
final class QueueServiceTest extends TestCase
{
    use QueueSchema;

    private Connection $db;

    private QueueService $queue;

    private \DateTimeImmutable $now;

    private function enqueue(string $ref = 'Q-20261124-AAAA', ?DeliveryResult $result = null, array $attachments = [], ?\DateTimeImmutable $at = null): string
    {
        return $this->queue->enqueue(
            $ref,
            'a@example.com',
            'Al',
            'Broken: form',
            'Message body',
            null,
            7,
            $attachments,
            $result ?? new DeliveryResult(DeliveryAction::Retryable, 500, error: 'HTTP 500'),
            $at ?? $this->now,
        );
    }

    private function rows(string $table = QueueService::TABLE): int
    {
        return (int) $this->db->fetchOne("SELECT COUNT(*) FROM $table");
    }

    public function testEnqueueStoresStateAndAttachments(): void
    {
        $id = $this->enqueue(attachments: [new Attachment('a.png', 'image/png', 4, null, "\x89PNG")]);
        $item = $this->queue->find($id);

        $this->assertSame('pending', $item->status);
        $this->assertSame(1, $item->attempts);
        $this->assertTrue($item->possibleDuplicate);
        $this->assertSame('2026-11-24 10:05:00', $item->nextAttemptAt->format('Y-m-d H:i:s'));
        $this->assertSame("\x89PNG", $this->queue->attachments($id)[0]->contents());

        $config = $this->queue->find($this->enqueue('Q-2', new DeliveryResult(DeliveryAction::ConfigError, 401)));
        $this->assertTrue($config->configError);
        $this->assertFalse($config->possibleDuplicate);
        $this->assertSame('2026-11-24 11:00:00', $config->nextAttemptAt->format('Y-m-d H:i:s'));
    }

    public function testClaimIsAtomicAndRespectsSchedule(): void
    {
        $id = $this->enqueue();

        $this->assertSame([], $this->queue->due(10, $this->now), 'not due yet');
        $this->assertFalse($this->queue->claim($id, $this->now), 'cannot claim before due');

        $later = $this->now->modify('+5 minutes');
        $this->assertTrue($this->queue->claim($id, $later));
        $this->assertFalse($this->queue->claim($id, $later), 'second run loses the race');
        $this->assertSame([], $this->queue->due(10, $later));
        $this->assertSame(2, $this->queue->find($id)->attempts);
    }

    public function testClaimDueLimit(): void
    {
        foreach (range(1, 12) as $i) {
            $this->enqueue("Q-$i");
        }

        $this->assertCount(10, $this->queue->due(10, $this->now->modify('+1 hour')));
    }

    public function testDeliveredItemIsDeletedWithAttachments(): void
    {
        $id = $this->enqueue(attachments: [new Attachment('a.pdf', 'application/pdf', 1, null, 'x')]);
        $this->queue->claim($id, $this->now->modify('+5 minutes'));

        $this->queue->markDelivered($id, 1234, $this->now);

        $this->assertSame(0, $this->rows());
        $this->assertSame(0, $this->rows(QueueService::ATTACHMENTS));
    }

    public function testDeliveredItemWhoseDeleteFailsIsNeverReclaimed(): void
    {
        $id = $this->enqueue();
        $this->queue->claim($id, $this->now->modify('+5 minutes'));
        $this->db->executeStatement("CREATE TRIGGER no_delete BEFORE DELETE ON LgtSupportTicketQueue BEGIN SELECT RAISE(ABORT, 'locked'); END");

        $this->queue->markDelivered($id, 1234, $this->now);

        $this->assertSame('delivered', $this->queue->find($id)->status);
        $far = $this->now->modify('+1 day');
        $this->assertSame(0, $this->queue->releaseStale($far));
        $this->assertSame([], $this->queue->due(10, $far));
        $this->assertFalse($this->queue->claim($id, $far));

        $this->db->executeStatement('DROP TRIGGER no_delete');
        $this->assertSame(1, $this->queue->purge($far));
        $this->assertSame(0, $this->rows());
    }

    public function testStaleDeliveringRevertsToPendingAsPossibleDuplicate(): void
    {
        $id = $this->enqueue('Q-1', new DeliveryResult(DeliveryAction::ConfigError, 401));
        $claimAt = $this->now->modify('+1 hour');
        $this->queue->claim($id, $claimAt);

        $this->assertSame(0, $this->queue->releaseStale($claimAt->modify('+1 minute')), 'lock still held');
        $this->assertSame(1, $this->queue->releaseStale($claimAt->modify('+3 minutes')));

        $item = $this->queue->find($id);
        $this->assertSame('pending', $item->status);
        $this->assertTrue($item->possibleDuplicate);
    }

    public function testRescheduleUsesBackoffAndKeepsDuplicateFlag(): void
    {
        $id = $this->enqueue();
        $at = $this->now->modify('+5 minutes');
        $this->queue->claim($id, $at);
        $item = $this->queue->find($id);

        $this->queue->reschedule($item, new DeliveryResult(DeliveryAction::RateLimited, 429, retryAfter: 60), $at);

        $item = $this->queue->find($id);
        $this->assertSame('pending', $item->status);
        $this->assertSame(429, $item->lastStatusCode);
        $this->assertTrue($item->possibleDuplicate, 'flag is sticky once set');
        $this->assertSame('2026-11-24 10:06:00', $item->nextAttemptAt->format('Y-m-d H:i:s'));
    }

    public function testExpiryAfter72HoursExceptRateLimited(): void
    {
        $old = $this->now->modify('-73 hours');
        $expiring = $this->enqueue('Q-1', at: $old);
        $limited = $this->enqueue('Q-2', new DeliveryResult(DeliveryAction::RateLimited, 429), at: $old);
        $fresh = $this->enqueue('Q-3');

        $this->assertSame(1, $this->queue->expire($this->now));
        $this->assertSame('failed', $this->queue->find($expiring)->status);
        $this->assertSame('pending', $this->queue->find($limited)->status);
        $this->assertSame('pending', $this->queue->find($fresh)->status);
    }

    public function testPurgeRemovesOldFailedOnly(): void
    {
        $old = $this->enqueue('Q-1');
        $recent = $this->enqueue('Q-2');
        $this->queue->markFailed($old, 400, 'bad', $this->now->modify('-31 days'));
        $this->queue->markFailed($recent, 400, 'bad', $this->now);

        $this->assertSame(1, $this->queue->purge($this->now));
        $this->assertNull($this->queue->find($old));
        $this->assertNotNull($this->queue->find($recent));
    }

    public function testDiscardOnlyFailed(): void
    {
        $pending = $this->enqueue('Q-1');
        $failed = $this->enqueue('Q-2');
        $this->queue->markFailed($failed, 400, 'bad', $this->now);

        $this->assertFalse($this->queue->discard($pending));
        $this->assertTrue($this->queue->discard($failed));
        $this->assertSame(1, $this->rows());
    }

    public function testDashboardQueries(): void
    {
        $this->enqueue('Q-1', new DeliveryResult(DeliveryAction::ConfigError, 401, error: 'Invalid key'));
        $failed = $this->enqueue('Q-2');
        $this->queue->markFailed($failed, 400, 'bad', $this->now);

        $this->assertCount(2, $this->queue->listForDashboard());
        $this->assertNull($this->queue->oldestOverdue($this->now->modify('+1 hour')), 'only 0 min overdue');
        $this->assertSame('Q-1', $this->queue->oldestOverdue($this->now->modify('+2 hours'))->localReference);

        $stats = $this->queue->stats();
        $this->assertSame(1, $stats['pending']);
        $this->assertSame(1, $stats['failed']);
        $this->assertTrue($stats['configError']);
        $this->assertNotNull($stats['lastError']);
    }

    protected function setUp(): void
    {
        $this->db = $this->queueDatabase();

        $this->queue = new QueueService($this->db);
        $this->now = new \DateTimeImmutable('2026-11-24 10:00:00');
    }
}
