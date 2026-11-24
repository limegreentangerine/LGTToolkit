<?php

namespace LgtToolkit\Support;

/**
 * Builds the portal API body (spec §8). The message is plain text; the portal keeps line breaks.
 */
class TicketPayloadBuilder
{
    /**
     * Room kept free so a retry's delivery note always fits under MAX_MESSAGE.
     */
    private const NOTE_RESERVE = 1000;
    public const MAX_SUBJECT = 150;
    public const MAX_MESSAGE = 20000;

    /**
     * Valid UTF-8 with no NUL bytes; line breaks kept.
     */
    private function clean(string $text): string
    {
        return str_replace("\0", '', mb_scrub($text, 'UTF-8'));
    }

    /**
     * Valid UTF-8 with every control character removed (no header injection in subject or name).
     */
    private function singleLine(string $text): string
    {
        return preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $this->clean($text)) ?? '';
    }

    public function subject(TicketCategory $category, string $summary, ?string $prefix = null): string
    {
        $subject = ($prefix !== null ? $prefix . ' ' : '') . $category->label() . ': ' . $summary;

        return mb_substr(trim($this->singleLine($subject)), 0, self::MAX_SUBJECT);
    }

    /**
     * The message as first submitted (and as stored in the queue, before any delivery note).
     */
    public function message(
        TicketSubmission $submission,
        string $localReference,
        \DateTimeInterface $submittedAt,
        string $diagnostics,
        string $recentErrors,
    ): string {
        $head = $this->clean($submission->description) . "\n\n---\n"
            . 'Category: ' . $submission->category->label() . "\n"
            . 'Page: ' . ($submission->pageUrl ?? 'not specified') . "\n"
            . 'Contact phone: ' . ($submission->contactPhone ?? 'not given') . "\n"
            . 'Submitted: ' . $submittedAt->format(DATE_ATOM) . "\n"
            . 'Local reference: ' . $localReference . "\n";

        $tail = "\n--- Diagnostics ---\n" . $this->clean($diagnostics) . "\n"
            . "\n--- Recent errors (redacted) ---\n" . $this->clean($recentErrors) . "\n";

        // Diagnostics give way first; the description is never cut.
        $room = self::MAX_MESSAGE - self::NOTE_RESERVE - mb_strlen($head);
        if (mb_strlen($tail) > $room) {
            $tail = mb_substr($tail, 0, max(0, $room - 20)) . "\n[truncated]\n";
        }

        return $head . $tail;
    }

    /**
     * The footer added to every retried ticket so the helpdesk can spot and merge duplicates.
     */
    public function deliveryNote(
        string $message,
        string $localReference,
        \DateTimeInterface $now,
        int $attempt,
        \DateTimeInterface $submittedAt,
        bool $possibleDuplicate,
    ): string {
        $note = "\n--- Delivery note ---\n"
            . sprintf(
                'Delivered by retry queue at %s (attempt %d). Originally submitted %s.',
                $now->format(DATE_ATOM),
                $attempt,
                $submittedAt->format(DATE_ATOM),
            ) . "\n";

        if ($possibleDuplicate) {
            $note .= sprintf(
                'An earlier attempt may have reached the helpdesk; if a ticket with local reference %s already exists, this is a duplicate.',
                $localReference,
            ) . "\n";
        }

        return mb_substr($message . $note, 0, self::MAX_MESSAGE);
    }

    /**
     * @param list<Attachment> $attachments
     *
     * @return array<string, mixed>
     */
    public function payload(
        string $email,
        ?string $name,
        string $subject,
        string $message,
        ?string $pageUrl,
        array $attachments = [],
    ): array {
        $payload = [
            'email' => $email,
            'subject' => $this->singleLine($subject),
            'message' => $this->clean($message),
        ];

        if ($name !== null && $name !== '') {
            $payload['name'] = $this->singleLine($name);
        }

        if ($pageUrl !== null && $pageUrl !== '') {
            $payload['page_url'] = $pageUrl;
        }

        if ($attachments !== []) {
            $payload['attachments'] = array_map(fn(Attachment $a) => [
                'filename' => self::safeFilename($a->filename),
                'content_base64' => base64_encode($a->contents()),
            ], $attachments);
        }

        return $payload;
    }

    /**
     * Encodes the payload, throwing \JsonException rather than ever producing an empty body.
     */
    public function encode(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public static function safeFilename(string $filename): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?? '';
        $safe = ltrim($safe, '.');

        return substr($safe !== '' ? $safe : 'attachment', -100);
    }
}
