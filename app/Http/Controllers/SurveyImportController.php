<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Services\Import\ExcelImportParser;
use App\Services\Import\SurveyImportBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;

class SurveyImportController extends Controller
{
    public function __construct(
        protected ExcelImportParser $excelParser,
        protected SurveyImportBuilder $builder
    ) {
    }

    // ─────────────────────────────────────────────────────────────────────
    // Step 0: Show the import wizard page
    // ─────────────────────────────────────────────────────────────────────

    public function showImportPage(Request $request)
    {
        $appendTo = null;
        if ($request->filled('append_to')) {
            $appendTo = Survey::findOrFail($request->integer('append_to'));
            $this->authorize('update', $appendTo);
        }

        return view('surveys.import', compact('appendTo'));
    }

    // ─────────────────────────────────────────────────────────────────────
    // Step 1: Parse the uploaded file and return variable metadata as JSON
    // ─────────────────────────────────────────────────────────────────────

    public function previewImport(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'codebook' => ['nullable', 'file', 'max:10240'],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $allowed = ['xlsx', 'xls', 'csv'];
        if (!in_array($extension, $allowed)) {
            return response()->json(['error' => __('Unsupported file type. Please upload a .xlsx, .xls or .csv.')], 422);
        }

        // Use the PHP-uploaded temp path directly — no storage write needed for parsing
        $realPath = $file->getRealPath();

        try {
            $codebookApplied = false;

            if (in_array($extension, ['xlsx', 'xls'])) {
                // For Excel, inject codebook to resolve headers during parse
                $this->excelParser->setCodebook($this->parseCodebook($request->file('codebook')));
                $parsed = $this->excelParser->parse($realPath);
                $source = 'excel';
                $codebookApplied = $request->hasFile('codebook');
            } elseif ($extension === 'csv') {
                $this->excelParser->setCodebook($this->parseCodebook($request->file('codebook')));
                $parsed = $this->excelParser->parse($realPath);
                $source = 'csv';
                $codebookApplied = $request->hasFile('codebook');
            } else {
                return response()->json(['error' => __('Unsupported file type.')], 422);
            }

            // Auto-infer type for each variable
            foreach ($parsed['variables'] as &$var) {
                $var['inferred_type'] = $this->builder->inferType($var);
                $var['inferred_options'] = $this->builder->buildOptions($var['value_labels']);
                $var['include'] = true;
            }
            unset($var);

            // Persist rows in session for the confirmation step.
            $storagePath = $file->storeAs('imports/tmp', Str::uuid() . '.' . $extension, 'local');

            session([
                'import_tmp_path' => $storagePath,
                'import_source' => $source,
                'import_parsed_rows' => $parsed['rows'],
                'import_row_count' => $parsed['count'],
            ]);

            return response()->json([
                'variables' => $parsed['variables'],
                'row_count' => $parsed['count'],
                'source' => $source,
                'preview_rows' => array_slice($parsed['rows'], 0, 5),
                'codebook_applied' => $codebookApplied,
            ]);

        } catch (\Throwable $e) {
            return response()->json(['error' => __('Could not parse the file: ') . $e->getMessage()], 422);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Step 2: Confirm the mapping and build the survey
    // ─────────────────────────────────────────────────────────────────────

    public function confirmImport(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'mapping' => ['required', 'array', 'min:1'],
            'mapping.*.var_index' => ['required', 'integer'],
            'mapping.*.label' => ['required', 'string'],
            'mapping.*.type' => ['required', 'string', 'in:text,textarea,radio,checkbox,select,scale,rating,select_one,number,decimal,date'],
            'mapping.*.include' => ['required', 'boolean'],
        ]);

        $rows = session('import_parsed_rows', []);
        $source = session('import_source', 'excel');

        if (empty($rows)) {
            return response()->json(['error' => __('Import session expired. Please re-upload the file.')], 422);
        }

        // Enrich mapping with value_labels from the request
        $mapping = collect($request->mapping)->map(function ($col) use ($request) {
            return [
                'var_index' => $col['var_index'],
                'label' => $col['label'],
                'type' => $col['type'],
                'options' => $col['options'] ?? [],
                'value_labels' => $col['value_labels'] ?? [],
                'include' => (bool) ($col['include'] ?? true),
            ];
        })->all();

        // Optionally append to existing survey
        $appendTo = null;
        if ($request->filled('append_to_survey')) {
            $appendTo = Survey::findOrFail($request->integer('append_to_survey'));
            $this->authorize('update', $appendTo);
        }

        try {
            $survey = $this->builder->build(
                $request->title,
                $source,
                $mapping,
                $rows,
                $appendTo
            );

            session()->forget(['import_tmp_path', 'import_source', 'import_parsed_rows', 'import_row_count']);

            return response()->json([
                'success' => true,
                'survey_id' => $survey->id,
                'links' => [
                    'builder' => route('surveys.edit', $survey),
                    'hub' => route('surveys.summary', $survey),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => __('Import failed: ') . $e->getMessage()], 500);
        }
    }


    // ─────────────────────────────────────────────────────────────────────
    // Internal: parse an uploaded codebook file (Excel/CSV) into a
    // [VAR_code => human_label] mapping.
    // ─────────────────────────────────────────────────────────────────────

    protected function parseCodebook(?\Illuminate\Http\UploadedFile $file): ?array
    {
        if (!$file) {
            return null;
        }

        try {
            $ext = strtolower($file->getClientOriginalExtension());
            $rows = [];

            if ($ext === 'csv') {
                $handle = fopen($file->getRealPath(), 'r');
                if (!$handle) {
                    return null;
                }
                while (($line = fgetcsv($handle)) !== false) {
                    $rows[] = $line;
                }
                fclose($handle);
            } else {
                // Excel codebook: inline ToArray parser
                $parser = new class implements ToArray {
                    public array $rows = [];
                    public function array(array $array): void
                    {
                        $this->rows = $array;
                    }
                };
                Excel::import($parser, $file->getRealPath());
                $rows = $parser->rows;
            }

            if (empty($rows)) {
                return null;
            }

            $map = [];
            foreach ($rows as $row) {
                $code = trim((string) ($row[0] ?? ''));
                $label = trim((string) ($row[1] ?? ''));
                if ($code !== '' && $label !== '') {
                    $map[$code] = $label;
                }
            }

            return !empty($map) ? $map : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Apply a codebook mapping to already-parsed variables (e.g. SPSS).
     */
    protected function applyCodebookLabels(array &$variables, ?array $codebookMap): bool
    {
        if (!$codebookMap) {
            return false;
        }

        $applied = false;
        foreach ($variables as &$var) {
            $name = $var['name'] ?? '';
            if (isset($codebookMap[$name])) {
                $var['label'] = $codebookMap[$name];
                $var['looks_like_spss_code'] = false;
                $applied = true;
            }
        }
        unset($var);

        return $applied;
    }
}
