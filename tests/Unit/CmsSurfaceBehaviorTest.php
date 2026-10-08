<?php

namespace {
    require_once dirname(__DIR__, 2) . '/attributes/country/controller.php';
    require_once dirname(__DIR__, 2) . '/attributes/lgt_page_redirector/controller.php';
    require_once dirname(__DIR__, 2) . '/src/Concrete/Entity/Attribute/Value/Value/RedirectValue.php';
    require_once dirname(__DIR__, 2) . '/blocks/lgt_button/controller.php';
    require_once dirname(__DIR__, 2) . '/controllers/single_page/dashboard/lgt_toolkit.php';
    require_once dirname(__DIR__, 2) . '/controller.php';
}

namespace LgtToolkit\Tests\Unit {
    use Core;
    use PageTheme;
    use PHPUnit\Framework\TestCase;
    use Concrete\Core\Entity\Package;
    use Concrete\Core\Localization\Service\CountryList;
    use Concrete\Package\LgtToolkit\Controller as PackageController;
    use Concrete\Package\LgtToolkit\Block\LgtButton\Controller as ButtonBlockController;
    use Concrete\Package\LgtToolkit\Attribute\Country\Controller as CountryAttributeController;
    use Concrete\Package\LgtToolkit\Controller\SinglePage\Dashboard\LgtToolkit as DashboardController;
    use Concrete\Package\LgtToolkit\Attribute\LgtPageRedirector\Controller as RedirectAttributeController;

    final class TestableRedirectAttributeController extends RedirectAttributeController
    {
        public array $postedValues = [];

        public function post($field = false, $defaultValue = null)
        {
            if ($field === false) {
                return $this->postedValues;
            }

            return $this->postedValues[$field] ?? $defaultValue;
        }
    }

    final class TestPackageEntity extends Package
    {
        public function getRelativePath(): string
        {
            return '/packages/lgt_toolkit';
        }
    }

    final class CmsSurfaceBehaviorTest extends TestCase
    {
        private function render(string $path, array $variables, ?object $context = null): string
        {
            $renderer = function () use ($path, $variables): void {
                extract($variables, EXTR_SKIP);
                include $path;
            };

            ob_start();
            if ($context === null) {
                $renderer();
            } else {
                $renderer->call($context);
            }

            return (string) ob_get_clean();
        }

        public function testCountryAttributeNormalizesAndRejectsUnknownCountryCodes(): void
        {
            $countries = $this->createMock(CountryList::class);
            $countries->method('getCountries')->willReturn([
                'GB' => 'United Kingdom',
                'US' => 'United States',
            ]);
            Core::set(CountryList::class, $countries);

            $controller = (new \ReflectionClass(CountryAttributeController::class))->newInstanceWithoutConstructor();
            $value = $controller->createAttributeValue(' gb ');

            self::assertSame('GB', $value->getValue());
            self::assertNull($controller->createAttributeValue(''));
            self::assertNull($controller->createAttributeValue('XX'));
        }

        public function testRedirectAttributeBuildsTypedValuesFromRequestData(): void
        {
            $controller = (new \ReflectionClass(TestableRedirectAttributeController::class))->newInstanceWithoutConstructor();
            $controller->postedValues = [
                'redirectType' => 'page',
                'redirectMethod' => '301',
                'pageValue' => '42',
                'externalValue' => 'https://example.test/',
            ];
            /** @var \Concrete\Package\LgtToolkit\Entity\Attribute\Value\Value\RedirectValue $value */
            $value = $controller->createAttributeValueFromRequest();

            self::assertSame('page', $value->getRedirectType());
            self::assertSame(301, $value->getRedirectMethod());
            self::assertSame('42', $value->getValue());
            self::assertSame([
                'page' => 'Page',
                'external' => 'External URL',
            ], $controller->getRedirectTypes());
        }

        public function testButtonBlockExposesSupportedStyles(): void
        {
            $controller = (new \ReflectionClass(ButtonBlockController::class))->newInstanceWithoutConstructor();

            self::assertSame('Button', $controller->getBlockTypeName());
            self::assertSame('Primary', $controller->getStyles()['primary']);
            self::assertSame('Outline Dark', $controller->getStyles()['outline-dark']);
            self::assertCount(17, $controller->getStyles());
        }

        public function testDashboardControllerBuildsPackageThumbnailPath(): void
        {
            $package = new TestPackageEntity();

            $controller = (new \ReflectionClass(DashboardController::class))->newInstanceWithoutConstructor();
            $property = new \ReflectionProperty(DashboardController::class, 'pkg');
            $property->setValue($controller, $package);

            self::assertSame(
                '/packages/lgt_toolkit/images/thumbnails/cookie_popup.png',
                $controller->getPageThumbnail('cookie_popup'),
            );
        }

        public function testPackageControllerProvidesPackageMetadata(): void
        {
            $controller = (new \ReflectionClass(PackageController::class))->newInstanceWithoutConstructor();

            self::assertSame('LGT Toolkit', $controller->getPackageName());
            self::assertSame('LGT tools and defaults for Concrete CMS', $controller->getPackageDescription());
        }

        public function testResponsivePictureOverrideRendersImageAttributesAndCaption(): void
        {
            Core::set('helper/image', new \stdClass());
            Core::set('focal_point', new class {
                public function getFocalPoint(object $file): string
                {
                    return '25% 75%';
                }
            });
            Core::set(\Concrete\Core\File\Image\Thumbnail\Type\Type::class, new \stdClass());
            PageTheme::$siteTheme = new class {
                public function getThemeResponsiveImageMap(): array
                {
                    return [];
                }
            };

            $file = new class {
                public function getFileName(): string
                {
                    return 'image.jpg';
                }

                public function getAttribute(string $name): int
                {
                    return $name === 'width' ? 640 : 480;
                }

                public function getRelativePath(): string
                {
                    return '/files/image.jpg';
                }
            };

            $output = $this->render(dirname(__DIR__, 2) . '/overrides/elements/picture.php', [
                'f' => $file,
                'altText' => 'A landscape',
                'title' => 'Landscape',
                'classes' => ['img-fluid', 'rounded'],
            ]);

            self::assertStringContainsString('<figure>', $output);
            self::assertStringContainsString('loading="lazy"', $output);
            self::assertStringContainsString('class="img-fluid rounded"', $output);
            self::assertStringContainsString('width="640"', $output);
            self::assertStringContainsString('height="480"', $output);
            self::assertStringContainsString('src="/files/image.jpg"', $output);
            self::assertStringContainsString('alt="A landscape"', $output);
            self::assertStringContainsString('title="Landscape"', $output);
            self::assertStringContainsString('style="object-position: 25% 75%;"', $output);
            self::assertStringContainsString('<figcaption>Landscape</figcaption>', $output);
        }

        public function testDashboardPageShowsDebugBarAndChildPages(): void
        {
            $dashboardView = new class {
                public object $controller;

                public function __construct()
                {
                    $this->controller = new class {
                        public function getPageThumbnail(string $handle): string
                        {
                            return '/thumbs/' . $handle . '.png';
                        }

                        public function getDebugBarStatus(): bool
                        {
                            return true;
                        }
                    };
                }

                public function action(string $action): string
                {
                    return '/action/' . $action;
                }
            };
            $page = new class {
                public function getCollectionHandle(): string
                {
                    return 'cookie_popup';
                }

                public function getCollectionName(): string
                {
                    return 'Cookie Popup';
                }

                public function getCollectionLink(): string
                {
                    return '/dashboard/lgt_toolkit/cookie_popup';
                }
            };

            $output = $this->render(
                dirname(__DIR__, 2) . '/single_pages/dashboard/lgt_toolkit.php',
                ['pages' => [$page]],
                $dashboardView,
            );

            self::assertStringContainsString('/thumbs/debug_bar.png', $output);
            self::assertStringContainsString('/action/toggle_debugbar', $output);
            self::assertStringContainsString('Deactivate', $output);
            self::assertStringContainsString('Cookie Popup', $output);
            self::assertStringContainsString('/dashboard/lgt_toolkit/cookie_popup', $output);
        }
        protected function setUp(): void
        {
            Core::reset();
            PageTheme::$siteTheme = null;
        }
    }
}
