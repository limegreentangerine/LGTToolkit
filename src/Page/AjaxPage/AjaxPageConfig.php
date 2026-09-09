<?php

namespace LgtToolkit\Page\AjaxPage;

use Page;
use PageList;
use LgtToolkit\Page\AjaxPage\Enums\SortOrder;

final readonly class AjaxPageConfig
{
    public function __construct(
        public int $startPage,
        public int $perPage,
        public SortOrder $sortOrder,
        public ?PageList $pl,
        public ?Page $parent,
        public ?bool $debug,
        public ?bool $includeExclusions,
        public ?string $noResultsMessage,
    ) {}

    public function toArray(): array
    {
        return [
            'startPage' => $this->startPage,
            'perPage' => $this->perPage,
            'sortOrder' => $this->sortOrder,
            'pageList' => $this->pl,
            'parentPage' => $this->parent,
            'debug' => $this->debug,
            'includeExclusions' => $this->includeExclusions,
            'noResultsMessage' => $this->noResultsMessage,
        ];
    }
}
