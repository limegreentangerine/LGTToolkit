<?php

declare(strict_types=1);

namespace LgtToolkit\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LgtToolkit\Mail\SendEmailRequest;
use LgtToolkit\Page\AjaxPage\AjaxPageConfig;
use LgtToolkit\Page\AjaxPage\AjaxPageRequest;
use LgtToolkit\Page\AjaxPage\Enums\SortOrder;
use LgtToolkit\Page\AjaxPage\AjaxPageResponse;

final class AjaxPageValueObjectsTest extends TestCase
{
    public function testAjaxPageRequestCanBeHydratedFromArrayAndSerialized(): void
    {
        $request = AjaxPageRequest::fromArray([
            'pageNum' => 2,
            'perPage' => 12,
            'sortOrder' => 'date_desc',
        ]);

        $this->assertSame(2, $request->pageNum);
        $this->assertSame(12, $request->perPage);
        $this->assertSame(SortOrder::DateDesc, $request->sortOrder);

        $this->assertSame([
            'pageNum' => 2,
            'perPage' => 12,
            'sortOrder' => SortOrder::DateDesc,
        ], $request->toArray());
    }

    public function testAjaxPageConfigSerializesExpectedPayload(): void
    {
        $config = new AjaxPageConfig(
            startPage: 1,
            perPage: 10,
            sortOrder: SortOrder::NameAsc,
            cardPath: '/cards/test-card',
            pl: null,
            parent: null,
            debug: true,
            includeExclusions: false,
            noResultsMessage: 'No matching pages',
            pkg: null,
        );

        $this->assertSame([
            'startPage' => 1,
            'perPage' => 10,
            'sortOrder' => SortOrder::NameAsc,
            'cardPath' => '/cards/test-card',
            'pageList' => null,
            'parentPage' => null,
            'debug' => true,
            'includeExclusions' => false,
            'noResultsMessage' => 'No matching pages',
            'pkg' => null,
        ], $config->toArray());
    }

    public function testAjaxPageResponseSerializesResponsePayload(): void
    {
        $response = new AjaxPageResponse(
            pages: [],
            html: '<li>Item</li>',
            nextPageNum: 3,
            hasNextPage: true,
        );

        $this->assertSame([
            'pages' => [],
            'html' => '<li>Item</li>',
            'nextPageNum' => 3,
            'hasNextPage' => true,
        ], $response->toArray());
    }

    public function testSendEmailRequestStoresPayloadValues(): void
    {
        $request = new SendEmailRequest(
            template: 'welcome_email',
            args: ['first_name' => 'Lee'],
            body_template: 'base_email',
            template_loop: 'email_loop',
            pkg: null,
            testing: true,
        );

        $this->assertSame('welcome_email', $request->template);
        $this->assertSame(['first_name' => 'Lee'], $request->args);
        $this->assertSame('base_email', $request->body_template);
        $this->assertSame('email_loop', $request->template_loop);
        $this->assertNull($request->pkg);
        $this->assertTrue($request->testing);
    }

    public function testSortOrderEnumContainsExpectedValues(): void
    {
        $this->assertSame('sitemap_asc', SortOrder::SitemapAsc->value);
        $this->assertSame('date_desc', SortOrder::DateDesc->value);
        $this->assertSame('modified_date_desc', SortOrder::ModifiedDatedesc->value);
        $this->assertSame('name_desc', SortOrder::Namedesc->value);
    }
}
