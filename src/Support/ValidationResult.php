<?php

namespace LgtToolkit\Support;

final readonly class ValidationResult
{
    /**
     * @param array<string, string> $errors field name => message
     */
    public function __construct(
        public ?TicketSubmission $submission,
        public array $errors,
    ) {}

    public function isValid(): bool
    {
        return $this->errors === [] && $this->submission !== null;
    }
}
