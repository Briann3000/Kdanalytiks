<?php

namespace App\Services\Import;

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;
use Illuminate\Http\UploadedFile;

class ExcelImportParser implements ToArray
{
    use CleansImportValues;

    protected array $rows = [];

    public function array(array $array): void
    {
        $this->rows = $array;
    }

    /**
     * Accept an optional codebook mapping array keyed by VAR code.
     * When provided, headers that match a codebook key get their
     * label replaced with the mapped label.
     *
     * @var array<string, string>|null
     */
    protected ?array $codebook = null;

    /**
     * Set a codebook mapping (VAR code → human label).
     */
    public function setCodebook(?array $codebook): void
    {
        $this->codebook = $codebook;
    }

    /**
     * Parse an Excel (.xlsx/.xls) or CSV file.
     * First row is treated as headers (question labels).
     *
     * Returns same structure as SpssImportParser:
     * [
     *   'variables' => [ ['name' => 'col_0', 'label' => 'Gender', 'value_labels' => [], ...], ... ],
     *   'rows'      => [ [val1, val2, ...], ... ],
     *   'count'     => N,
     * ]
     */
    public function parse(string $filePath): array
    {
        Excel::import($this, $filePath);

        if (empty($this->rows)) {
            return ['variables' => [], 'rows' => [], 'count' => 0];
        }

        // ── Header row detection ───────────────────────────────────────────────
        // Pop the first row as candidate headers.
        $candidateHeaders = array_shift($this->rows);
        $dataRows = $this->rows;

        // Normalise candidate headers to strings for inspection
        $cleanedHeaders = array_map(fn($h) => trim((string) ($h ?? '')), $candidateHeaders);

        // Detect whether row 1 is actually a header or just data.
        // Signal 1: all non-blank cells are numeric → definitely data
        // Signal 2: the candidate header values also appear in row 2 → row 1 is data
        $nonBlank = array_filter($cleanedHeaders, fn($h) => $h !== '');
        $allNumeric = count($nonBlank) > 0
            && count(array_filter($nonBlank, 'is_numeric')) === count($nonBlank);

        $looksLikeData = $allNumeric || empty($nonBlank);

        // If row 2 exists, check value overlap: if >50% of row-1 values appear
        // somewhere in row 2, row 1 is almost certainly a data row, not headers.
        if (!$looksLikeData && !empty($dataRows)) {
            $row2 = array_map(fn($v) => trim((string) ($v ?? '')), array_values((array) ($dataRows[0] ?? [])));
            $overlapCount = count(array_intersect($nonBlank, array_filter($row2, fn($v) => $v !== '')));
            if ($overlapCount > 0 && ($overlapCount / count($nonBlank)) > 0.5) {
                $looksLikeData = true;
            }
        }

        if ($looksLikeData) {
            // Row 1 was data — put it back and use positional Column_N as headers
            array_unshift($dataRows, $candidateHeaders);
            $cleanedHeaders = []; // will be filled with Column_N below
        }

        // ── Clean data rows ────────────────────────────────────────────────────
        $dataRows = array_map(function ($row) {
            return array_map(fn($cell) => $this->cleanValue($cell), array_values((array) $row));
        }, $dataRows);

        // Drop fully-blank trailing rows (PHPSpreadsheet emits these)
        $dataRows = array_values(array_filter(
            $dataRows,
            fn($row) =>
            count(array_filter($row, fn($v) => $v !== null && $v !== '')) > 0
        ));

        // Determine number of columns from data if headers were absent
        $colCount = !empty($cleanedHeaders)
            ? count($cleanedHeaders)
            : (isset($dataRows[0]) ? count($dataRows[0]) : 0);

        // ── Build variables from header row (or positional fallbacks) ──────────
        $variables = [];
        for ($i = 0; $i < $colCount; $i++) {
            $header = $cleanedHeaders[$i] ?? '';
            if ($header === '') {
                $header = 'Column_' . ($i + 1);
            }

            // Derive a readable slug from the header string for 'name'
            // e.g. "1. Gender" → "1_gender",  "Age Group" → "age_group"
            $nameSlug = preg_replace('/[^a-z0-9]+/', '_', strtolower($header));
            $nameSlug = trim($nameSlug, '_');
            if ($nameSlug === '') {
                $nameSlug = 'col_' . $i;
            }

            // Detect if the header looks like a raw SPSS variable code (VAR00001)
            $looksLikeSpssCode = (bool) preg_match('/^VAR\d+$|^[A-Z_]+[0-9]+$/', $header);

            // If a codebook is set, try to resolve the label
            $label = $header;
            if ($this->codebook && isset($this->codebook[$header])) {
                $label = $this->codebook[$header];
                $looksLikeSpssCode = false;
            }

            // Infer value_labels from the data column (≤15 distinct non-blank values)
            $uniqueValues = array_unique(array_column($dataRows, $i));
            $uniqueValues = array_filter($uniqueValues, fn($v) => $v !== null && $v !== '');
            sort($uniqueValues);

            $valueLabels = [];
            if (count($uniqueValues) <= 15 && count($uniqueValues) > 0) {
                foreach ($uniqueValues as $val) {
                    $valueLabels[(string) $val] = (string) $val;
                }
            }

            $variables[] = [
                'name' => $nameSlug,
                'label' => $label,
                'value_labels' => $valueLabels,
                'measure' => 0,
                'var_index' => $i,
                'looks_like_spss_code' => $looksLikeSpssCode,
            ];
        }

        return [
            'variables' => $variables,
            'rows' => $dataRows,
            'count' => count($dataRows),
        ];
    }
}
