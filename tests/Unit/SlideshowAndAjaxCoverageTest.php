<?php

declare(strict_types=1);

namespace {
    if (!class_exists('View', false)) {
        class View
        {
        }
    }
}

namespace LgtToolkit\Tests\Unit {
    use LgtToolkit\Ajax\Cookies;
    use LgtToolkit\Block\SocialShare\ShareLink;
    use LgtToolkit\Slideshow\Enums\SnapOptions;
    use LgtToolkit\Slideshow\Options;
    use LgtToolkit\Slideshow\Slideshow;
    use PHPUnit\Framework\TestCase;
    use Symfony\Component\HttpFoundation\JsonResponse;

    final class SlideshowAndAjaxCoverageTest extends TestCase
    {
        public function testOptionsFromArrayCreatesStructuredValues(): void
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
                'prevIcon' => 'bi-chevron-left',
                'nextIcon' => 'bi-chevron-right',
                'autoplay' => ['enabled' => true, 'speed' => 7, 'useTimer' => false],
            ]);

            $this->assertSame(2, $options->mobile);
            $this->assertSame(3, $options->desktop);
            $this->assertSame(4, $options->hd);
            $this->assertTrue($options->draggable);
            $this->assertSame(SnapOptions::Center, $options->snap);
            $this->assertSame(['mobile' => 5, 'desktop' => 10, 'hd' => 15], $options->gap->toArray());
            $this->assertSame(['mobile' => 1, 'desktop' => 2, 'hd' => 3], $options->peek->toArray());
            $this->assertSame(['mobile' => true, 'desktop' => false, 'hd' => true], $options->showButtons->toArray());
            $this->assertSame(['mobile' => false, 'desktop' => true, 'hd' => false], $options->showPagination->toArray());
            $this->assertFalse($options->useTheme);
            $this->assertSame('bi-chevron-left', $options->prevIcon);
            $this->assertSame('bi-chevron-right', $options->nextIcon);
            $this->assertSame(['enabled' => true, 'speed' => 7, 'useTimer' => false], $options->autoplay->toArray());
        }

        public function testSlideshowBuildsDefaultSettingsAndStyleVariables(): void
        {
            $slideshow = new Slideshow([
                'First slide',
                'Second slide',
            ], [
                'mobile' => 2,
                'desktop' => 3,
                'hd' => 4,
                'snap' => 'center',
                'showButtons' => ['mobile' => false, 'desktop' => true, 'hd' => false],
            ]);

            $this->assertSame('string', $slideshow->getArrayType());
            $this->assertSame(2, $slideshow->getOption('mobile'));
            $this->assertSame('center', $slideshow->getOption('snap'));
            $this->assertSame('slideshow/slideshow.css', $slideshow->stylesheet());
            $this->assertSame('slideshow/theme.css', $slideshow->theme_stylesheet());
            $this->assertSame('slideshow.js', $slideshow->javascript());
            $this->assertSame('--mobile:2;--desktop:3;--hd:4;--snap:center;', $slideshow->generateStyleVariables());
            $this->assertIsString($slideshow->getOptions(true));
            $this->assertSame(
                'center',
                json_decode($slideshow->getOptions(true), true)['snap'],
            );
        }

        public function testShareLinkTracksBasicMetadata(): void
        {
            $shareLink = new ShareLink();
            $shareLink->setID(42)
                ->setBID(99)
                ->setServiceHandle('facebook');

            $this->assertSame(42, $shareLink->getID());
            $this->assertSame(99, $shareLink->getBID());
            $this->assertSame('facebook', $shareLink->getServiceHandle());
        }

        public function testCookiesResponsesReturnExpectedStatuses(): void
        {
            $cookies = new Cookies();

            $accepted = $cookies->allowCookies();
            $declined = $cookies->disallowCookies();

            $this->assertInstanceOf(JsonResponse::class, $accepted);
            $this->assertInstanceOf(JsonResponse::class, $declined);
            $this->assertSame(['status' => 'accepted'], json_decode((string) $accepted->getContent(), true));
            $this->assertSame(['status' => 'declined'], json_decode((string) $declined->getContent(), true));
        }
    }
}
