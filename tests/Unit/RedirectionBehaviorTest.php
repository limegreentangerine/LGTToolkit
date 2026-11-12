<?php

namespace LgtToolkit\Tests\Unit {
    require_once dirname(__DIR__, 2) . '/src/Concrete/Entity/Redirection.php';
    require_once dirname(__DIR__, 2) . '/src/Search/Column/Set/Redirections.php';

    use PHPUnit\Framework\TestCase;
    use Concrete\Package\LgtToolkit\Entity\Redirection;
    use LgtToolkit\Redirections\{CsvParser, PathNormaliser};
    use LgtToolkit\Search\Column\Set\Redirections as RedirectionColumns;

    final class RedirectionBehaviorTest extends TestCase
    {
        public function testNormaliseProducesLowercaseLeadingSlashPathWithoutTrailingSlashOrQuery(): void
        {
            $this->assertSame('/old/page', PathNormaliser::normalise('  Old//Page/?utm=1#top '));
            $this->assertSame('/old', PathNormaliser::normalise('old'));
        }

        public function testNormaliseAcceptsFullUrlsOnlyForTheSiteHost(): void
        {
            $this->assertSame('/about', PathNormaliser::normalise('https://Example.com/About/?a=1', 'example.com'));
            $this->assertNull(PathNormaliser::normalise('https://other.com/about', 'example.com'));
            $this->assertSame('/about', PathNormaliser::normalise('//example.com/about', 'example.com'));
        }

        public function testNormaliseRejectsUnusableInput(): void
        {
            foreach (['', '   ', '/', '//', '?a=1', '/a/../b', '/a b', "/a\x00b"] as $input) {
                $this->assertNull(PathNormaliser::normalise($input), var_export($input, true));
            }
        }

        public function testReservedPathsIncludeTheirChildrenButNotLookalikes(): void
        {
            $this->assertTrue(PathNormaliser::isReserved('/dashboard'));
            $this->assertTrue(PathNormaliser::isReserved('/dashboard/system'));
            $this->assertTrue(PathNormaliser::isReserved('/ccm/system/foo'));
            $this->assertFalse(PathNormaliser::isReserved('/dashboards'));
            $this->assertFalse(PathNormaliser::isReserved('/about'));
        }

        public function testCsvParserSkipsHeaderBlankLinesAndBom(): void
        {
            $csv = "\xEF\xBB\xBFsource,destination,code\r\n/a,/b,301\r\n\r\n/c,/d,\r\n";

            $result = CsvParser::parse($csv);

            $this->assertSame([], $result['errors']);
            $this->assertSame([
                ['line' => 2, 'source' => '/a', 'destination' => '/b', 'code' => '301'],
                ['line' => 4, 'source' => '/c', 'destination' => '/d', 'code' => ''],
            ], $result['rows']);
        }

        public function testCsvParserDetectsSemicolonsAndHandlesFilesWithoutHeader(): void
        {
            $result = CsvParser::parse("/a;/b\n\"/c;x\";/d;302\n");

            $this->assertCount(2, $result['rows']);
            $this->assertSame('/a', $result['rows'][0]['source']);
            $this->assertSame('/c;x', $result['rows'][1]['source']);
            $this->assertSame('302', $result['rows'][1]['code']);
        }

        public function testCsvParserCapsRowCount(): void
        {
            $csv = str_repeat("/a,/b\n", CsvParser::MAX_ROWS + 5);

            $result = CsvParser::parse($csv);

            $this->assertCount(CsvParser::MAX_ROWS, $result['rows']);
            $this->assertNotSame([], $result['errors']);
        }

        public function testSampleCsvRoundTripsThroughTheParser(): void
        {
            $this->assertCount(3, CsvParser::parse(CsvParser::sample())['rows']);
        }

        public function testRedirectionDefaultsToTemporaryRedirect(): void
        {
            $redirection = new Redirection();

            $this->assertSame(302, $redirection->getStatusCode());
            $this->assertSame([301, 302], Redirection::STATUS_CODES);
            $this->assertInstanceOf(\ClassKit\Entity\Core\UpdatedGuidEntity::class, $redirection);
            $this->assertSame($redirection->getDateCreated(), $redirection->getDateUpdated());
        }

        public function testSearchColumnSetSortsBySourcePathByDefault(): void
        {
            $columns = new RedirectionColumns();

            $this->assertSame(
                ['r.sourcePath', 'r.statusCode', 'r.date_created'],
                array_map(fn($column) => $column->getColumnKey(), $columns->getColumns()),
            );
            $this->assertSame('r.sourcePath', $columns->getDefaultSortColumn()->getColumnKey());
        }
    }
}
