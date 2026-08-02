<?php

namespace App\Services\Import;

use SPSS\Sav\Reader;
use Illuminate\Support\Facades\Log;

class SpssImportParser
{
    use CleansImportValues;

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

        $varNames = [];
        foreach ($reader->variables as $idx => $v) {
            $varNames[$idx] = $v->name ?? 'UNKNOWN';
        }

        $firstRowKeys = [];
        $firstRowVals = [];
        foreach ($reader->data as $case) {
            $firstRowKeys = array_keys((array) $case);
            // Only grab the first 10 values to keep the log clean
            $firstRowVals = array_slice(array_values((array) $case), 0, 10);
            break;
        }

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

            // Detect if label looks like an untranslated VAR code
            $looksLikeSpssCode = (bool) preg_match('/^VAR\d+$|^[A-Z_]+[0-9]+$/', $name);

            $variables[] = [
                'name' => $name,
                'label' => $label,
                'value_labels' => $valueLabels,
                'measure' => 0,
                'var_index' => $varIndex,
                'real_pos' => $realPos,
                'looks_like_spss_code' => $looksLikeSpssCode,
            ];
        }

        // ── Build raw rows ────────────────────────────────────────────────────
        // The tiamo/spss Data::readCaseData() fills each case row with sequential
        // integer keys [0, 1, 2, ...] that directly match the sequential foreach
        // index of $reader->variables (which is already a filtered list — hidden/blank
        // width=-1 tempVars have been excluded). So $varIndex from the variable
        // metadata loop is exactly the right key to use for case data lookup.
        $rows = [];

        foreach ($reader->data as $case) {
            $caseValues = (array) $case; // [0 => val, 1 => val, ...] sequential
            $row = [];

            foreach ($variables as $var) {
                $varIndex = $var['var_index']; // sequential index in $reader->variables
                $val = $this->cleanValue($caseValues[$varIndex] ?? null);
                $row[$varIndex] = $val;
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
