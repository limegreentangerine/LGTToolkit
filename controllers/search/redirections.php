<?php

namespace Concrete\Package\LgtToolkit\Controller\Search;

use Concrete\Core\Search\StickyRequest;
use Concrete\Core\Controller\AbstractController;
use LgtToolkit\Search\{Column\Set\Redirections as ColumnSet, ItemList\Redirections as SearchList, Result\Redirections as SearchResult};

class Redirections extends AbstractController
{
    private $stickyRequest;

    private $searchList;

    private $searchResult;

    protected function getSearchList()
    {
        if ($this->searchList === null) {
            $this->searchList = $this->app->make(SearchList::class, [$this->getStickyRequest()]);
        }

        return $this->searchList;
    }

    public function getStickyRequest()
    {
        if ($this->stickyRequest === null) {
            $this->stickyRequest = new StickyRequest('lgt.redirections');
        }

        return $this->stickyRequest;
    }

    public function getAllowedPaginationSizes()
    {
        return [
            10,
            20,
            50,
            100,
        ];
    }

    public function getDefaultPaginationSize()
    {
        return $this->getAllowedPaginationSizes()[1];
    }

    public function search($reset = false)
    {
        $stickyRequest = $this->getStickyRequest();
        $searchList = $this->getSearchList();

        if ($reset) {
            $stickyRequest->resetSearchRequest();
        }

        $columnSet = new ColumnSet();

        if (!$searchList->getActiveSortColumn()) {
            $sortColumn = $columnSet->getDefaultSortColumn();
            $searchList->sanitizedSortBy($sortColumn->getColumnKey(), $sortColumn->getColumnDefaultSortDirection());
        }

        $vnumbers = $this->app->make('helper/validation/numbers');
        $req = $stickyRequest->getSearchRequest();

        $q = $req['source'] ?? null;
        if (is_string($q) && $q !== '') {
            $searchList->filterBySourcePath($q);
        }

        $q = $req['code'] ?? null;
        if (is_string($q) && $q !== '') {
            $searchList->filterByStatusCode($q);
        }

        $paginationSize = null;
        $q = $req['num_results'] ?? null;
        if ($q && $vnumbers->integer($q) && in_array((int) $q, $this->getAllowedPaginationSizes(), true)) {
            $paginationSize = (int) $q;
        }

        $searchList->setItemsPerPage($paginationSize ?? $this->getDefaultPaginationSize());

        $this->searchResult = new SearchResult(
            $columnSet,
            $searchList,
            $this->app->make('url/manager')->resolve(['dashboard/lgt_toolkit/redirections/']),
        );
    }

    /**
     * Get the search result (once the search() method has been called).
     *
     * @return SearchResult|null
     */
    public function getSearchResultObject()
    {
        return $this->searchResult;
    }
}
