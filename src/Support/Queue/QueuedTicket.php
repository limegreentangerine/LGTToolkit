<?php

namespace LgtToolkit\Support\Queue;

final readonly class QueuedTicket
{
    public function __construct(
        public string $id,
        public string $localReference,
        public string $email,
        public ?string $name,
        public string $subject,
        public string $message,
        public ?string $pageUrl,
        public string $status,
        public int $attempts,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $nextAttemptAt,
        public ?\DateTimeImmutable $lastAttemptAt,
        public ?int $lastStatusCode,
        public ?string $lastError,
        public bool $possibleDuplicate,
        public bool $configError,
    ) {}

    public static function fromRow(array $row): self
    {
        $date = static fn($v) => $v !== null && $v !== '' ? new \DateTimeImmutable((string) $v) : null;

        return new self(
            (string) $row['id'],
            (string) $row['localReference'],
            (string) ($row['email'] ?? ''),
            $row['name'] ?? null,
            (string) $row['subject'],
            (string) ($row['message'] ?? ''),
            $row['pageUrl'] ?? null,
            (string) $row['status'],
            (int) $row['attempts'],
            $date($row['date_created']),
            $date($row['nextAttemptAt']),
            $date($row['lastAttemptAt'] ?? null),
            isset($row['lastStatusCode']) ? (int) $row['lastStatusCode'] : null,
            $row['lastError'] ?? null,
            (bool) $row['possibleDuplicate'],
            (bool) $row['configError'],
        );
    }
}
