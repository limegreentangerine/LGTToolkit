<?php

namespace LgtToolkit\Tests\Unit {
    require_once dirname(__DIR__, 2) . '/src/Concrete/Entity/Redirection.php';

    use Page;
    use PHPUnit\Framework\TestCase;
    use Concrete\Core\Application\Application;
    use Symfony\Component\HttpFoundation\Request;
    use PHPUnit\Framework\Attributes\DataProvider;
    use Symfony\Component\HttpFoundation\Response;
    use LgtToolkit\Redirections\RedirectionService;
    use LgtToolkit\Redirections\RedirectionMiddleware;
    use Concrete\Package\LgtToolkit\Entity\Redirection;
    use Concrete\Core\Http\Middleware\DelegateInterface;

    final class RedirectionMiddlewareBehaviorTest extends TestCase
    {
        private RedirectionService $service;

        private Response $fallthrough;

        private int $lookups = 0;

        private function dispatch(Request $request, ?\Closure $make = null): Response
        {
            $app = $this->createMock(Application::class);
            $app->method('make')->willReturnCallback($make ?? fn() => $this->service);

            $frame = $this->createMock(DelegateInterface::class);
            $frame->method('next')->willReturn($this->fallthrough);

            return (new RedirectionMiddleware($app))->process($request, $frame);
        }

        private function rule(string $source, int $cID, int $status): Redirection
        {
            return (new Redirection())->setSourcePath($source)->setPageID($cID)->setStatusCode($status);
        }

        private function matchRule(Redirection $rule, ?Page $page): void
        {
            $this->service->method('findBySourcePath')->willReturnCallback(function (string $path) use ($rule) {
                $this->lookups++;

                return $path === $rule->getSourcePath() ? $rule : null;
            });
            $this->service->method('resolveDestination')->willReturn($page);
        }

        public function testMatchingRuleRedirectsWithItsStatusCode(): void
        {
            $this->matchRule($this->rule('/old', 5, 301), new Page(5, '/new'));

            $response = $this->dispatch(Request::create('/old'));

            $this->assertSame(301, $response->getStatusCode());
            $this->assertSame('https://example.com/new', $response->headers->get('Location'));
        }

        public function testTemporaryRulesUseTheirOwnStatus(): void
        {
            $this->matchRule($this->rule('/old', 5, 302), new Page(5, '/new'));

            $this->assertSame(302, $this->dispatch(Request::create('/old'))->getStatusCode());
        }

        public function testMatchingIsCaseAndTrailingSlashInsensitive(): void
        {
            $this->matchRule($this->rule('/old/page', 5, 301), new Page(5, '/new'));

            $this->assertSame(301, $this->dispatch(Request::create('/Old/Page/'))->getStatusCode());
        }

        public function testQueryStringIsCarriedOverToTheDestination(): void
        {
            $this->matchRule($this->rule('/old', 5, 301), new Page(5, '/new'));

            $response = $this->dispatch(Request::create('/old?utm=1&b=2'));

            $this->assertSame('https://example.com/new?b=2&utm=1', $response->headers->get('Location'));
        }

        public function testQueryStringIsAppendedWithAmpersandWhenDestinationAlreadyHasOne(): void
        {
            $page = new class(5, '/new') extends Page {
                public function getCollectionLink(): string
                {
                    return 'https://example.com/index.php?cID=5';
                }
            };
            $this->matchRule($this->rule('/old', 5, 301), $page);

            $response = $this->dispatch(Request::create('/old?utm=1'));

            $this->assertSame('https://example.com/index.php?cID=5&utm=1', $response->headers->get('Location'));
        }

        public function testHeadRequestsAreRedirectedToo(): void
        {
            $this->matchRule($this->rule('/old', 5, 301), new Page(5, '/new'));

            $this->assertSame(301, $this->dispatch(Request::create('/old', 'HEAD'))->getStatusCode());
        }

        #[DataProvider('unsafeMethods')]
        public function testNonReadMethodsAreNeverRedirected(string $method): void
        {
            $this->matchRule($this->rule('/old', 5, 301), new Page(5, '/new'));

            $this->assertSame($this->fallthrough, $this->dispatch(Request::create('/old', $method)));
            $this->assertSame(0, $this->lookups);
        }

        public static function unsafeMethods(): array
        {
            return [['POST'], ['PUT'], ['DELETE'], ['PATCH']];
        }

        public function testUnmatchedPathFallsThrough(): void
        {
            $this->matchRule($this->rule('/old', 5, 301), new Page(5, '/new'));

            $this->assertSame($this->fallthrough, $this->dispatch(Request::create('/something-else')));
        }

        public function testRuleWithMissingDestinationFallsThrough(): void
        {
            $this->matchRule($this->rule('/old', 5, 301), null);

            $this->assertSame($this->fallthrough, $this->dispatch(Request::create('/old')));
        }

        #[DataProvider('reservedRequests')]
        public function testReservedAndRootPathsSkipTheLookupEntirely(string $uri): void
        {
            $this->matchRule($this->rule('/dashboard', 5, 301), new Page(5, '/new'));

            $this->assertSame($this->fallthrough, $this->dispatch(Request::create($uri)));
            $this->assertSame(0, $this->lookups);
        }

        public static function reservedRequests(): array
        {
            return [['/'], ['/dashboard'], ['/dashboard/system/basics'], ['/ccm/system/x'], ['/login']];
        }

        public function testLookupFailuresFailOpen(): void
        {
            $response = $this->dispatch(Request::create('/old'), function () {
                throw new \RuntimeException('table LgtRedirections missing');
            });

            $this->assertSame($this->fallthrough, $response);
        }

        public function testServiceErrorsFailOpen(): void
        {
            $this->service->method('findBySourcePath')->willThrowException(new \RuntimeException('db gone'));

            $this->assertSame($this->fallthrough, $this->dispatch(Request::create('/old')));
        }

        protected function setUp(): void
        {
            Page::reset();
            $this->lookups = 0;
            $this->fallthrough = new Response('next');
            $this->service = $this->createMock(RedirectionService::class);
        }
    }
}
