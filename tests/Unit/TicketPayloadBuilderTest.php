<?php

namespace LgtToolkit\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LgtToolkit\Support\Redactor;
use LgtToolkit\Support\Attachment;
use LgtToolkit\Support\LocalReference;
use LgtToolkit\Support\TicketCategory;
use LgtToolkit\Support\UserRateLimiter;
use LgtToolkit\Support\TicketSubmission;
use LgtToolkit\Support\TicketPayloadBuilder;

final class TicketPayloadBuilderTest extends TestCase
{
    private TicketPayloadBuilder $builder;

    private \DateTimeImmutable $now;

    private function submission(string $description = 'The form is broken.'): TicketSubmission
    {
        return new TicketSubmission(TicketCategory::Broken, 'Form fails', $description, 'a@example.com');
    }

    public function testSubjectStripsHeaderInjectionAndControlChars(): void
    {
        $subject = $this->builder->subject(TicketCategory::Broken, "foo\r\nBcc: x\0\x07", '[STAGING]');

        $this->assertStringNotContainsString("\n", $subject);
        $this->assertStringNotContainsString("\r", $subject);
        $this->assertStringNotContainsString("\0", $subject);
        $this->assertStringStartsWith("[STAGING] Something's broken: foo", $subject);
    }

    public function testSubjectIsTruncatedByCharacters(): void
    {
        $subject = $this->builder->subject(TicketCategory::Broken, str_repeat('é', 200));

        $this->assertSame(150, mb_strlen($subject));
        $this->assertTrue(mb_check_encoding($subject, 'UTF-8'));
    }

    public function testMessageLayout(): void
    {
        $message = $this->builder->message($this->submission(), 'Q-20261124-ABCD', $this->now, 'Site: Example', 'none');

        $this->assertStringStartsWith("The form is broken.\n\n---\nCategory: Something's broken\n", $message);
        $this->assertStringContainsString("Page: not specified\n", $message);
        $this->assertStringContainsString("Contact phone: not given\n", $message);
        $this->assertStringContainsString("Submitted: 2026-11-24T10:00:00+00:00\n", $message);
        $this->assertStringContainsString("Local reference: Q-20261124-ABCD\n", $message);
        $this->assertStringContainsString("--- Diagnostics ---\nSite: Example\n", $message);
        $this->assertStringContainsString("--- Recent errors (redacted) ---\nnone\n", $message);
    }

    public function testInvalidUtf8BecomesValidJson(): void
    {
        $message = $this->builder->message($this->submission("Broken \xC3\x28 text\0"), 'Q-1', $this->now, '', '');
        $json = $this->builder->encode($this->builder->payload('a@example.com', null, 'S', $message, null));

        $this->assertIsArray(json_decode($json, true));
        $this->assertStringNotContainsString('\u0000', $json);
    }

    public function testLongDiagnosticsAreTruncatedButDescriptionKept(): void
    {
        $description = str_repeat('d', 5000);
        $message = $this->builder->message($this->submission($description), 'Q-1', $this->now, str_repeat('x', 30000), 'none');

        $this->assertStringContainsString($description, $message);
        $this->assertStringContainsString('[truncated]', $message);

        $withNote = $this->builder->deliveryNote($message, 'Q-1', $this->now, 3, $this->now, true);
        $this->assertLessThanOrEqual(TicketPayloadBuilder::MAX_MESSAGE, mb_strlen($withNote));
        $this->assertStringContainsString('this is a duplicate.', $withNote);
    }

    public function testDeliveryNote(): void
    {
        $note = $this->builder->deliveryNote('Body', 'Q-1', $this->now, 2, $this->now->modify('-1 hour'), false);

        $this->assertStringContainsString("--- Delivery note ---\nDelivered by retry queue at 2026-11-24T10:00:00+00:00 (attempt 2). Originally submitted 2026-11-24T09:00:00+00:00.", $note);
        $this->assertStringNotContainsString('duplicate', $note);
    }

    public function testPayloadFieldsAndAttachments(): void
    {
        $payload = $this->builder->payload('a@example.com', 'Al', 'S', 'M', 'https://www.example.com/', [
            new Attachment('../my file <1>.png', 'image/png', 3, null, 'abc'),
        ]);

        $this->assertSame([
            'email' => 'a@example.com',
            'subject' => 'S',
            'message' => 'M',
            'name' => 'Al',
            'page_url' => 'https://www.example.com/',
            'attachments' => [['filename' => '_my_file__1_.png', 'content_base64' => 'YWJj']],
        ], $payload);

        $this->assertArrayNotHasKey('name', $this->builder->payload('a@example.com', null, 'S', 'M', null));
    }

    public function testSafeFilenameKeepsExtensionWhenTruncating(): void
    {
        $name = TicketPayloadBuilder::safeFilename(str_repeat('a', 150) . '.pdf');

        $this->assertSame(100, strlen($name));
        $this->assertStringEndsWith('.pdf', $name);
        $this->assertSame('attachment', TicketPayloadBuilder::safeFilename('...'));
    }

    public function testEncodeThrowsInsteadOfSendingEmptyBody(): void
    {
        $this->expectException(\JsonException::class);
        $this->builder->encode(['message' => NAN]);
    }

    public function testLocalReferenceFormat(): void
    {
        $this->assertMatchesRegularExpression('/^Q-20261124-[A-Z2-7]{4}$/', LocalReference::generate($this->now));
    }

    public static function redactionProvider(): array
    {
        return [
            'email' => ['Mail from bob@example.com failed', 'Mail from [email] failed'],
            'password' => ['password=hunter2 rest', 'password=[redacted] rest'],
            'api key' => ['API-KEY: abc123', 'API-KEY=[redacted]'],
            'bearer' => ['Authorization: Bearer abc.def', 'Authorization=[redacted] [redacted]'],
            'card' => ['card 4111 1111 1111 1111 declined', 'card [number] declined'],
            'short numbers kept' => ['Order 12345 page 2', 'Order 12345 page 2'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('redactionProvider')]
    public function testRedactor(string $input, string $expected): void
    {
        $this->assertSame($expected, Redactor::redact($input));
    }

    public function testRedactorTruncates(): void
    {
        $this->assertSame(500, mb_strlen(Redactor::redact(str_repeat('é', 600))));
    }

    public function testUserRateLimiter(): void
    {
        $store = [];
        $limiter = new UserRateLimiter(
            function (string $key) use (&$store): int {
                return $store[$key] ?? 0;
            },
            function (string $key, int $value) use (&$store): void {
                $store[$key] = $value;
            },
            5,
        );

        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($limiter->isAllowed(1, $this->now));
            $limiter->hit(1, $this->now);
        }

        $this->assertFalse($limiter->isAllowed(1, $this->now));
        $this->assertTrue($limiter->isAllowed(2, $this->now));
        $this->assertTrue($limiter->isAllowed(1, $this->now->modify('+1 hour')));
    }

    protected function setUp(): void
    {
        $this->builder = new TicketPayloadBuilder();
        $this->now = new \DateTimeImmutable('2026-11-24T10:00:00+00:00');
    }
}
