<?php

namespace LgtToolkit\Tests\Unit;

use Core;
use PHPUnit\Framework\TestCase;
use LgtToolkit\Support\SupportEnv;
use Concrete\Core\Production\Modes;
use LgtToolkit\Support\SupportSettings;
use PHPUnit\Framework\Attributes\DataProvider;

final class SupportEnvTest extends TestCase
{
    private const VARS = [
        SupportEnv::API_KEY,
        SupportEnv::URL,
        SupportEnv::ENABLED_NON_PRODUCTION,
        SupportEnv::FALLBACK_EMAIL,
    ];

    private function clearEnv(): void
    {
        // Host or CI environment must not leak into these tests.
        foreach (self::VARS as $name) {
            unset($_ENV[$name], $_SERVER[$name]);
            putenv($name);
        }
    }

    private function setMode(string $mode): void
    {
        Core::reset();
        Core::set('config', new class($mode) {
            public function __construct(private string $mode) {}

            public function get(string $key): ?string
            {
                return $key === 'concrete.security.production.mode' ? $this->mode : null;
            }
        });
    }

    public function testApiKeyIsTrimmedAndNullWhenBlank(): void
    {
        $this->assertNull(SupportEnv::apiKey());
        $this->assertFalse(SupportEnv::isConfigured());

        $_ENV[SupportEnv::API_KEY] = '  key_abc ';
        $this->assertSame('key_abc', SupportEnv::apiKey());
        $this->assertTrue(SupportEnv::isConfigured());

        $_ENV[SupportEnv::API_KEY] = '   ';
        $this->assertNull(SupportEnv::apiKey());
    }

    public function testFallsBackToServerAndGetenv(): void
    {
        $_SERVER[SupportEnv::API_KEY] = 'from_server';
        $this->assertSame('from_server', SupportEnv::apiKey());

        unset($_SERVER[SupportEnv::API_KEY]);
        putenv(SupportEnv::API_KEY . '=from_getenv');
        $this->assertSame('from_getenv', SupportEnv::apiKey());
    }

    public static function validUrlProvider(): array
    {
        return [
            'default' => ['https://portal.limegreentangerine.co.uk/api/tickets.php'],
            'port' => ['https://portal.example.com:8443/api/tickets.php'],
        ];
    }

    #[DataProvider('validUrlProvider')]
    public function testUrlAcceptsHttps(string $value): void
    {
        $_ENV[SupportEnv::URL] = " $value ";

        $this->assertSame($value, SupportEnv::url());
        $this->assertSame($value, SupportEnv::urlOrDefault());
    }

    public static function invalidUrlProvider(): array
    {
        return [
            'blank' => [''],
            'http' => ['http://portal.limegreentangerine.co.uk/api/tickets.php'],
            'http localhost outside dev' => ['http://localhost/mock.php'],
            'no scheme' => ['portal.limegreentangerine.co.uk'],
            'javascript' => ['javascript:alert(1)'],
            'query' => ['https://portal.example.com/api?x=1'],
            'credentials' => ['https://user:pass@portal.example.com/api'],
            'fragment' => ['https://portal.example.com/api#x'],
        ];
    }

    #[DataProvider('invalidUrlProvider')]
    public function testUrlRejectsInvalidValuesAndFallsBackToDefault(string $value): void
    {
        $_ENV[SupportEnv::URL] = $value;

        $this->assertNull(SupportEnv::url());
        $this->assertSame(SupportEnv::DEFAULT_URL, SupportEnv::urlOrDefault());
    }

    public function testHttpLocalhostOnlyInDevelopment(): void
    {
        $this->setMode(Modes::MODE_DEVELOPMENT);

        $_ENV[SupportEnv::URL] = 'http://localhost:8080/mock.php';
        $this->assertSame('http://localhost:8080/mock.php', SupportEnv::url());

        $_ENV[SupportEnv::URL] = 'http://127.0.0.1/mock.php';
        $this->assertSame('http://127.0.0.1/mock.php', SupportEnv::url());

        $_ENV[SupportEnv::URL] = 'http://example.com/mock.php';
        $this->assertNull(SupportEnv::url());
    }

    public static function booleanProvider(): array
    {
        return [
            ['1', true], ['true', true], ['TRUE', true], ['yes', true], ['On', true],
            ['0', false], ['false', false], ['no', false], ['enabled', false], ['', false],
        ];
    }

    #[DataProvider('booleanProvider')]
    public function testEnabledNonProduction(string $value, bool $expected): void
    {
        $_ENV[SupportEnv::ENABLED_NON_PRODUCTION] = $value;

        $this->assertSame($expected, SupportEnv::enabledNonProduction());
    }

    public function testFallbackEmailValidation(): void
    {
        $_ENV[SupportEnv::FALLBACK_EMAIL] = 'support@example.com';
        $this->assertSame('support@example.com', SupportEnv::fallbackEmail());

        $_ENV[SupportEnv::FALLBACK_EMAIL] = 'not-an-email';
        $this->assertNull(SupportEnv::fallbackEmail());
    }

    public function testSettingsEnabledRules(): void
    {
        $production = new SupportSettings(Modes::MODE_PRODUCTION);
        $staging = new SupportSettings(Modes::MODE_STAGING);

        $this->assertFalse($production->isEnabled());
        $this->assertSame('unconfigured', $production->disabledReason());

        $_ENV[SupportEnv::API_KEY] = 'key';
        $this->assertTrue($production->isEnabled());
        $this->assertNull($production->subjectPrefix());
        $this->assertFalse($staging->isEnabled());
        $this->assertSame('non_production', $staging->disabledReason());

        $_ENV[SupportEnv::ENABLED_NON_PRODUCTION] = 'true';
        $this->assertTrue($staging->isEnabled());
        $this->assertSame('[STAGING]', $staging->subjectPrefix());
        $this->assertSame('[DEV]', (new SupportSettings(Modes::MODE_DEVELOPMENT))->subjectPrefix());
    }

    protected function setUp(): void
    {
        $this->clearEnv();
        $this->setMode(Modes::MODE_PRODUCTION);
    }

    protected function tearDown(): void
    {
        $this->clearEnv();
        Core::reset();

        parent::tearDown();
    }
}
