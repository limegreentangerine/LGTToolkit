<?php

namespace LgtToolkit\Support;

use Doctrine\DBAL\Connection;

/**
 * A record of every ticket submitted through the site (shown to the super user), kept for 12 months.
 * Separate from the retry queue, which only holds tickets still waiting to be delivered.
 */
class TicketLog
{
    private const DATE = 'Y-m-d H:i:s';
    public const TABLE = 'LgtSupportTickets';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_FAILED = 'failed';
    public const RETENTION = '-12 months';

    public function __construct(private readonly Connection $db) {}

    private static function uuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0F) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }

    public function record(
        string $localReference,
        string $category,
        string $email,
        ?string $name,
        ?int $uID,
        string $subject,
        string $message,
        ?string $pageUrl,
        string $status,
        ?int $portalTicket,
        ?string $portalReference,
        ?string $portalUrl,
        ?string $error,
        \DateTimeImmutable $now,
    ): void {
        $this->db->insert(self::TABLE, [
            'id' => self::uuid(),
            'date_created' => $now->format(self::DATE),
            'date_updated' => $now->format(self::DATE),
            'localReference' => $localReference,
            'category' => $category,
            'email' => mb_substr($email, 0, 255),
            'name' => $name !== null ? mb_substr($name, 0, 100) : null,
            'uID' => $uID,
            'subject' => mb_substr($subject, 0, 200),
            'message' => $message,
            'pageUrl' => $pageUrl,
            'status' => $status,
            'portalTicket' => $portalTicket,
            'portalReference' => $portalReference,
            'portalUrl' => $portalUrl,
            'deliveredAt' => $status === self::STATUS_DELIVERED ? $now->format(self::DATE) : null,
            'lastError' => $error !== null ? mb_substr($error, 0, 2000) : null,
        ]);
    }

    public function markDelivered(string $localReference, ?int $portalTicket, ?string $portalReference, ?string $portalUrl, \DateTimeImmutable $now): void
    {
        $this->db->update(self::TABLE, [
            'status' => self::STATUS_DELIVERED,
            'portalTicket' => $portalTicket,
            'portalReference' => $portalReference,
            'portalUrl' => $portalUrl,
            'deliveredAt' => $now->format(self::DATE),
            'lastError' => null,
            'date_updated' => $now->format(self::DATE),
        ], ['localReference' => $localReference]);
    }

    public function markFailed(string $localReference, ?string $error, \DateTimeImmutable $now): void
    {
        $this->db->update(self::TABLE, [
            'status' => self::STATUS_FAILED,
            'lastError' => $error !== null ? mb_substr($error, 0, 2000) : null,
            'date_updated' => $now->format(self::DATE),
        ], ['localReference' => $localReference]);
    }

    /**
     * Deletes records older than 12 months.
     */
    public function purge(\DateTimeImmutable $now): int
    {
        return $this->db->executeStatement(
            'DELETE FROM ' . self::TABLE . ' WHERE date_created < ?',
            [$now->modify(self::RETENTION)->format(self::DATE)],
        );
    }


}
