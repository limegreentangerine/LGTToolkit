<?php

namespace LgtToolkit\Support;

final readonly class TicketSubmission
{
    /**
     * @param list<Attachment> $attachments
     */
    public function __construct(
        public TicketCategory $category,
        public string $summary,
        public string $description,
        public string $email,
        public ?string $name = null,
        public ?string $contactPhone = null,
        public ?int $pageID = null,
        public ?int $pageVersionID = null,
        public ?string $pageUrl = null,
        public array $attachments = [],
    ) {}
}
