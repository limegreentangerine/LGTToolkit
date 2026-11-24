<?php

namespace LgtToolkit\Support\Diagnostics;

final readonly class Diagnostics
{
    /**
     * @param array<string, array<string, string>> $sections     heading => label => value
     * @param string                               $recentErrors redacted log lines, "none" or "unavailable"
     */
    public function __construct(
        public array $sections,
        public string $recentErrors,
    ) {}

    public function toText(): string
    {
        $blocks = [];
        foreach ($this->sections as $heading => $rows) {
            $lines = [$heading . ':'];
            foreach ($rows as $label => $value) {
                $lines[] = '  ' . $label . ': ' . $value;
            }
            $blocks[] = implode("\n", $lines);
        }

        return implode("\n\n", $blocks);
    }
}
