<?php

namespace LgtToolkit\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LgtToolkit\Support\Diagnostics\DiagnosticsSource;
use LgtToolkit\Support\Diagnostics\DiagnosticsCollector;

final class DiagnosticsCollectorTest extends TestCase
{
    private function source(array $overrides = []): DiagnosticsSource
    {
        return new class($overrides) implements DiagnosticsSource {
            public function __construct(private array $o) {}

            private function value(string $key, mixed $default): mixed
            {
                $value = array_key_exists($key, $this->o) ? $this->o[$key] : $default;
                if ($value instanceof \Throwable) {
                    throw $value;
                }

                return $value;
            }

            public function site(): array
            {
                return $this->value('site', ['name' => 'Example', 'url' => 'https://www.example.com/', 'mode' => 'production']);
            }

            public function page(int $cID, ?int $cvID): ?array
            {
                return $this->value('page', ['cID' => $cID, 'cvID' => $cvID, 'path' => '/about', 'name' => 'About']);
            }

            public function user(): ?array
            {
                return $this->value('user', ['uID' => 7, 'username' => 'editor', 'groups' => ['Registered Users', 'Editors']]);
            }

            public function versions(): array
            {
                return $this->value('versions', ['concrete' => '9.5.4', 'php' => '8.4.4', 'packages' => ['lgt_toolkit' => '1.1.0']]);
            }

            public function userAgent(): ?string
            {
                return $this->value('agent', 'Mozilla/5.0');
            }

            public function recentErrors(int $limit): array
            {
                return $this->value('errors', []);
            }
        };
    }

    public function testCollectsAllowedSections(): void
    {
        $diagnostics = (new DiagnosticsCollector($this->source()))->collect(12, 3);

        $this->assertSame(['Site', 'Page', 'User', 'Versions', 'Client'], array_keys($diagnostics->sections));
        $this->assertSame('none', $diagnostics->recentErrors);

        $text = $diagnostics->toText();
        $this->assertStringContainsString("Page:\n  cID: 12\n  cvID: 3\n  Path: /about", $text);
        $this->assertStringContainsString('Groups: Registered Users, Editors', $text);
        $this->assertStringContainsString('Packages: lgt_toolkit 1.1.0', $text);
    }

    public function testPageOmittedWhenNotGivenOrNotViewable(): void
    {
        $collector = new DiagnosticsCollector($this->source(['page' => null]));

        $this->assertArrayNotHasKey('Page', $collector->collect(12)->sections);
        $this->assertArrayNotHasKey('Page', (new DiagnosticsCollector($this->source()))->collect()->sections);
    }

    public function testFailingSourceDropsSectionOnly(): void
    {
        $diagnostics = (new DiagnosticsCollector($this->source([
            'user' => new \RuntimeException('db down'),
            'errors' => new \RuntimeException('no Logs table'),
        ])))->collect();

        $this->assertArrayNotHasKey('User', $diagnostics->sections);
        $this->assertArrayHasKey('Site', $diagnostics->sections);
        $this->assertSame('unavailable', $diagnostics->recentErrors);
    }

    public function testRecentErrorsAreRedactedAndLimited(): void
    {
        $errors = array_fill(0, 20, ['time' => 't', 'channel' => 'app', 'message' => 'Login failed for bob@example.com password=hunter2']);
        $diagnostics = (new DiagnosticsCollector($this->source(['errors' => $errors])))->collect();

        $lines = explode("\n", $diagnostics->recentErrors);
        $this->assertCount(DiagnosticsCollector::ERROR_LIMIT, $lines);
        $this->assertSame('[t] app: Login failed for [email] password=[redacted]', $lines[0]);
    }

    public function testErrorLogCanBeExcluded(): void
    {
        $this->assertSame('not included', (new DiagnosticsCollector($this->source(), false))->collect()->recentErrors);
    }

    public function testUserAgentTruncated(): void
    {
        $diagnostics = (new DiagnosticsCollector($this->source(['agent' => str_repeat('a', 600)])))->collect();

        $this->assertSame(DiagnosticsCollector::USER_AGENT_MAX, mb_strlen($diagnostics->sections['Client']['User-Agent']));
    }
}
