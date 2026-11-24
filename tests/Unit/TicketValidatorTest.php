<?php

namespace LgtToolkit\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LgtToolkit\Support\TicketCategory;
use LgtToolkit\Support\TicketValidator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class TicketValidatorTest extends TestCase
{
    /** 1x1 transparent PNG */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    /** @var list<string> */
    private array $tempFiles = [];

    private TicketValidator $validator;

    private function input(array $overrides = []): array
    {
        return array_merge([
            'category' => 'broken',
            'summary' => 'Contact form fails',
            'description' => 'The contact form shows an error when I submit it.',
            'email' => 'client@example.com',
            'name' => 'Client User',
            'contact_phone' => '',
            'page_url' => '',
        ], $overrides);
    }

    private function upload(string $name, string $content, int $error = UPLOAD_ERR_OK): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'lgt');
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;

        return new UploadedFile($path, $name, null, $error, true);
    }

    private function png(int $padTo = 0): string
    {
        return str_pad(base64_decode(self::PNG), $padTo, "\0");
    }

    public function testValidSubmission(): void
    {
        $result = $this->validator->validate($this->input([
            'contact_phone' => '+44 1234 567890',
            'page_id' => '12',
            'page_url' => 'https://WWW.example.com/about/',
        ]), [$this->upload('screen shot.png', $this->png())]);

        $this->assertTrue($result->isValid(), json_encode($result->errors));
        $this->assertSame(TicketCategory::Broken, $result->submission->category);
        $this->assertSame(12, $result->submission->pageID);
        $this->assertNull($result->submission->pageVersionID);
        $this->assertSame('https://WWW.example.com/about/', $result->submission->pageUrl);
        $this->assertSame('image/png', $result->submission->attachments[0]->mime);
    }

    public static function invalidFieldProvider(): array
    {
        return [
            'category' => ['category', 'urgent'],
            'summary short' => ['summary', 'Hi'],
            'summary long' => ['summary', str_repeat('a', 151)],
            'description short' => ['description', 'Help'],
            'description long' => ['description', str_repeat('a', 5001)],
            'email' => ['email', 'nope'],
            'email header injection' => ['email', "a@example.com\r\nBcc: x@example.com"],
            'name long' => ['name', str_repeat('a', 101)],
            'phone letters' => ['contact_phone', 'call me'],
            'phone long' => ['contact_phone', str_repeat('1', 31)],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidFieldProvider')]
    public function testInvalidFields(string $field, string $value): void
    {
        $result = $this->validator->validate($this->input([$field => $value]));

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey($field, $result->errors);
    }

    public function testMultibyteLengthsCountCharacters(): void
    {
        $result = $this->validator->validate($this->input(['summary' => str_repeat('é', 150)]));

        $this->assertTrue($result->isValid());
    }

    public static function foreignUrlProvider(): array
    {
        return [
            ['https://evil.example.org/'],
            ['javascript:alert(1)'],
            ['https://user:pass@www.example.com/'],
            ['ftp://www.example.com/'],
            ['https://www.example.com.evil.org/'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('foreignUrlProvider')]
    public function testForeignPageUrlIsDiscardedNotRejected(string $url): void
    {
        $result = $this->validator->validate($this->input(['page_url' => $url]));

        $this->assertTrue($result->isValid());
        $this->assertNull($result->submission->pageUrl);
    }

    public function testMimeSpoofingIsRejected(): void
    {
        $result = $this->validator->validate($this->input(), [
            $this->upload('shell.png', "<?php system(\$_GET['c']);"),
        ]);

        $this->assertArrayHasKey('attachments', $result->errors);
    }

    public function testFileCountLimit(): void
    {
        $files = array_map(fn($i) => $this->upload("f$i.png", $this->png()), range(1, 6));

        $this->assertArrayHasKey('attachments', $this->validator->validate($this->input(), $files)->errors);
    }

    public function testPerFileSizeLimit(): void
    {
        $result = $this->validator->validate($this->input(), [
            $this->upload('big.png', $this->png(TicketValidator::MAX_FILE_BYTES + 1)),
        ]);

        $this->assertArrayHasKey('attachments', $result->errors);
    }

    public function testTotalSizeLimit(): void
    {
        $six = 6 * 1024 * 1024 + 1;
        $result = $this->validator->validate($this->input(), [
            $this->upload('a.png', $this->png($six)),
            $this->upload('b.png', $this->png($six)),
        ]);

        $this->assertArrayHasKey('attachments', $result->errors);
    }

    public function testUploadErrorsAreReportedAndEmptySlotsIgnored(): void
    {
        $empty = $this->upload('', '', UPLOAD_ERR_NO_FILE);
        $this->assertTrue($this->validator->validate($this->input(), [$empty, null])->isValid());

        $tooBig = $this->upload('big.png', '', UPLOAD_ERR_INI_SIZE);
        $this->assertArrayHasKey('attachments', $this->validator->validate($this->input(), [$tooBig])->errors);

        $partial = $this->upload('half.png', '', UPLOAD_ERR_PARTIAL);
        $this->assertArrayHasKey('attachments', $this->validator->validate($this->input(), [$partial])->errors);
    }

    public function testIniBytes(): void
    {
        $this->assertSame(8 * 1024 * 1024, TicketValidator::iniBytes('8M'));
        $this->assertSame(1024 ** 3, TicketValidator::iniBytes('1G'));
        $this->assertSame(512, TicketValidator::iniBytes('512'));
        $this->assertLessThanOrEqual(TicketValidator::MAX_FILE_BYTES, TicketValidator::effectiveFileLimit());
    }

    protected function setUp(): void
    {
        $this->validator = new TicketValidator('https://www.example.com/');
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
    }
}
