<?php

namespace LgtToolkit\Redirections;

/**
 * Parses an uploaded redirections CSV: source, destination path, optional status code.
 */
class CsvParser
{
    public const MAX_ROWS = 5000;

    protected static function detectDelimiter(string $line): string
    {
        $best = ',';
        $max = 0;

        foreach ([',', ';', "\t"] as $candidate) {
            $count = substr_count($line, $candidate);

            if ($count > $max) {
                $max = $count;
                $best = $candidate;
            }
        }

        return $best;
    }

    protected static function isBlank(array $cells): bool
    {
        foreach ($cells as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{rows: array<int, array{line: int, source: string, destination: string, code: string}>, errors: array<int, string>}
     */
    public static function parse(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        $firstLine = strtok($content, "\n") ?: '';
        $delimiter = self::detectDelimiter($firstLine);

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $rows = [];
        $errors = [];
        $line = 0;
        $first = true;

        while (($cells = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
            $line++;

            if ($cells === [null] || self::isBlank($cells)) {
                continue;
            }

            $cells = array_map(static fn($cell) => trim((string) $cell), $cells);

            if ($first) {
                $first = false;

                if (preg_match('/^(source|from|old)/i', $cells[0])) {
                    continue;
                }
            }

            if (count($rows) >= self::MAX_ROWS) {
                $errors[] = sprintf('Only the first %d rows were read.', self::MAX_ROWS);
                break;
            }

            $rows[] = [
                'line' => $line,
                'source' => $cells[0],
                'destination' => $cells[1] ?? '',
                'code' => $cells[2] ?? '',
            ];
        }

        fclose($stream);

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * Sample file offered for download from the dashboard page.
     */
    public static function sample(): string
    {
        return "source,destination,code\n/old-about-us,/about-us,302\n/old-contact,/contact,301\n/old-news,/news,\n";
    }
}
