<?php

namespace LgtToolkit\Tests\Unit {
    require_once dirname(__DIR__, 2) . '/src/Concrete/Entity/Redirection.php';

    use Page;
    use Request;
    use Doctrine\ORM\EntityManager;
    use PHPUnit\Framework\TestCase;
    use Doctrine\ORM\EntityRepository;
    use Concrete\Core\Entity\Page\PagePath;
    use PHPUnit\Framework\Attributes\DataProvider;
    use LgtToolkit\Redirections\RedirectionService;
    use Concrete\Core\Database\Connection\Connection;
    use LgtToolkit\Redirections\RedirectionException;
    use Concrete\Package\LgtToolkit\Entity\Redirection;

    final class RedirectionServiceBehaviorTest extends TestCase
    {
        /** @var array<string, Redirection> */
        private array $rules = [];

        /** @var array<int, PagePath> */
        private array $pagePaths = [];

        /** @var array<string, array{ppID: int, cID: int}> */
        private array $dbPaths = [];

        /** @var list<object> */
        private array $persisted = [];

        /** @var list<object> */
        private array $removed = [];

        private int $nextRuleID = 1;

        private RedirectionService $service;

        private function page(int $cID, string $path = '', array $overrides = []): Page
        {
            $page = new Page($cID, $path ?: '/page-' . $cID);

            foreach ($overrides as $property => $value) {
                $page->$property = $value;
            }

            Page::$byId[$cID] = $page;
            Page::$byPath[$page->path] = $page;

            return $page;
        }

        private function rule(string $source, int $cID, ?int $ppID = null, int $status = 302): Redirection
        {
            $rule = (new Redirection())->setSourcePath($source)->setPageID($cID)->setPagePathID($ppID)->setStatusCode($status);
            $id = 'rule-' . $this->nextRuleID++;
            (new \ReflectionProperty(Redirection::class, 'id'))->setValue($rule, $id);
            $this->rules[$id] = $rule;

            return $rule;
        }

        private function pagePath(int $ppID, bool $canonical = false): PagePath
        {
            $path = $this->createMock(PagePath::class);
            $path->method('isPagePathCanonical')->willReturn($canonical);

            return $this->pagePaths[$ppID] = $path;
        }

        public function testCreateNormalisesSourceDefaultsStatusAndRegistersPagePath(): void
        {
            $page = $this->page(5, '/new');

            $rule = $this->service->create(' Old//Page/?utm=1 ', 5);

            $this->assertSame('/old/page', $rule->getSourcePath());
            $this->assertSame(5, $rule->getPageID());
            $this->assertSame(302, $rule->getStatusCode());
            $this->assertSame(100, $rule->getPagePathID());
            $this->assertSame(['/old/page'], $page->addedPaths);
            $this->assertSame([$rule], $this->persisted);
        }

        public function testCreateAcceptsPermanentStatusAsStringOrInt(): void
        {
            $this->page(5, '/new');

            $this->assertSame(301, $this->service->create('/a', 5, '301')->getStatusCode());
            $this->assertSame(301, $this->service->create('/b', 5, 301)->getStatusCode());
        }

        public function testCreateTreatsBlankStatusAsDefault(): void
        {
            $this->page(5, '/new');

            $this->assertSame(302, $this->service->create('/a', 5, '  ')->getStatusCode());
        }

        #[DataProvider('invalidStatusCodes')]
        public function testCreateRejectsUnsupportedStatusCodes(int|string $code): void
        {
            $this->page(5, '/new');

            $this->expectException(RedirectionException::class);
            $this->expectExceptionMessage('301 or 302');

            $this->service->create('/a', 5, $code);
        }

        public static function invalidStatusCodes(): array
        {
            return [['307'], [404], ['abc'], ['30x'], ['-301']];
        }

        public function testCreateRejectsUnusableSource(): void
        {
            $this->page(5, '/new');

            $this->expectException(RedirectionException::class);
            $this->expectExceptionMessage('not a valid path');

            $this->service->create('https://other.com/x', 5);
        }

        public function testCreateAcceptsFullUrlOnSiteHost(): void
        {
            $this->page(5, '/new');

            $this->assertSame('/old', $this->service->create('https://Example.com/Old/', 5)->getSourcePath());
        }

        public function testCreateRejectsReservedSource(): void
        {
            $this->page(5, '/new');

            $this->expectException(RedirectionException::class);
            $this->expectExceptionMessage('reserved system path');

            $this->service->create('/dashboard/system', 5);
        }

        #[DataProvider('unusableDestinations')]
        public function testCreateRejectsUnusableDestinationPages(array $overrides): void
        {
            $this->page(5, '/new', $overrides);

            $this->expectException(RedirectionException::class);
            $this->expectExceptionMessage('existing, published page');

            $this->service->create('/old', 5);
        }

        public static function unusableDestinations(): array
        {
            return [
                'error' => [['error' => true]],
                'trashed' => [['trashed' => true]],
                'admin area' => [['adminArea' => true]],
                'system page' => [['systemPage' => true]],
                'external link' => [['externalLink' => 'https://elsewhere.test']],
            ];
        }

        public function testCreateRejectsMissingDestinationPage(): void
        {
            $this->expectException(RedirectionException::class);
            $this->expectExceptionMessage('existing, published page');

            $this->service->create('/old', 999);
        }

        public function testCreateRejectsSourceMatchingDestinationAddress(): void
        {
            $this->page(5, '/About');

            $this->expectException(RedirectionException::class);
            $this->expectExceptionMessage('already the address of the destination page');

            $this->service->create('/about', 5);
        }

        public function testCreateRejectsDuplicateSource(): void
        {
            $this->page(5, '/new');
            $this->rule('/old', 5);

            $this->expectException(RedirectionException::class);
            $this->expectExceptionMessage('already exists');

            $this->service->create('/OLD/', 5);
        }

        public function testCreateRejectsSourceUsedByExistingPage(): void
        {
            $this->page(5, '/new');
            $this->dbPaths['/taken'] = ['ppID' => 7, 'cID' => 9];

            $this->expectException(RedirectionException::class);
            $this->expectExceptionMessage('already used by a page');

            $this->service->create('/taken', 5);
        }

        public function testFailedValidationPersistsNothing(): void
        {
            $page = $this->page(5, '/new');

            try {
                $this->service->create('/dashboard', 5);
                $this->fail('Expected a RedirectionException.');
            } catch (RedirectionException) {
            }

            $this->assertSame([], $this->persisted);
            $this->assertSame([], $page->addedPaths);
        }

        public function testUpdateOfMissingRuleFails(): void
        {
            $this->expectException(RedirectionException::class);
            $this->expectExceptionMessage('no longer exists');

            $this->service->update('nope', '/a', 5);
        }

        public function testUpdateKeepsPagePathWhenOnlyStatusChanges(): void
        {
            $page = $this->page(5, '/new');
            $rule = $this->rule('/old', 5, 40);
            $this->dbPaths['/old'] = ['ppID' => 40, 'cID' => 5];

            $updated = $this->service->update($rule->getID(), '/old', 5, 301);

            $this->assertSame(301, $updated->getStatusCode());
            $this->assertSame(40, $updated->getPagePathID());
            $this->assertSame([], $page->addedPaths);
            $this->assertSame([], $this->removed);
        }

        public function testUpdateMovingSourceReplacesNonCanonicalPagePath(): void
        {
            $page = $this->page(5, '/new');
            $rule = $this->rule('/old', 5, 40);
            $oldPath = $this->pagePath(40);

            $updated = $this->service->update($rule->getID(), '/older', 5);

            $this->assertSame('/older', $updated->getSourcePath());
            $this->assertSame(100, $updated->getPagePathID());
            $this->assertSame(['/older'], $page->addedPaths);
            $this->assertSame([$oldPath], $this->removed);
        }

        public function testUpdateMovingDestinationRepointsRule(): void
        {
            $this->page(5, '/new');
            $other = $this->page(6, '/other');
            $rule = $this->rule('/old', 5, 40);
            $this->pagePath(40);

            $updated = $this->service->update($rule->getID(), '/old', 6);

            $this->assertSame(6, $updated->getPageID());
            $this->assertSame(['/old'], $other->addedPaths);
        }

        public function testUpdateMayKeepItsOwnSourceButNotTakeAnotherRulesSource(): void
        {
            $this->page(5, '/new');
            $first = $this->rule('/one', 5);
            $second = $this->rule('/two', 5);

            $this->assertSame('/one', $this->service->update($first->getID(), '/one', 5)->getSourcePath());

            $this->expectException(RedirectionException::class);
            $this->expectExceptionMessage('already exists');

            $this->service->update($second->getID(), '/one', 5);
        }

        public function testUpdateAllowsReusingItsOwnPagePathRow(): void
        {
            $this->page(5, '/new');
            $rule = $this->rule('/old', 5, 40);
            $this->dbPaths['/old'] = ['ppID' => 40, 'cID' => 5];

            $this->assertSame('/old', $this->service->update($rule->getID(), '/old', 5)->getSourcePath());
        }

        public function testDeleteRemovesRuleAndItsNonCanonicalPagePath(): void
        {
            $rule = $this->rule('/old', 5, 40);
            $path = $this->pagePath(40);

            $this->assertTrue($this->service->delete($rule->getID()));
            $this->assertContains($path, $this->removed);
            $this->assertContains($rule, $this->removed);
        }

        public function testDeleteNeverRemovesACanonicalPagePath(): void
        {
            $rule = $this->rule('/old', 5, 40);
            $path = $this->pagePath(40, true);

            $this->assertTrue($this->service->delete($rule->getID()));
            $this->assertNotContains($path, $this->removed);
            $this->assertContains($rule, $this->removed);
        }

        public function testDeleteOfMissingRuleReturnsFalse(): void
        {
            $this->assertFalse($this->service->delete('nope'));
            $this->assertSame([], $this->removed);
        }

        public function testResolveDestinationReturnsLivePageOnly(): void
        {
            $page = $this->page(5, '/new');
            $this->page(6, '/trashed', ['trashed' => true]);

            $this->assertSame($page, $this->service->resolveDestination($this->rule('/a', 5)));
            $this->assertNull($this->service->resolveDestination($this->rule('/b', 6)));
            $this->assertNull($this->service->resolveDestination($this->rule('/c', 404)));
        }

        public function testFindBySourcePathAndGetByID(): void
        {
            $rule = $this->rule('/old', 5);

            $this->assertSame($rule, $this->service->findBySourcePath('/old'));
            $this->assertNull($this->service->findBySourcePath('/missing'));
            $this->assertSame($rule, $this->service->getByID($rule->getID()));
            $this->assertNull($this->service->getByID('missing'));
        }

        public function testFindDestinationPageAcceptsPathsAndSiteUrlsOnly(): void
        {
            $page = $this->page(5, '/about');

            $this->assertSame($page, $this->service->findDestinationPage('/About/'));
            $this->assertSame($page, $this->service->findDestinationPage('https://example.com/about?x=1'));
            $this->assertNull($this->service->findDestinationPage('https://other.com/about'));
            $this->assertNull($this->service->findDestinationPage('/missing'));
            $this->assertNull($this->service->findDestinationPage(''));
        }

        public function testImportCreatesValidRowsAndReportsBadOnesWithLineNumbers(): void
        {
            $this->page(5, '/new');
            $this->page(6, '/trashed', ['trashed' => true]);

            $csv = implode("\n", [
                'source,destination,code',
                '/old-a,/new,301',
                '/old-b,/missing,',
                '/old-c,/trashed,',
                '/old-d,/new,307',
                '/dashboard,/new,',
                '/old-e,/new,',
            ]);

            $result = $this->service->importCsv($csv);

            $this->assertSame(2, $result['created']);
            $this->assertSame(
                [3, 4, 5, 6],
                array_column($result['errors'], 'line'),
            );
            $this->assertSame(
                ['/old-b', '/old-c', '/old-d', '/dashboard'],
                array_column($result['errors'], 'source'),
            );
            $this->assertCount(2, $this->persisted);
            $this->assertSame(301, $this->persisted[0]->getStatusCode());
            $this->assertSame(302, $this->persisted[1]->getStatusCode());
        }

        public function testImportFlagsDuplicateSourcesWithinTheFile(): void
        {
            $this->page(5, '/new');

            $result = $this->service->importCsv("/old,/new\n/OLD/,/new\n");

            $this->assertSame(1, $result['created']);
            $this->assertCount(1, $result['errors']);
            $this->assertSame(2, $result['errors'][0]['line']);
            $this->assertStringContainsString('line 1', $result['errors'][0]['message']);
        }

        public function testImportReportsFileLevelParseErrorsOnLineZero(): void
        {
            $this->page(5, '/new');

            $csv = str_repeat("/a,/new\n", \LgtToolkit\Redirections\CsvParser::MAX_ROWS + 1);

            $result = $this->service->importCsv($csv);

            $this->assertSame(0, $result['errors'][0]['line']);
        }

        public function testImportReportsExistingRuleAsAnError(): void
        {
            $this->page(5, '/new');
            $this->rule('/old', 5);

            $result = $this->service->importCsv("/old,/new\n");

            $this->assertSame(0, $result['created']);
            $this->assertStringContainsString('already exists', $result['errors'][0]['message']);
        }

        protected function setUp(): void
        {
            Page::reset();
            Request::$host = 'example.com';
            $this->rules = $this->pagePaths = $this->dbPaths = $this->persisted = $this->removed = [];

            $repository = $this->createMock(EntityRepository::class);
            $repository->method('findOneBy')->willReturnCallback(function (array $criteria) {
                foreach ($this->rules as $rule) {
                    if ($rule->getSourcePath() === $criteria['sourcePath']) {
                        return $rule;
                    }
                }

                return null;
            });

            $em = $this->createMock(EntityManager::class);
            $em->method('getRepository')->willReturn($repository);
            $em->method('find')->willReturnCallback(fn(string $class, $id) => match ($class) {
                Redirection::class => $this->rules[$id] ?? null,
                PagePath::class => $this->pagePaths[$id] ?? null,
                default => null,
            });
            $em->method('wrapInTransaction')->willReturnCallback(fn(callable $func) => $func());
            $em->method('persist')->willReturnCallback(function (object $entity) {
                $this->persisted[] = $entity;
            });
            $em->method('remove')->willReturnCallback(function (object $entity) {
                $this->removed[] = $entity;

                if ($entity instanceof Redirection) {
                    unset($this->rules[$entity->getID()]);
                }
            });

            $db = $this->createMock(Connection::class);
            $db->method('fetchAssociative')->willReturnCallback(fn(string $sql, array $params) => $this->dbPaths[$params[0]] ?? false);

            $this->service = new RedirectionService($em, $db);
        }
    }
}
