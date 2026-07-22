<?php

namespace App\Services\Import;

use SPSS\Sav\Reader;

class SpssImportParser
{
    /**
     * Parse an SPSS .sav file and return structured metadata + rows.
     *
     * Returns:
     * [
     *   'variables' => [
     *       ['name' => 'VAR00001', 'label' => 'Gender', 'value_labels' => ['1' => 'Male', '2' => 'Female'], 'measure' => 0],
     *       ...
     *   ],
     *   'rows'  => [ [1, 2, 3, ...], [...], ... ],
     *   'count' => 150,
     * ]
     */
    public function parse(string $filePath): array
    {
        $reader = Reader::fromFile($filePath)->read();

        // ── Build a map: variable realPosition → value labels ────────────────
        // ValueLabel records contain an $indexes list (1-based positions in the
        // *temporary* variable list) and a $labels array of ['value' => n, 'label' => s]
        $valueLabelsByVarIndex = [];
        foreach ($reader->valueLabels as $vlRecord) {
            $labelMap = [];
            foreach ($vlRecord->labels as $entry) {
                $code = (string) (int) $entry['value'];
                $labelMap[$code] = trim($entry['label'] ?? '');
            }
            foreach ($vlRecord->indexes ?? [] as $pos) {
                // indexes are 1-based within the temp variable list
                $valueLabelsByVarIndex[$pos - 1] = $labelMap;
            }
        }

        // ── Build variable metadata list ─────────────────────────────────────
        $variables = [];
        foreach ($reader->variables as $varIndex => $variable) {
            $name = trim($variable->name ?? ('VAR' . str_pad($varIndex + 1, 5, '0', STR_PAD_LEFT)));
            $label = trim($variable->label ?? '');
            if ($label === '') {
                $label = $name;
            }

            // Match value labels using realPosition (the index in the temp list)
            $realPos = $variable->realPosition ?? $varIndex;
            $valueLabels = $valueLabelsByVarIndex[$realPos] ?? [];

            $variables[] = [
                'name' => $name,
                'label' => $label,
                'value_labels' => $valueLabels,
                'measure' => 0,
                'var_index' => $varIndex,
            ];
        }

        // ── Build raw rows ────────────────────────────────────────────────────
        // $reader->data is an array of arrays: each sub-array is one case,
        // indexed by variable realPosition (same order as $reader->variables)
        $rows = [];
        foreach ($reader->data as $case) {
            $row = [];
            foreach (array_values((array) $case) as $val) {
                if (is_float($val) && $val <= -1e300) {
                    $val = null;
                }
                $row[] = $val;
            }
            $rows[] = $row;
        }

        return [
            'variables' => $variables,
            'rows' => $rows,
            'count' => count($rows),
        ];
    }
}
