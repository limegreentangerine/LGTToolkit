<?php

namespace {
    require_once dirname(__DIR__) . '/vendor/autoload.php';

    if (!defined('C5_EXECUTE')) {
        define('C5_EXECUTE', true);
    }

    foreach ([
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
}
