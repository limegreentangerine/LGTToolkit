<?php

namespace LgtToolkit\Tests\Unit;

use Core;
use View;
use Exception;
use PHPUnit\Framework\TestCase;
use LgtToolkit\Slideshow\Options;
use LgtToolkit\Slideshow\Slideshow;
use LgtToolkit\Slideshow\Options\OptionNumber;
use LgtToolkit\Slideshow\Options\OptionBoolean;
use LgtToolkit\Slideshow\Options\SlideTemplate;
use LgtToolkit\Slideshow\Options\OptionAutoplay;

final class SlideshowBehaviorTest extends TestCase
{
    private function render(string $path, array $variables): string
    {
        ob_start();
        (static function () use ($path, $variables): void {
            extract($variables, EXTR_SKIP);
            include $path;
        })();

        return (string) ob_get_clean();
    }

    public function testStructuredOptionsRoundTripToExpectedArray(): void
    {
        $options = Options::fromArray([
            'mobile' => 2,
            'desktop' => 3,
            'hd' => 4,
            'draggable' => true,
            'snap' => 'center',
            'gap' => ['mobile' => 5, 'desktop' => 10, 'hd' => 15],
            'peek' => ['mobile' => 1, 'desktop' => 2, 'hd' => 3],
            'showButtons' => ['mobile' => true, 'desktop' => false, 'hd' => true],
            'showPagination' => ['mobile' => false, 'desktop' => true, 'hd' => false],
            'useTheme' => false,
            'prevIcon' => 'bi-arrow-left',
            'nextIcon' => 'bi-arrow-right',
            'autoplay' => ['enabled' => true, 'speed' => 7, 'useTimer' => false],
        ]);

        self::assertSame([
            'mobile' => 2,
            'desktop' => 3,
            'hd' => 4,
            'draggable' => true,
            'snap' => 'center',
            'gap' => ['mobile' => 5, 'desktop' => 10, 'hd' => 15],
            'peek' => ['mobile' => 1, 'desktop' => 2, 'hd' => 3],
            'showButtons' => ['mobile' => true, 'desktop' => false, 'hd' => true],
            'showPagination' => ['mobile' => false, 'desktop' => true, 'hd' => false],
            'useTheme' => false,
            'prevIcon' => 'bi-arrow-left',
            'nextIcon' => 'bi-arrow-right',
            'autoplay' => ['enabled' => true, 'speed' => 7, 'useTimer' => false],
            'template' => null,
        ], $options->toArray());
    }

    public function testOptionValueObjectsApplyTheirDefaults(): void
    {
        self::assertSame(['mobile' => 0, 'desktop' => 0, 'hd' => 0], OptionNumber::fromArray([])->toArray());
        self::assertSame(['mobile' => false, 'desktop' => false, 'hd' => false], OptionBoolean::fromArray([])->toArray());
        self::assertSame(
            ['enabled' => false, 'speed' => 0, 'useTimer' => true],
            OptionAutoplay::fromArray(null)->toArray(),
        );
        self::assertNull(SlideTemplate::fromArray(null));
    }

    public function testSlideshowUsesDefaultsAndBuildsStyleVariables(): void
    {
        $slideshow = new Slideshow(['First slide', 'Second slide'], [
            'mobile' => 2,
            'desktop' => 3,
            'hd' => 4,
            'snap' => 'center',
        ]);

        self::assertInstanceOf(View::class, $slideshow->getView());
        self::assertSame('string', $slideshow->getArrayType());
        self::assertSame(['First slide', 'Second slide'], $slideshow->getSlides());
        self::assertSame('center', $slideshow->getOption('snap'));
        self::assertTrue($slideshow->getOption('useTheme'));
        self::assertSame('--mobile:2;--desktop:3;--hd:4;--snap:center;', $slideshow->generateStyleVariables());
        self::assertSame('slideshow/slideshow.css', $slideshow->stylesheet());
        self::assertSame('slideshow/theme.css', $slideshow->theme_stylesheet());
        self::assertSame('slideshow.js', $slideshow->javascript());
        self::assertSame('center', json_decode($slideshow->getOptions(true), true)['snap']);
    }

    public function testSlideshowClassifiesObjectsAndEmptySlides(): void
    {
        self::assertSame('object', (new Slideshow([new \stdClass()], []))->getArrayType());
        self::assertSame('empty', (new Slideshow([], []))->getArrayType());
    }

    public function testSlideshowRejectsMixedSlideTypes(): void
    {
        $slideshow = new Slideshow(['text', new \stdClass()], []);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Slide array must contain a single type of string[] or object[]');
        $slideshow->getArrayType();
    }

    public function testSlideshowElementRendersStringSlidesAndRegistersAssets(): void
    {
        $assets = new class {
            public function css(string $path, string $package): string
            {
                return 'css:' . $package . '/' . $path;
            }

            public function javascript(string $path, string $package): string
            {
                return 'js:' . $package . '/' . $path;
            }
        };
        Core::set('helper/html', $assets);

        $output = $this->render(dirname(__DIR__, 2) . '/elements/slideshow/slideshow.php', [
            'slides' => ['<p>One</p>', '<p>Two</p>'],
            'options' => ['useTheme' => false],
        ]);

        self::assertStringContainsString('<div class="component-slideshow__slide"><p>One</p></div>', $output);
        self::assertStringContainsString('<div class="component-slideshow__slide"><p>Two</p></div>', $output);
        self::assertStringContainsString('"snap":"start"', $output);
    }
    protected function setUp(): void
    {
        Core::reset();
    }
}
