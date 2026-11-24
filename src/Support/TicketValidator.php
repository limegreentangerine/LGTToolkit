<?php

namespace LgtToolkit\Support;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Validates the support form. Limits are stricter than the portal's (5 files, 10 MB each, 20 MB total)
 * because it is undocumented whether the portal counts raw or base64 size, and to bound memory.
 */
class TicketValidator
{
    public const MAX_FILES = 5;
    public const MAX_FILE_BYTES = 7 * 1024 * 1024;
    public const MAX_TOTAL_BYTES = 12 * 1024 * 1024;
    public const ALLOWED_MIME = ['image/png', 'image/jpeg', 'image/webp', 'application/pdf'];

    /**
     * @param ?string $canonicalUrl the site's canonical URL from config, never HTTP_HOST
     */
    public function __construct(private readonly ?string $canonicalUrl) {}

    /**
     * @param array<?UploadedFile>  $files
     * @param array<string, string> $errors
     *
     * @return list<Attachment>
     */
    private function validateFiles(array $files, array &$errors): array
    {
        $files = array_values(array_filter(
            $files,
            static fn($file) => $file instanceof UploadedFile && $file->getError() !== UPLOAD_ERR_NO_FILE,
        ));

        if ($files === []) {
            return [];
        }

        if (count($files) > self::MAX_FILES) {
            $errors['attachments'] = t('You can attach up to %s files.', self::MAX_FILES);

            return [];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $attachments = [];
        $total = 0;

        foreach ($files as $file) {
            $name = $file->getClientOriginalName();

            if ($file->getError() === UPLOAD_ERR_INI_SIZE || $file->getError() === UPLOAD_ERR_FORM_SIZE) {
                $errors['attachments'] = t('%s is too large.', $name);

                return [];
            }

            if ($file->getError() !== UPLOAD_ERR_OK || !is_file($file->getPathname())) {
                $errors['attachments'] = t('%s could not be uploaded. Please try again.', $name);

                return [];
            }

            $size = (int) filesize($file->getPathname());
            if ($size > self::MAX_FILE_BYTES) {
                $errors['attachments'] = t('%s is too large. Each file must be %s MB or smaller.', $name, self::MAX_FILE_BYTES / 1048576);

                return [];
            }

            // The client-sent type and extension are not trusted.
            $mime = (string) $finfo->file($file->getPathname());
            if (!in_array($mime, self::ALLOWED_MIME, true)) {
                $errors['attachments'] = t('%s is not an allowed file type. Use PNG, JPEG, WebP or PDF.', $name);

                return [];
            }

            $total += $size;
            $attachments[] = new Attachment($name, $mime, $size, $file->getPathname());
        }

        if ($total > self::MAX_TOTAL_BYTES) {
            $errors['attachments'] = t('Attachments must total %s MB or less.', self::MAX_TOTAL_BYTES / 1048576);

            return [];
        }

        return $attachments;
    }

    /**
     * Largest single file the form can accept, taking PHP's own limits into account.
     */
    public static function effectiveFileLimit(): int
    {
        $limits = [self::MAX_FILE_BYTES];
        foreach (['upload_max_filesize', 'post_max_size'] as $ini) {
            $bytes = self::iniBytes((string) ini_get($ini));
            if ($bytes > 0) {
                $limits[] = $bytes;
            }
        }

        return min($limits);
    }

    public static function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    /**
     * @param array<string, mixed> $input form fields
     * @param array<?UploadedFile> $files uploaded attachments (empty slots allowed)
     */
    public function validate(array $input, array $files = []): ValidationResult
    {
        $errors = [];
        $text = static fn(string $key): string => trim((string) ($input[$key] ?? ''));

        $category = TicketCategory::tryFrom($text('category'));
        if ($category === null) {
            $errors['category'] = t('Choose what your request is about.');
        }

        $summary = $text('summary');
        $length = mb_strlen($summary);
        if ($length < 5 || $length > 150) {
            $errors['summary'] = t('The summary must be between %s and %s characters.', 5, 150);
        }

        $description = $text('description');
        $length = mb_strlen($description);
        if ($length < 10 || $length > 5000) {
            $errors['description'] = t('The description must be between %s and %s characters.', 10, 5000);
        }

        $email = $text('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = t('Enter a valid email address.');
        }

        $name = $text('name');
        if (mb_strlen($name) > 100) {
            $errors['name'] = t('The name must be %s characters or fewer.', 100);
        }

        $phone = $text('contact_phone');
        if ($phone !== '' && !preg_match('/^[0-9 +()-]{1,30}$/', $phone)) {
            $errors['contact_phone'] = t('The phone number may only contain digits, spaces and + ( ) -, up to %s characters.', 30);
        }

        $attachments = $this->validateFiles($files, $errors);

        if ($errors !== []) {
            return new ValidationResult(null, $errors);
        }

        $pageID = (int) ($input['page_id'] ?? 0);
        $pageVersionID = (int) ($input['page_version_id'] ?? 0);

        return new ValidationResult(new TicketSubmission(
            $category,
            $summary,
            $description,
            $email,
            $name !== '' ? $name : null,
            $phone !== '' ? $phone : null,
            $pageID > 0 ? $pageID : null,
            $pageVersionID > 0 ? $pageVersionID : null,
            $this->sameHostUrl($text('page_url')),
            $attachments,
        ), []);
    }

    /**
     * The URL when it is on the site's canonical host; anything else is discarded.
     */
    public function sameHostUrl(string $url): ?string
    {
        if ($url === '' || $this->canonicalUrl === null || mb_strlen($url) > 255 || !filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($url);
        $site = parse_url($this->canonicalUrl);
        if (!isset($parts['scheme'], $parts['host'], $site['host'])
            || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        return strcasecmp($parts['host'], $site['host']) === 0 ? $url : null;
    }
}
