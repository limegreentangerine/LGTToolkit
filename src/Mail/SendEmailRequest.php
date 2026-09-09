<?php

namespace LgtToolkit\Mail;

use Package;

final readonly class SendEmailRequest
{
    public function __construct(
        public string $template,
        public array $args,
        public ?string $body_template,
        public ?string $template_loop,
        public ?Package $pkg,
        public ?boolean $testing,
    ) {}
}
