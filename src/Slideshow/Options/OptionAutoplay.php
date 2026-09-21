<?php

namespace LgtToolkit\Slideshow\Options;

final readonly class OptionAutoplay
{
    public function __construct(
        public bool $enabled,
        public int $speed,
    ) {}

    public static function fromArray(?array $data): self
    {
        return new self(
            enabled: $data['enabled'] ?? false,
            speed: $data['speed'] ?? 0,
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'speed' => $this->speed,
        ];
    }

    public function speedInMilliseconds(): int
    {
        return ceil($this->speed * 1000);
    }
}
