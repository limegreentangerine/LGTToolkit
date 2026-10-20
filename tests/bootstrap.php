<?php

namespace {
    require_once dirname(__DIR__) . '/vendor/autoload.php';

    if (!defined('C5_EXECUTE')) {
        define('C5_EXECUTE', true);
    }

    foreach ([
        'DIR_BASE' => sys_get_temp_dir() . DIRECTORY_SEPARATOR,
        'DIR_PACKAGES_CORE' => sys_get_temp_dir(),
        'DIR_PACKAGES' => sys_get_temp_dir(),
        'REL_DIR_PACKAGES_CORE' => '/concrete/packages',
        'REL_DIR_PACKAGES' => '/packages',
    ] as $name => $value) {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    if (!function_exists('t')) {
        function t(string $message, mixed ...$args): string
        {
            return $args === [] ? $message : vsprintf($message, $args);
        }
    }

    if (!function_exists('h')) {
        function h(string $value): string
        {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
    }

    if (!class_exists('Core', false)) {
        class Core
        {
            private static array $services = [];

            public static function set(string $name, mixed $service): void
            {
                self::$services[$name] = $service;
            }

            public static function reset(): void
            {
                self::$services = [];
            }

            public static function make(string $name): mixed
            {
                if (!array_key_exists($name, self::$services)) {
                    throw new \RuntimeException(sprintf('No test service is registered for "%s".', $name));
                }

                return self::$services[$name];
            }
        }
    }

    if (!class_exists('View', false)) {
        class View
        {
            public array $headerItems = [];
            public array $footerItems = [];

            public function addHeaderItem(mixed $item): void
            {
                $this->headerItems[] = $item;
            }

            public function addFooterItem(mixed $item): void
            {
                $this->footerItems[] = $item;
            }
        }
    }

    if (!class_exists('PageTheme', false)) {
        class PageTheme
        {
            public static mixed $siteTheme;

            public static function getSiteTheme(): mixed
            {
                return self::$siteTheme;
            }
        }
    }
    if (!class_exists('Page', false)) {
        class Page
        {
            /** @var array<int, Page> */
            public static array $byId = [];

            /** @var array<string, Page> */
            public static array $byPath = [];

            public array $addedPaths = [];

            public function __construct(
                public int $cID = 1,
                public string $path = '/page',
                public bool $error = false,
                public bool $trashed = false,
                public bool $adminArea = false,
                public bool $systemPage = false,
                public string $externalLink = '',
                public int $nextPagePathID = 100,
            ) {}

            public static function reset(): void
            {
                self::$byId = [];
                self::$byPath = [];
            }

            public static function getByID(int $cID, string $version = 'RECENT'): self
            {
                return self::$byId[$cID] ?? new self($cID, '', true);
            }

            public static function getByPath(string $path, string $version = 'RECENT'): self
            {
                return self::$byPath[$path] ?? new self(0, '', true);
            }

            public function isError(): bool
            {
                return $this->error;
            }

            public function isInTrash(): bool
            {
                return $this->trashed;
            }

            public function isAdminArea(): bool
            {
                return $this->adminArea;
            }

            public function isSystemPage(): bool
            {
                return $this->systemPage;
            }

            public function getCollectionPointerExternalLink(): string
            {
                return $this->externalLink;
            }

            public function getCollectionID(): int
            {
                return $this->cID;
            }

            public function getCollectionPath(): string
            {
                return $this->path;
            }

            public function getCollectionLink(): string
            {
                return 'https://example.com' . $this->path;
            }

            public function addAdditionalPagePath(string $path): object
            {
                $this->addedPaths[] = $path;
                $id = $this->nextPagePathID++;

                return new class($id) {
                    public function __construct(private int $id) {}

                    public function getPagePathID(): int
                    {
                        return $this->id;
                    }
                };
            }
        }
    }

    if (!class_exists('Request', false)) {
        class Request
        {
            public static string $host = 'example.com';

            public static function getInstance(): object
            {
                return new class(self::$host) {
                    public function __construct(private string $host) {}

                    public function getHost(): string
                    {
                        return $this->host;
                    }
                };
            }
        }
    }
}
