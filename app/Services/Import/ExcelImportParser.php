<?php

namespace App\Services\Import;

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;
use Illuminate\Http\UploadedFile;

class ExcelImportParser implements ToArray
{
    protected array $rows = [];

    public function array(array $array): void
    {
        $this->rows = $array;
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

        $headers = array_shift($this->rows); // First row = column headers
        $dataRows = $this->rows;

        // Build variables list from header row
        $variables = [];
        foreach ($headers as $i => $header) {
            $header = trim((string) ($header ?? ''));
            if ($header === '') {
                $header = 'Column_' . ($i + 1);
            }

            // Infer value_labels from the data column
            $uniqueValues = array_unique(array_column(
                array_map(fn($row) => [$row[$i] ?? null], $dataRows),
                0
            ));
            $uniqueValues = array_filter($uniqueValues, fn($v) => $v !== null && $v !== '');
            sort($uniqueValues);

            // Only create value labels if looks like coded numeric data (≤15 distinct values)
            $valueLabels = [];
            if (count($uniqueValues) <= 15) {
                foreach ($uniqueValues as $val) {
                    $valueLabels[(string) $val] = (string) $val;
                }
            }

            $variables[] = [
                'name' => 'col_' . $i,
                'label' => $header,
                'value_labels' => $valueLabels,
                'measure' => 0,
                'var_index' => $i,
            ];
        }

        // Normalise rows to be integer-indexed arrays
        $rows = array_map(fn($row) => array_values((array) $row), $dataRows);

        return [
            'variables' => $variables,
            'rows' => $rows,
            'count' => count($rows),
        ];
    }
}
