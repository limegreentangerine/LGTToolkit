<?php

namespace LgtToolkit\Slideshow;

use LgtToolkit\Slideshow\Enums\SnapOptions;
use LgtToolkit\Slideshow\Options\OptionNumber;
use LgtToolkit\Slideshow\Options\OptionBoolean;
use LgtToolkit\Slideshow\Options\OptionAutoplay;

final readonly class Options
{
    public function __construct(
        public int $mobile,
        public int $desktop,
        public int $hd,
        public bool $draggable,
        public SnapOptions $snap,
        public OptionNumber $gap,
        public OptionNumber $peek,
        public OptionBoolean $showButtons,
        public OptionBoolean $showPagination,
        public ?string $prevIcon,
        public ?string $nextIcon,
        public ?OptionAutoplay $autoplay,
    ) {}

    public function toArray(): array
    {
        return [
            'mobile' => $this->mobile,
            'desktop' => $this->desktop,
            'hd' => $this->hd,
            'draggable' => $this->draggable,
            'snap' => $this->snap->value,
            'gap' => $this->gap->toArray(),
            'peek' => $this->peek->toArray(),
            'showButtons' => $this->showButtons->toArray(),
            'showPagination' => $this->showPagination->toArray(),
            'prevIcon' => $this->prevIcon,
            'nextIcon' => $this->nextIcon,
            'autoplay' => $this->autoplay?->toArray(),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            mobile: $data['mobile'],
            desktop: $data['desktop'],
            hd: $data['hd'],
            draggable: $data['draggable'],
            snap: SnapOptions::from($data['snap']),
            gap: OptionNumber::fromArray($data['gap'] ?? []),
            peek: OptionNumber::fromArray($data['peek'] ?? []),
            showButtons: OptionBoolean::fromArray($data['showButtons'] ?? []),
            showPagination: OptionBoolean::fromArray($data['showPagination'] ?? []),
            prevIcon: $data['prevIcon'] ?? 'bi-chevron-left',
            nextIcon: $data['nextIcon'] ?? 'bi-chevron-right',
            autoplay: OptionAutoplay::fromArray($data['autoplay'] ?? null),
        );
    }
}
