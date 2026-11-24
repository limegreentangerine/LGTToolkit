<?php

namespace LgtToolkit\Support;

/**
 * A validated attachment: either an uploaded temp file (path) or bytes loaded from the queue (content).
 */
final readonly class Attachment
{
    public function __construct(
        public string $filename,
        public string $mime,
        public int $size,
        public ?string $path = null,
        public ?string $content = null,
    ) {}

    public function contents(): string
    {
        if ($this->content !== null) {
            return $this->content;
        }

        $data = $this->path !== null ? @file_get_contents($this->path) : false;
        if ($data === false) {
            throw new \RuntimeException('Attachment could not be read.');
        }

        return $data;
    }
}
