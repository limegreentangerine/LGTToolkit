<?php

namespace LgtToolkit\Page\AjaxPage;

use Page;
use PageList;
use LgtToolkit\Page\AjaxPage\Enums\SortOrder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class AjaxPage
{
    protected AjaxPageResponse $response;
    protected PageList $pl;
    protected ?Page $parent;
    protected int $currentPage;
    protected int $perPage;
    protected SortOrder $sortOrder;
    protected bool $includeExclusions;
    protected string $noResultsMessage;
    protected bool $debug;

    public function __construct(AjaxPageConfig $options)
    {
        $this->pl = $options->pl ?? new PageList();
        $this->parent = $options->parent;
        $this->currentPage = $options->startPage;
        $this->perPage = $options->perPage;
        $this->sortOrder = $options->sortOrder;
        $this->includeExclusions = $options->includeExclusions ?? false;
        $this->debug = $options->debug ?? false;
        $this->noResultsMessage = t($options->noResultsMessage) ?? t('No articles found');

        $this->build();
    }

    protected function build(): void
    {
        if ($this->debug === true) {
            $this->getPageList()->debug();
        }

        if ($this->parent && $this->parent instanceof Page) {
            $this->getPageList()->filterByParentID($this->parent->getCollectionID());
        }

        $this->getPageList()->filter('cvName', '', '!=');
        $this->getPageList()->filterByExcludePageList($this->includeExclusions);
        $this->getPageList()->setItemsPerPage($this->perPage);
        $this->setSortOrder();
    }

    protected function setSortOrder()
    {
        switch ($this->sortOrder) {
            case 'sitemap_asc':
                $this->getPageList()->sortByDisplayOrder();
                break;
            case 'sitemap_desc':
                $this->getPageList()->sortByDisplayOrderDescending();
                break;
            case 'date_asc':
                $this->getPageList()->sortByPublicDate();
                break;
            case 'modified_date_asc':
                $this->getPageList()->sortByDateModified();
                break;
            case 'modified_date_desc':
                $this->getPageList()->sortByDateModifiedDescending();
                break;
            case 'random':
                $this->getPageList()->sortBy('RAND()');
                break;
            case 'name_asc':
                $this->getPageList()->sortByName();
                break;
            case 'name_desc':
                $this->getPageList()->sortByNameDescending();
                break;
            case 'date_desc':
            default:
                $this->getPageList()->sortByPublicDateDescending();
                break;
        }
    }

    public function getPageList(): PageList
    {
        return $this->pl;
    }

    public function getNextPage(): Response
    {
        $request = AjaxPageRequest::fromArray($_GET);

        $this->currentPage = (isset($request->pageNum)) ? $request->pageNum : $this->currentPage;

        if ($request->perPage) {
            $this->perPage = $request->perPage;
            $this->getPageList()->setItemsPerPage($this->perPage);
        }

        if ($request->sortOrder) {
            $this->sortOrder = $request->sortOrder;
            $this->setSortOrder();
        }

        $pagination = $this->getPageList()->getPagination();
        $pagination->setCurrentPage($this->currentPage);
        $pages = $pagination->getCurrentPageResults();

        $this->response = new AjaxPageResponse(
            pages: $pages,
            nextPageNum: ($pagination->hasNextPage()) ? ($this->currentPage + 1) : 0,
            hasNextPage: $pagination->hasNextPage(),
        );

        return new JsonResponse($this->response->toArray(), 200);
    }
}
