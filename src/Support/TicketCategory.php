<?php

namespace LgtToolkit\Support;

/**
 * What a support ticket is about. Priority is set portal-side, so there is none here.
 */
enum TicketCategory: string
{
    public function label(): string
    {
        return match ($this) {
            self::Broken => t("Something's broken"),
            self::ContentHelp => t('Content help'),
            self::ChangeRequest => t('Change request'),
        };
    }

    public function help(): string
    {
        return match ($this) {
            self::Broken => t("A feature, form or page isn't working as expected."),
            self::ContentHelp => t('You need help editing or publishing content.'),
            self::ChangeRequest => t("You'd like something new or changed on the site."),
        };
    }
    case Broken = 'broken';
    case ContentHelp = 'content_help';
    case ChangeRequest = 'change_request';
}
