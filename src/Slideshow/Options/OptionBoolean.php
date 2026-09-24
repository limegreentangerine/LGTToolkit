<?php

namespace LgtToolkit\Slideshow\Options;

final readonly class OptionBoolean
{
    public function __construct(
        public bool $mobile,
        public bool $desktop,
        public bool $hd,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            mobile: $data['mobile'] ?? false,
            desktop: $data['desktop'] ?? false,
            hd: $data['hd'] ?? false,
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
