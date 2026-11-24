<?php

namespace LgtToolkit\Support\Queue;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use LgtToolkit\Support\Attachment;
use LgtToolkit\Support\Delivery\BackoffPolicy;
use LgtToolkit\Support\Delivery\DeliveryAction;
use LgtToolkit\Support\Delivery\DeliveryResult;

/**
 * The retry queue (spec §9). Plain DBAL so claims are atomic (affected-row count) and no ORM state goes stale.
 * Invariant: once a 201 is recorded the row is 'delivered' and can never be claimed again.
 */
class QueueService
{
    private const DATE = 'Y-m-d H:i:s';
    private const LIST_COLUMNS = 'id, localReference, subject, status, attempts, date_created, nextAttemptAt, lastAttemptAt, lastStatusCode, lastError, possibleDuplicate, configError';
    public const STATUS_PENDING = 'pending';
    public const STATUS_DELIVERING = 'delivering';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const TABLE = 'LgtSupportTicketQueue';
    public const ATTACHMENTS = 'LgtSupportTicketQueueAttachments';
    public const LOCK_SECONDS = 120;
    public const STALE_WARNING_SECONDS = 1800;

    public function __construct(
        private readonly Connection $db,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    private function nextAttempt(DeliveryResult $result, int $failures, \DateTimeImmutable $now): \DateTimeImmutable
    {
        return BackoffPolicy::nextAttempt($result->action, $failures, $now, $result->retryAfter);
    }

    /**
     * Attachments are deleted explicitly too, so nothing is left if the FK cascade is missing.
     */
    private function deleteWithAttachments(string $id): void
    {
        $this->db->delete(self::ATTACHMENTS, ['queueItemID' => $id]);
        $this->db->delete(self::TABLE, ['id' => $id]);
    }

    private static function uuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0F) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }

    /**
     * Saves a ticket whose immediate send failed with a retryable or config outcome.
     *
     * @param list<Attachment> $attachments
     */
    public function enqueue(
        string $localReference,
        string $email,
        ?string $name,
        string $subject,
        string $message,
        ?string $pageUrl,
        ?int $uID,
        array $attachments,
        DeliveryResult $result,
        \DateTimeImmutable $now,
    ): string {
        $id = self::uuid();
        $this->db->insert(self::TABLE, [
            'id' => $id,
            'date_created' => $now->format(self::DATE),
            'date_updated' => $now->format(self::DATE),
            'localReference' => $localReference,
            'email' => mb_substr($email, 0, 255),
            'name' => $name !== null ? mb_substr($name, 0, 100) : null,
            'subject' => mb_substr($subject, 0, 200),
            'message' => $message,
            'pageUrl' => $pageUrl,
            'uID' => $uID,
            'status' => self::STATUS_PENDING,
            'attempts' => 1,
            'nextAttemptAt' => $this->nextAttempt($result, 1, $now)->format(self::DATE),
            'lastAttemptAt' => $now->format(self::DATE),
            'lastStatusCode' => $result->statusCode ?: null,
            'lastError' => $result->error,
            'possibleDuplicate' => (int) $result->possibleDuplicate(),
            'configError' => (int) ($result->action === DeliveryAction::ConfigError),
        ]);

        // One row per file; a file the DB refuses (e.g. max_allowed_packet) is dropped rather than losing the ticket.
        $dropped = [];
        foreach ($attachments as $attachment) {
            try {
                $this->db->insert(self::ATTACHMENTS, [
                    'id' => self::uuid(),
                    'queueItemID' => $id,
                    'filename' => mb_substr($attachment->filename, 0, 100),
                    'mime' => $attachment->mime,
                    'size' => $attachment->size,
                    'content' => $attachment->contents(),
                ], ['content' => ParameterType::LARGE_OBJECT]);
            } catch (\Throwable $e) {
                $dropped[] = sprintf('%s (%s KB)', $attachment->filename, number_format($attachment->size / 1024));
                $this->logger->warning('Support ticket attachment could not be queued.', ['reference' => $localReference, 'error' => $e->getMessage()]);
            }
        }

        if ($dropped !== []) {
            $this->db->update(self::TABLE, [
                'message' => $message . "\n--- Attachments not sent ---\nThese files could not be saved for retry: " . implode(', ', $dropped) . "\n",
            ], ['id' => $id]);
        }

        return $id;
    }

    /**
     * IDs of pending items that are due, oldest first. Nothing is claimed; call claim() just before sending each.
     *
     * @return list<string>
     */
    public function due(int $limit, \DateTimeImmutable $now): array
    {
        return array_map('strval', $this->db->fetchFirstColumn(
            'SELECT id FROM ' . self::TABLE . ' WHERE status = ? AND nextAttemptAt <= ? ORDER BY nextAttemptAt LIMIT ' . max(1, $limit),
            [self::STATUS_PENDING, $now->format(self::DATE)],
        ));
    }

    /**
     * Atomically marks a due item as being sent. False means another run already has it (or it is no longer due).
     */
    public function claim(string $id, \DateTimeImmutable $now): bool
    {
        return $this->db->executeStatement(
            'UPDATE ' . self::TABLE . ' SET status = ?, lockedUntil = ?, attempts = attempts + 1, lastAttemptAt = ?, date_updated = ?'
            . ' WHERE id = ? AND status = ? AND nextAttemptAt <= ?',
            [
                self::STATUS_DELIVERING,
                $now->modify('+' . self::LOCK_SECONDS . ' seconds')->format(self::DATE),
                $now->format(self::DATE),
                $now->format(self::DATE),
                $id,
                self::STATUS_PENDING,
                $now->format(self::DATE),
            ],
        ) === 1;
    }

    public function find(string $id): ?QueuedTicket
    {
        $row = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE . ' WHERE id = ?', [$id]);

        return $row ? QueuedTicket::fromRow($row) : null;
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(string $id): array
    {
        $rows = $this->db->fetchAllAssociative('SELECT filename, mime, size, content FROM ' . self::ATTACHMENTS . ' WHERE queueItemID = ?', [$id]);

        return array_map(static fn(array $r) => new Attachment(
            (string) $r['filename'],
            (string) $r['mime'],
            (int) $r['size'],
            null,
            is_resource($r['content']) ? (string) stream_get_contents($r['content']) : (string) $r['content'],
        ), $rows);
    }

    /**
     * Records the 201 first, then deletes. If the delete fails the row stays 'delivered' and is never re-sent.
     */
    public function markDelivered(string $id, ?int $portalTicket, \DateTimeImmutable $now): void
    {
        $this->db->update(self::TABLE, [
            'status' => self::STATUS_DELIVERED,
            'portalTicket' => $portalTicket,
            'lockedUntil' => null,
            'date_updated' => $now->format(self::DATE),
        ], ['id' => $id]);

        try {
            $this->deleteWithAttachments($id);
        } catch (\Throwable $e) {
            $this->logger->warning('Delivered support ticket could not be removed from the queue; it will be purged later.', ['error' => $e->getMessage()]);
        }
    }

    public function reschedule(QueuedTicket $item, DeliveryResult $result, \DateTimeImmutable $now): void
    {
        $this->db->update(self::TABLE, [
            'status' => self::STATUS_PENDING,
            'lockedUntil' => null,
            'nextAttemptAt' => $this->nextAttempt($result, $item->attempts, $now)->format(self::DATE),
            'lastStatusCode' => $result->statusCode ?: null,
            'lastError' => $result->error,
            'possibleDuplicate' => (int) ($item->possibleDuplicate || $result->possibleDuplicate()),
            'configError' => (int) ($result->action === DeliveryAction::ConfigError),
            'date_updated' => $now->format(self::DATE),
        ], ['id' => $item->id]);
    }

    public function markFailed(string $id, ?int $statusCode, ?string $reason, \DateTimeImmutable $now): void
    {
        $this->db->update(self::TABLE, [
            'status' => self::STATUS_FAILED,
            'lockedUntil' => null,
            'lastStatusCode' => $statusCode ?: null,
            'lastError' => $reason !== null ? mb_substr($reason, 0, 2000) : null,
            'date_updated' => $now->format(self::DATE),
        ], ['id' => $id]);
    }

    /**
     * Items whose run died mid-send revert to pending; the send may have reached the portal.
     */
    public function releaseStale(\DateTimeImmutable $now): int
    {
        return $this->db->executeStatement(
            'UPDATE ' . self::TABLE . ' SET status = ?, lockedUntil = NULL, possibleDuplicate = 1 WHERE status = ? AND lockedUntil < ?',
            [self::STATUS_PENDING, self::STATUS_DELIVERING, $now->format(self::DATE)],
        );
    }

    /**
     * Pending items older than 72 h fail. Items last held back by the portal's rate limit (429) are exempt.
     */
    public function expire(\DateTimeImmutable $now): int
    {
        return $this->db->executeStatement(
            'UPDATE ' . self::TABLE . ' SET status = ?, date_updated = ? WHERE status = ? AND date_created < ? AND (lastStatusCode IS NULL OR lastStatusCode <> 429)',
            [
                self::STATUS_FAILED,
                $now->format(self::DATE),
                self::STATUS_PENDING,
                $now->modify('-' . BackoffPolicy::EXPIRY_SECONDS . ' seconds')->format(self::DATE),
            ],
        );
    }

    /**
     * Removes delivered leftovers, and failed items after 30 days.
     */
    public function purge(\DateTimeImmutable $now): int
    {
        $ids = $this->db->fetchFirstColumn(
            'SELECT id FROM ' . self::TABLE . ' WHERE status = ? OR (status = ? AND date_updated < ?)',
            [
                self::STATUS_DELIVERED,
                self::STATUS_FAILED,
                $now->modify('-' . BackoffPolicy::FAILED_RETENTION_SECONDS . ' seconds')->format(self::DATE),
            ],
        );

        foreach ($ids as $id) {
            $this->deleteWithAttachments((string) $id);
        }

        return count($ids);
    }

    /**
     * Deletes a failed item on request from the dashboard.
     */
    public function discard(string $id): bool
    {
        $status = $this->db->fetchOne('SELECT status FROM ' . self::TABLE . ' WHERE id = ?', [$id]);
        if ($status !== self::STATUS_FAILED) {
            return false;
        }

        $this->deleteWithAttachments($id);

        return true;
    }

    /**
     * Queued and failed items for the dashboard (no message bodies or attachment bytes).
     *
     * @return list<QueuedTicket>
     */
    public function listForDashboard(int $limit = 50): array
    {
        $rows = $this->db->fetchAllAssociative(
            'SELECT ' . self::LIST_COLUMNS . ' FROM ' . self::TABLE . ' WHERE status <> ? ORDER BY date_created DESC LIMIT ' . max(1, $limit),
            [self::STATUS_DELIVERED],
        );

        return array_map(QueuedTicket::fromRow(...), $rows);
    }

    /**
     * The earliest pending item more than 30 minutes past due: a sign the cron job isn't running.
     */
    public function oldestOverdue(\DateTimeImmutable $now): ?QueuedTicket
    {
        $row = $this->db->fetchAssociative(
            'SELECT ' . self::LIST_COLUMNS . ' FROM ' . self::TABLE . ' WHERE status = ? AND nextAttemptAt < ? ORDER BY nextAttemptAt LIMIT 1',
            [self::STATUS_PENDING, $now->modify('-' . self::STALE_WARNING_SECONDS . ' seconds')->format(self::DATE)],
        );

        return $row ? QueuedTicket::fromRow($row) : null;
    }

    /**
     * @return array{pending: int, failed: int, configError: bool, lastError: ?QueuedTicket}
     */
    public function stats(): array
    {
        $counts = [];
        foreach ($this->db->fetchAllAssociative('SELECT status, COUNT(*) AS n FROM ' . self::TABLE . ' GROUP BY status') as $row) {
            $counts[$row['status']] = (int) $row['n'];
        }

        $last = $this->db->fetchAssociative(
            'SELECT ' . self::LIST_COLUMNS . ' FROM ' . self::TABLE . ' WHERE lastError IS NOT NULL AND status <> ? ORDER BY lastAttemptAt DESC LIMIT 1',
            [self::STATUS_DELIVERED],
        );

        return [
            'pending' => ($counts[self::STATUS_PENDING] ?? 0) + ($counts[self::STATUS_DELIVERING] ?? 0),
            'failed' => $counts[self::STATUS_FAILED] ?? 0,
            'configError' => (bool) $this->db->fetchOne('SELECT COUNT(*) FROM ' . self::TABLE . ' WHERE configError = 1 AND status = ?', [self::STATUS_PENDING]),
            'lastError' => $last ? QueuedTicket::fromRow($last) : null,
        ];
    }
}
