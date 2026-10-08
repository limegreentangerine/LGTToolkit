<?php

namespace Concrete\Core\Entity\Express\Association {
    class ExampleAssociation {}
}

namespace LgtToolkit\Tests\Unit {
    require_once dirname(__DIR__, 2) . '/src/Concrete/Entity/Attribute/Value/Value/RedirectValue.php';

    use Core;
    use PHPUnit\Framework\TestCase;
    use LgtToolkit\Mail\SendEmailRequest;
    use LgtToolkit\Block\SocialShare\ShareLink;
    use LgtToolkit\Entity\File\ImageFocalPoint;
    use Concrete\Core\Error\ErrorList\ErrorList;
    use Symfony\Component\HttpFoundation\Response;
    use LgtToolkit\Express\DuplicateExpressObjects;
    use LgtToolkit\Providers\LgtMail\LgtMailService;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Concrete\Core\File\Service\File as FileService;
    use LgtToolkit\Providers\AutoCache\AutoCacheService;
    use LgtToolkit\Block\SocialShare\Service as SocialShareService;
    use Concrete\Core\Entity\Express\Association\ExampleAssociation;
    use Concrete\Core\Sharing\SocialNetwork\Service as ConcreteSocialService;

    class TestableDuplicateExpressObjects extends DuplicateExpressObjects
    {
        public function __construct() {}

        public function methodName(object $object): string
        {
            return $this->getAssociationFunctionName($object);
        }
    }

    class TestableLgtMailService extends LgtMailService
    {
        public function __construct()
        {
            $this->error = new ErrorList();
        }

        public function render(SendEmailRequest $request): string
        {
            return $this->renderBody(new FileService(), $request);
        }

    }

    final class PackageServicesBehaviorTest extends TestCase
    {
        public function testAutoCacheAddsFileModificationTimeAndLeavesMissingPathsAlone(): void
        {
            $documentRoot = sys_get_temp_dir() . '/lgt-autocache-' . uniqid('', true);
            mkdir($documentRoot . '/assets', 0777, true);
            $filePath = $documentRoot . '/assets/app.css';
            file_put_contents($filePath, 'body { color: red; }');

            $originalDocumentRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;
            $_SERVER['DOCUMENT_ROOT'] = $documentRoot;

            try {
                $service = new AutoCacheService();
                self::assertSame('/assets/app.css?' . filemtime($filePath), $service->autocache('/assets', 'app.css'));
                self::assertSame('/assets/missing.css', $service->autocache('/assets', 'missing.css'));
            } finally {
                if ($originalDocumentRoot === null) {
                    unset($_SERVER['DOCUMENT_ROOT']);
                } else {
                    $_SERVER['DOCUMENT_ROOT'] = $originalDocumentRoot;
                }
                unlink($filePath);
                rmdir($documentRoot . '/assets');
                rmdir($documentRoot);
            }
        }

        public function testImageFocalPointAndRedirectValueRetainTheirValues(): void
        {
            $focalPoint = new ImageFocalPoint();
            $focalPoint->setX('25')->setY('75');
            self::assertSame('25', $focalPoint->getX());
            self::assertSame('75', $focalPoint->getY());
            self::assertSame('25%', $focalPoint->getX(true));
            self::assertSame('75%', $focalPoint->getY(true));
            self::assertSame('25% 75%', $focalPoint->getFocalPoint());

            $redirect = new \Concrete\Package\LgtToolkit\Entity\Attribute\Value\Value\RedirectValue();
            $redirect->setRedirectType('external')->setRedirectMethod(301)->setValue('https://example.test/');
            self::assertSame('external', $redirect->getRedirectType());
            self::assertSame(301, $redirect->getRedirectMethod());
            self::assertSame('https://example.test/', $redirect->getValue());
        }

        public function testSocialShareBuildsEncodedLinksAndFallbackIconMarkup(): void
        {
            $facebook = new SocialShareService(new ConcreteSocialService('facebook', 'Facebook', 'facebook'));
            self::assertSame(
                'https://www.facebook.com/share.php?u=https%3A%2F%2Fexample.test%2Fpage%3Fa%3Db&quote=Hello+world',
                $facebook->getSharerLink('https://example.test/page?a=b', 'Hello world'),
            );
            self::assertSame(
                '<i class="fa fa-facebook" aria-hidden="true" title="Facebook"></i>',
                $facebook->getServiceIconHTML(),
            );

            $twitter = new SocialShareService(new ConcreteSocialService('twitter', 'Twitter', 'twitter'));
            self::assertSame(
                'http://twitter.com/share?text=Hello+world&url=https%3A%2F%2Fexample.test%2Fpage&hashtags=newportlive',
                $twitter->getSharerLink('https://example.test/page', 'Hello world'),
            );

            Core::set('site', new class {
                public function getSite(): object
                {
                    return new class {
                        public function getSiteName(): string
                        {
                            return 'Example Site';
                        }
                    };
                }
            });
            $linkedin = new SocialShareService(new ConcreteSocialService('linkedin', 'LinkedIn', 'linkedin'));
            self::assertSame(
                'http://www.linkedin.com/shareArticle?mini=true&url=https%3A%2F%2Fexample.test&title=Hello&source=Example+Site',
                $linkedin->getSharerLink('https://example.test', 'Hello'),
            );
            self::assertSame('', (new SocialShareService(
                new ConcreteSocialService('unknown', 'Unknown', 'question'),
            ))->getSharerLink('https://example.test', 'Hello'));
        }

        public function testShareLinkAndAssociationNameHelpers(): void
        {
            $shareLink = new ShareLink();
            $shareLink->setID(42)->setBID(99)->setServiceHandle('facebook');
            self::assertSame(42, $shareLink->getID());
            self::assertSame(99, $shareLink->getBID());
            self::assertSame('facebook', $shareLink->getServiceHandle());

            self::assertSame(
                'addaddExample',
                (new TestableDuplicateExpressObjects())->methodName(new ExampleAssociation()),
            );
        }

        public function testMailServiceRendersTemplateLoopAndBaseUrl(): void
        {
            $documentRoot = sys_get_temp_dir() . '/lgt-mail-' . uniqid('', true);
            $mailDirectory = $documentRoot . '/application/mail';
            mkdir($mailDirectory, 0777, true);
            file_put_contents($mailDirectory . '/email_template.php', '<html>{{email_content}} {{footer}} {{base_url}}</html>');
            file_put_contents($mailDirectory . '/welcome.php', 'Hello {{first_name}} {{loop_replace}}');
            file_put_contents($mailDirectory . '/email_loop.php', '{{name}}<br />');

            $originalDocumentRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;
            $originalHost = $_SERVER['HTTP_HOST'] ?? null;
            $_SERVER['DOCUMENT_ROOT'] = $documentRoot;
            $_SERVER['HTTP_HOST'] = 'mail.example.test';

            try {
                $request = new SendEmailRequest(
                    template: 'welcome',
                    args: [
                        'replace' => [
                            'first_name' => 'Lee',
                            'footer' => 'Thanks!',
                            'loop' => [
                                ['name' => 'Alice'],
                                ['name' => 'Bob'],
                                'ignored',
                            ],
                        ],
                    ],
                    body_template: null,
                    template_loop: 'email_loop',
                    pkg: null,
                    testing: false,
                );

                $body = (new TestableLgtMailService())->render($request);
                self::assertSame('<html>Hello Lee Alice<br />Bob<br /> Thanks! https://mail.example.test</html>', $body);
            } finally {
                if ($originalDocumentRoot === null) {
                    unset($_SERVER['DOCUMENT_ROOT']);
                } else {
                    $_SERVER['DOCUMENT_ROOT'] = $originalDocumentRoot;
                }
                if ($originalHost === null) {
                    unset($_SERVER['HTTP_HOST']);
                } else {
                    $_SERVER['HTTP_HOST'] = $originalHost;
                }
                unlink($mailDirectory . '/email_template.php');
                unlink($mailDirectory . '/welcome.php');
                unlink($mailDirectory . '/email_loop.php');
                rmdir($mailDirectory);
                rmdir($documentRoot . '/application');
                rmdir($documentRoot);
            }
        }

        public function testMailServiceReturnsBadRequestForMissingRequiredFields(): void
        {
            $request = new SendEmailRequest('welcome', [], null, null, null, false);
            $response = (new TestableLgtMailService())->sendEmail($request);

            self::assertInstanceOf(JsonResponse::class, $response);
            self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
            self::assertFalse(json_decode((string) $response->getContent(), true)['success']);
        }
        protected function setUp(): void
        {
            Core::reset();
        }
    }
}
