<?php

namespace App\Services\Import;

trait CleansImportValues
{
    /**
     * Normalize a single cell value: strip SPSS/Excel artifacts,
     * trim whitespace, and convert sentinel strings to '-'.
     *
     * Handles:
     *   - Literal "#NULL!" (case-insensitive) from SPSS exports
     *   - SPSS SYSMIS extreme float / string markers
     *   - Whitespace and empty strings → '-'
     */
    protected function cleanValue(mixed $raw): mixed
    {
        if ($raw === null) {
            return '-';
        }

        // SYSMIS float sentinel
        if (is_float($raw) && $raw <= -1e300) {
            return '-';
        }

        if (is_string($raw)) {
            $raw = trim($raw);

            if ($raw === '') {
                return '-';
            }

            // SPSS dot missing code or single period
            if ($raw === '.') {
                return '-';
            }

            $upper = strtoupper($raw);

            // Blacklist literal SPSS/Excel sentinel strings
            $sentinels = ['#NULL!', 'SYSMIS', 'NULL', 'NA', 'N/A', '#N/A', '#VALUE!', '#REF!'];
            if (in_array($upper, $sentinels, true)) {
                return '-';
            }

            // SYSMIS string variants
            if (
                str_contains($raw, '-1.797693') ||
                str_contains($raw, 'E+308') ||
                str_contains($raw, 'E-308')
            ) {
                return '-';
            }
        }

        return $raw;
    }
}
