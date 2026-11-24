<?php

namespace LgtToolkit\Tests\Unit;

use Core;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use LgtToolkit\Mail\SendEmailRequest;
use LgtToolkit\Providers\LgtMail\LgtMailService;
use Concrete\Core\File\Service\File as FileService;

final class LgtMailServiceBehaviorTest extends TestCase
{
    private object $mailer;

    private function send(array $args): array
    {
        $response = (new LgtMailService())->sendEmail(
            new SendEmailRequest('default', $args, null, null, null, false),
        );

        return [$response->getStatusCode(), json_decode($response->getContent(), true)];
    }

    public function testMissingRecipientReturnsBadRequestWithoutFatal(): void
    {
        [$status, $body] = $this->send(['subject' => 'Hi']);

        self::assertSame(400, $status);
        self::assertFalse($body['success']);
        self::assertNotEmpty($body['errors']);
    }

    public function testErrorsDoNotCarryOverBetweenCalls(): void
    {
        $service = new LgtMailService();
        $service->sendEmail(new SendEmailRequest('default', [], null, null, null, false));
        $response = $service->sendEmail(
            new SendEmailRequest('default', ['to' => 'a@b.test', 'subject' => 'Hi'], null, null, null, false),
        );

        self::assertSame(202, $response->getStatusCode());
    }

    public function testFallsBackToToolkitTemplatesAndReplacesPlaceholders(): void
    {
        [$status, $body] = $this->send(['to' => 'a@b.test', 'subject' => 'Hello', 'replace' => ['subject' => 'Hello']]);

        self::assertSame(202, $status);
        self::assertTrue($body['success']);
        self::assertSame('Hello', trim($this->mailer->calls['setBodyHTML'][0][0]));
        self::assertArrayNotHasKey('from', $this->mailer->calls, 'core default From (address + name) should be used');
    }

    public function testFailedSendReturnsServerError(): void
    {
        $this->mailer->result = false;

        [$status, $body] = $this->send(['to' => 'a@b.test', 'subject' => 'Hi']);

        self::assertSame(500, $status);
        self::assertFalse($body['success']);
    }

    public function testMailerExceptionCodeIsNotUsedAsHttpStatus(): void
    {
        $this->mailer->result = new RuntimeException('Auth failed', 535);

        [$status, $body] = $this->send(['to' => 'a@b.test', 'subject' => 'Hi']);

        self::assertSame(500, $status);
        self::assertSame('Auth failed', $body['errors']);
    }

    protected function setUp(): void
    {
        Core::reset();

        // Records calls and returns $result (or throws it) from sendMail().
        $this->mailer = new class {
            public array $calls = [];
            public mixed $result = true;

            public function __call(string $name, array $args): mixed
            {
                $this->calls[$name][] = $args;

                if ($name !== 'sendMail') {
                    return null;
                }
                if ($this->result instanceof \Throwable) {
                    throw $this->result;
                }

                return $this->result;
            }
        };

        Core::set('mail', $this->mailer);
        Core::set('helper/file', new FileService());
        Core::set('url/canonical', 'https://example.test/');
    }

    protected function tearDown(): void
    {
        Core::reset();
    }
}
