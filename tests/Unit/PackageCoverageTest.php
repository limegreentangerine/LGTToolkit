<?php

declare(strict_types=1);

namespace {
    if (!function_exists('t')) {
        function t(string $message, mixed ...$args): string
        {
            if ($args !== []) {
                return vsprintf($message, $args);
            }

            return $message;
        }
    }

    if (!function_exists('h')) {
        function h(string $value): string
        {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
    }
}

namespace Concrete\Core\Entity\Express\Association {
    class ExampleAssociation
    {
    }
}

namespace LgtToolkit\Tests\Unit {
    use Concrete\Core\Entity\Express\Association\ExampleAssociation;
    use Concrete\Core\Error\ErrorList\ErrorList;
    use Concrete\Core\File\Service\File as FileService;
    use LgtToolkit\Area\GlobalArea;
    use LgtToolkit\Entity\File\ImageFocalPoint;
    use LgtToolkit\Express\DuplicateExpressObjects;
    use LgtToolkit\Mail\SendEmailRequest;
    use LgtToolkit\Providers\AutoCache\AutoCacheService;
    use LgtToolkit\Providers\LgtMail\LgtMailService;
    use Localization;
    use PHPUnit\Framework\TestCase;
    use Punic\Data;
    use Punic\Language;

    class ExposedLgtMailService extends LgtMailService
    {
        public function exposeApplyReplacements(string $body, array $replacements): string
        {
            return $this->applyReplacements($body, $replacements);
        }

        public function exposeGetTemplateBasePath(SendEmailRequest $request): string
        {
            return $this->getTemplateBasePath($request);
        }

        public function exposeRenderBody(FileService $fh, SendEmailRequest $request): string
        {
            return $this->renderBody($fh, $request);
        }

        public function exposeCheckRequestForErrors(SendEmailRequest $request): ErrorList
        {
            $this->error = new ErrorList();
            $this->checkRequestForErrors($request);

            return $this->error;
        }
    }

    class ExposedDuplicateExpressObjects extends DuplicateExpressObjects
    {
        public function __construct()
        {
        }

        public function exposeAssociationFunctionName(object $object): string
        {
            return $this->getAssociationFunctionName($object);
        }
    }

    final class PackageCoverageTest extends TestCase
    {
        public function testAutoCacheAddsModifiedTimestampToExistingFiles(): void
        {
            $documentRoot = sys_get_temp_dir() . '/lgt-autocache-' . uniqid('', true);
            mkdir($documentRoot . '/assets', 0777, true);
            $filePath = $documentRoot . '/assets/app.css';
            file_put_contents($filePath, 'body { color: red; }');

            $originalDocumentRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;
            $_SERVER['DOCUMENT_ROOT'] = $documentRoot;

            try {
                $service = new AutoCacheService();
                $url = $service->autocache('/assets', 'app.css');

                $this->assertSame('/assets/app.css?' . filemtime($filePath), $url);
            } finally {
                $_SERVER['DOCUMENT_ROOT'] = $originalDocumentRoot;
                unlink($filePath);
                rmdir($documentRoot . '/assets');
                rmdir($documentRoot);
            }
        }

        public function testImageFocalPointStoresCoordinatesAndFormatsPercentage(): void
        {
            $focalPoint = new ImageFocalPoint();
            $focalPoint->setX('25');
            $focalPoint->setY('75');

            $this->assertSame('25', $focalPoint->getX());
            $this->assertSame('75', $focalPoint->getY());
            $this->assertSame('25%', $focalPoint->getX(true));
            $this->assertSame('75%', $focalPoint->getY(true));
            $this->assertSame('25% 75%', $focalPoint->getFocalPoint());
        }

        public function testDuplicateExpressObjectsAssociationMethodNameNormalizesEntityNames(): void
        {
            $service = new ExposedDuplicateExpressObjects();
            $methodName = $service->exposeAssociationFunctionName(new ExampleAssociation());

            $this->assertSame('addaddExample', $methodName);
        }

        public function testGlobalAreaKeepsProvidedHandleOnSubclass(): void
        {
            $area = new class extends GlobalArea {
                public function __construct()
                {
                    $this->arHandle = 'hero';
                }
            };

            $this->assertSame('hero', $area->arHandle);
        }

        public function testLgtMailServiceApplyReplacementsReplacesTokens(): void
        {
            $service = new ExposedLgtMailService();
            $body = 'Hello {{first_name}}. {{footer}}';

            $result = $service->exposeApplyReplacements($body, [
                'first_name' => 'Lee',
                'footer' => 'Thanks!',
            ]);

            $this->assertSame('Hello Lee. Thanks!', $result);
        }

        public function testLgtMailServiceRendersBodyFromPackageTemplates(): void
        {
            $documentRoot = sys_get_temp_dir() . '/lgt-mail-render-' . uniqid('', true);
            $mailDir = $documentRoot . '/application/mail';
            mkdir($mailDir, 0777, true);

            file_put_contents($mailDir . '/email_template.php', '<html>{{email_content}}</html>');
            file_put_contents($mailDir . '/welcome.php', 'Hello {{first_name}} {{loop_replace}}');
            file_put_contents($mailDir . '/email_loop.php', '{{name}}<br />');

            $originalDocumentRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;
            $_SERVER['DOCUMENT_ROOT'] = $documentRoot;

            try {
                $service = new ExposedLgtMailService();
                $request = new SendEmailRequest(
                    template: 'welcome',
                    args: [
                        'first_name' => 'Lee',
                        'replace' => [
                            'first_name' => 'Lee',
                            'footer' => 'Thanks!',
                            'loop' => [
                                ['name' => 'Alice'],
                                ['name' => 'Bob'],
                            ],
                        ],
                    ],
                    body_template: 'email_template',
                    template_loop: 'email_loop',
                    pkg: null,
                    testing: false,
                );

                $body = $service->exposeRenderBody(new FileService(), $request);

                $this->assertStringContainsString('Hello Lee', $body);
                $this->assertStringContainsString('Alice', $body);
                $this->assertStringContainsString('Bob', $body);
            } finally {
                $_SERVER['DOCUMENT_ROOT'] = $originalDocumentRoot;
                unlink($mailDir . '/email_template.php');
                unlink($mailDir . '/welcome.php');
                unlink($mailDir . '/email_loop.php');
                rmdir($mailDir);
                rmdir($documentRoot . '/application');
                rmdir($documentRoot);
            }
        }

        public function testLgtMailServiceValidatesMissingToAndSubject(): void
        {
            $service = new ExposedLgtMailService();
            $request = new SendEmailRequest(
                template: 'welcome',
                args: ['replace' => ['first_name' => 'Lee']],
                body_template: 'email_template',
                template_loop: null,
                pkg: null,
                testing: false,
            );

            $errorList = $service->exposeCheckRequestForErrors($request);

            $this->assertStringContainsString('The &quot;To&quot; field is required', (string) $errorList);
            $this->assertStringContainsString('The &quot;Subject&quot; field is required', (string) $errorList);
        }
    }
}
