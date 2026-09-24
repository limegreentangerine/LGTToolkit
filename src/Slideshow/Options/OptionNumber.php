<?php

namespace LgtToolkit\Slideshow\Options;

final readonly class OptionNumber
{
    public function __construct(
        public int $mobile,
        public int $desktop,
        public int $hd,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            mobile: $data['mobile'] ?? 0,
            desktop: $data['desktop'] ?? 0,
            hd: $data['hd'] ?? 0,
        );
    }

    public function toArray(): array
    {
        return [
            'mobile' => $this->mobile,
            'desktop' => $this->desktop,
            'hd' => $this->hd,
        ];
    }
}
