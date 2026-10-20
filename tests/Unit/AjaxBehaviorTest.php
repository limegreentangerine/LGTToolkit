<?php

namespace LgtToolkit\Tests\Unit;

use Core;
use PHPUnit\Framework\TestCase;
use LgtToolkit\Ajax\PlaceholderText;

final class AjaxBehaviorTest extends TestCase
{
    public function testPlaceholderEndpointReturnsRequestedAllowedContent(): void
    {
        $path = sys_get_temp_dir() . '/lgt-toolkit-package';
        Core::set('token', new class {
            public function validate(string $handle): bool
            {
                return $handle === 'lgt_content_site_attribute';
            }
        });
        Core::set('helper/file', new class {
            public function getContents(string $path): string
            {
                return 'Cookie policy text';
            }
        });
        Core::set('Concrete\Core\Package\PackageService', new class($path) {
            public function __construct(private string $path) {}

            public function getByHandle(string $handle): object
            {
                return new class($this->path) {
                    public function __construct(private string $path) {}

                    public function getPackagePath(): string
                    {
                        return $this->path;
                    }
                };
            }
        });

        $originalRequest = $_REQUEST;
        $_REQUEST = ['dummy' => 'cookie'];

        try {
            $response = (new PlaceholderText())->getDummyText();
        } finally {
            $_REQUEST = $originalRequest;
        }

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('Cookie policy text', json_decode((string) $response->getContent(), true)['content']);
    }

    public function testPlaceholderEndpointRejectsInvalidDummyOptionsAndTokens(): void
    {
        Core::set('token', new class {
            public bool $isValid = true;

            public function validate(string $handle): bool
            {
                return $this->isValid;
            }

            public function getErrorMessage(): string
            {
                return 'Invalid token';
            }
        });
        Core::set('helper/file', new class {
            public function getContents(string $path): string
            {
                return '';
            }
        });
        Core::set('Concrete\Core\Package\PackageService', new class {
            public function getByHandle(string $handle): object
            {
                return new class {
                    public function getPackagePath(): string
                    {
                        return '/package';
                    }
                };
            }
        });

        $originalRequest = $_REQUEST;

        try {
            $_REQUEST = ['dummy' => 'unknown'];
            $invalidOptionResponse = (new PlaceholderText())->getDummyText();
            self::assertSame(400, $invalidOptionResponse->getStatusCode());
            self::assertSame('Invalid Option selected', json_decode((string) $invalidOptionResponse->getContent(), true)['error']);

            $_REQUEST = ['dummy' => 'cookie'];
            $missingContentResponse = (new PlaceholderText())->getDummyText();
            self::assertSame(404, $missingContentResponse->getStatusCode());

            Core::set('token', new class {
                public function validate(string $handle): bool
                {
                    return false;
                }

                public function getErrorMessage(): string
                {
                    return 'Invalid token';
                }
            });
            $invalidTokenResponse = (new PlaceholderText())->getDummyText();
            self::assertSame(400, $invalidTokenResponse->getStatusCode());
            self::assertSame('Invalid token', json_decode((string) $invalidTokenResponse->getContent(), true)['error']);
        } finally {
            $_REQUEST = $originalRequest;
        }
    }
    protected function setUp(): void
    {
        Core::reset();
    }
}
