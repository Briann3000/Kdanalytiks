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

    /**
     * Import a complete .kdsurvey ZIP package bundle.
     */
    public function importPackage(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'append_to_survey' => ['nullable', 'integer', 'exists:surveys,id'],
        ]);

        $uploadedFile = $request->file('file');
        $zip = new \ZipArchive();

        if ($zip->open($uploadedFile->getRealPath()) !== true) {
            if ($request->wantsJson()) {
                return response()->json(['error' => __('Invalid data package or corrupted archive.')], 422);
            }
            return back()->with('error', __('Invalid data package or corrupted archive.'));
        }

        $surveyJsonStr = $zip->getFromName('survey.json');
        $questionsJsonStr = $zip->getFromName('questions.json');
        $responsesJsonStr = $zip->getFromName('responses.json');
        $zip->close();

        if (!$surveyJsonStr) {
            if ($request->wantsJson()) {
                return response()->json(['error' => __('The package is missing survey.json definition.')], 422);
            }
            return back()->with('error', __('The package is missing survey.json definition.'));
        }

        $surveyData = json_decode($surveyJsonStr, true) ?? [];
        $questionsData = $questionsJsonStr ? (json_decode($questionsJsonStr, true) ?? []) : [];
        $responsesData = $responsesJsonStr ? (json_decode($responsesJsonStr, true) ?? []) : [];

        $user = \Illuminate\Support\Facades\Auth::user();
        $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $surveyData, $questionsData, $responsesData, $user, $role) {
            if ($request->filled('append_to_survey')) {
                $survey = Survey::findOrFail($request->integer('append_to_survey'));
                $this->authorize('update', $survey);
            } else {
                $createData = [
                    'title' => $surveyData['title'] ?? 'Imported Survey',
                    'description' => $surveyData['description'] ?? 'Imported from .kdsurvey package.',
                    'status' => \App\Enums\SurveyStatus::Active,
                    'type' => \App\Enums\SurveyType::Invitation,
                    'category' => \App\Enums\SurveyCategory::tryFrom($surveyData['category'] ?? '') ?? \App\Enums\SurveyCategory::Academic,
                    'import_source' => 'package',
                    'created_by' => $user->id,
                    'json_schema' => !empty($surveyData['json_schema']) ? json_encode($surveyData['json_schema']) : json_encode([]),
                    'share_token' => Str::random(32),
                    'export_org_name' => $surveyData['export_org_name'] ?? null,
                    'remove_kd_branding' => !empty($surveyData['remove_kd_branding']),
                ];

                if ($role === 'organization') {
                    $createData['organization_id'] = $user->organization?->id;
                } elseif ($role === 'independent') {
                    $createData['independent_id'] = $user->independent?->id;
                }

                $survey = Survey::create($createData);
            }

            // Import responses
            $importedCount = 0;
            foreach ($responsesData as $r) {
                $response = \App\Models\Response::create([
                    'survey_id' => $survey->id,
                    'respondent_id' => null,
                    'guest_name' => $r['guest_name'] ?? 'Imported Respondent',
                    'ai_metadata' => $r['ai_metadata'] ?? null,
                    'created_at' => !empty($r['created_at']) ? \Carbon\Carbon::parse($r['created_at']) : now(),
                ]);

                // Prepare answers array
                $answersArray = [];
                if (!empty($r['data']) && is_array($r['data'])) {
                    foreach ($r['data'] as $fName => $uVal) {
                        $answersArray[] = [
                            'name' => $fName,
                            'userData' => $uVal,
                        ];
                    }
                } elseif (!empty($r['answers']) && is_array($r['answers'])) {
                    foreach ($r['answers'] as $ans) {
                        $answersArray[] = [
                            'name' => $ans['name'] ?? ($ans['question_id'] ? 'question_' . $ans['question_id'] : 'field'),
                            'userData' => $ans['value'] ?? '',
                        ];
                    }
                }

                if (!empty($answersArray)) {
                    \App\Models\Answer::create([
                        'response_id' => $response->id,
                        'question_id' => null,
                        'value' => json_encode($answersArray),
                    ]);
                }

                $importedCount++;
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'survey_id' => $survey->id,
                    'message' => __('Package imported successfully with :count responses.', ['count' => $importedCount]),
                    'links' => [
                        'hub' => route('surveys.summary', $survey),
                        'settings' => route('surveys.settings', $survey),
                    ],
                ]);
            }

            return redirect()->route('surveys.summary', $survey)->with(
                'success',
                __('Survey package imported successfully with :count responses.', ['count' => $importedCount])
            );
        });
    }
}

