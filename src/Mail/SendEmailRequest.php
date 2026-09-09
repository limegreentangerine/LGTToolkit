<?php

namespace LgtToolkit\Mail;

use Package;

/**
 * Represents the payload needed to render and send a package email.
 */
final readonly class SendEmailRequest
{
    /**
     * Create a new email request payload.
     *
     * @param string       $template      The template name to render.
     * @param array        $args          The replacement variables used in the email template.
     * @param string|null  $body_template The base body template name.
     * @param string|null  $template_loop The optional loop template name.
     * @param Package|null $pkg           The package providing the email templates.
     * @param bool|null    $testing       Whether the email should be sent in test mode.
     */
    public function __construct(
        public string $template,
        public array $args,
        public ?string $body_template,
        public ?string $template_loop,
        public ?Package $pkg,
        public ?bool $testing,
    ) {}
}
