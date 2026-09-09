<?php

namespace LgtToolkit\Page\AjaxPage;

use LgtToolkit\Page\AjaxPage\Enums\SortOrder;

final readonly class AjaxPageRequest
{
    public function __construct(
        public int $pageNum,
        public ?int $perPage,
        public ?SortOrder $sortOrder,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            pageNum: $data['pageNum'],
            perPage: $data['perPage'] ?? null,
            sortOrder: isset($data['sortOrder'])
                ? SortOrder::from($data['sortOrder'])
                : null,
        );
    }

    public function toArray(): array
    {
        return [
            'pageNum' => $this->pageNum,
            'perPage' => $this->perPage ?? null,
            'sortOrder' => $this->sortOrder ?? null,
        ];
    }
}
