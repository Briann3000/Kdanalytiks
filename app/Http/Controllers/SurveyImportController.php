<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Services\Import\SpssImportParser;
use App\Services\Import\ExcelImportParser;
use App\Services\Import\SurveyImportBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SurveyImportController extends Controller
{
    public function __construct(
        protected SpssImportParser $spssParser,
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
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $allowed = ['sav', 'xlsx', 'xls', 'csv', 'kmsurvey'];
        if (!in_array($extension, $allowed)) {
            return response()->json(['error' => __('Unsupported file type. Please upload a .sav, .xlsx, .xls, .csv, or .kmsurvey file.')], 422);
        }

        // Use the PHP-uploaded temp path directly — no storage write needed for parsing
        $realPath = $file->getRealPath();

        try {
            if ($extension === 'sav') {
                $parsed = $this->spssParser->parse($realPath);
                $source = 'spss';
            } elseif (in_array($extension, ['xlsx', 'xls'])) {
                $parsed = $this->excelParser->parse($realPath);
                $source = 'excel';
            } elseif ($extension === 'csv') {
                $parsed = $this->excelParser->parse($realPath);
                $source = 'csv';
            } elseif ($extension === 'kmsurvey') {
                return $this->previewKmsurvey($realPath);
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
            // Also persist the file for confirmation (move it to a permanent temp location now).
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
                title: $request->title,
                importSource: $source,
                mapping: $mapping,
                rows: $rows,
                appendToSurvey: $appendTo,
            );

            // Clean up tmp file
            if (session('import_tmp_path')) {
                Storage::disk('local')->delete(session('import_tmp_path'));
                session()->forget(['import_tmp_path', 'import_source', 'import_parsed_rows', 'import_row_count']);
            }

            return response()->json([
                'success' => true,
                'survey_id' => $survey->id,
                'survey_title' => $survey->title,
                'links' => [
                    'builder' => route('surveys.edit', $survey),
                    'reports' => route('surveys.reports', $survey),
                    'settings' => route('surveys.settings', $survey),
                    'hub' => route('surveys.summary', $survey),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => __('Import failed: ') . $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Import a .kmsurvey ZIP bundle (re-import on another site)
    // ─────────────────────────────────────────────────────────────────────

    public function importPackage(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:102400'],
        ]);

        $file = $request->file('file');
        $tmpPath = storage_path('app/imports/tmp/' . Str::uuid() . '.zip');

        $file->move(dirname($tmpPath), basename($tmpPath));

        try {
            $zip = new \ZipArchive();
            if ($zip->open($tmpPath) !== true) {
                throw new \Exception('Could not open ZIP archive.');
            }

            $surveyJson = json_decode($zip->getFromName('survey.json'), true);
            $questionsJson = json_decode($zip->getFromName('questions.json'), true);
            $responsesJson = json_decode($zip->getFromName('responses.json'), true);
            $zip->close();

            if (!$surveyJson || !$questionsJson) {
                throw new \Exception('Invalid .kmsurvey bundle — missing required files.');
            }

            $survey = \App\Models\Survey::create([
                'title' => ($surveyJson['title'] ?? 'Imported Survey') . ' (Restored)',
                'description' => $surveyJson['description'] ?? '',
                'status' => \App\Enums\SurveyStatus::Active,
                'type' => \App\Enums\SurveyType::Private ,
                'category' => \App\Enums\SurveyCategory::Academic,
                'import_source' => 'package',
                'created_by' => \Illuminate\Support\Facades\Auth::id(),
                'json_schema' => json_encode($surveyJson['json_schema'] ?? []),
                'share_token' => \Illuminate\Support\Str::random(32),
            ]);

            // Restore questions
            $questionIdMap = [];
            foreach ($questionsJson as $q) {
                $newQ = \App\Models\Question::create([
                    'survey_id' => $survey->id,
                    'text' => $q['text'],
                    'type' => $q['type'],
                    'options' => $q['options'] ?? [],
                    'required' => $q['required'] ?? false,
                    'position' => $q['position'],
                ]);
                $questionIdMap[$q['id']] = $newQ->id;
            }

            // Restore responses + answers
            if ($responsesJson) {
                foreach ($responsesJson as $r) {
                    $response = \App\Models\Response::create([
                        'survey_id' => $survey->id,
                        'respondent_id' => null,
                        'guest_name' => $r['guest_name'] ?? 'Restored Respondent',
                        'ai_metadata' => null,
                    ]);

                    foreach ($r['answers'] ?? [] as $a) {
                        $newQId = $questionIdMap[$a['question_id']] ?? null;
                        if (!$newQId)
                            continue;
                        \App\Models\Answer::create([
                            'response_id' => $response->id,
                            'question_id' => $newQId,
                            'value' => $a['value'],
                        ]);
                    }
                }
            }

            @unlink($tmpPath);

            return response()->json([
                'success' => true,
                'survey_id' => $survey->id,
                'links' => [
                    'hub' => route('surveys.summary', $survey),
                    'builder' => route('surveys.edit', $survey),
                    'reports' => route('surveys.reports', $survey),
                ],
            ]);
        } catch (\Throwable $e) {
            @unlink($tmpPath);
            return response()->json(['error' => __('Package import failed: ') . $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Internal: preview a .kmsurvey ZIP without committing
    // ─────────────────────────────────────────────────────────────────────

    protected function previewKmsurvey(string $filePath)
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return response()->json(['error' => __('Invalid .kmsurvey file.')], 422);
        }

        $surveyJson = json_decode($zip->getFromName('survey.json'), true);
        $questionsJson = json_decode($zip->getFromName('questions.json'), true);
        $responsesJson = json_decode($zip->getFromName('responses.json'), true);
        $zip->close();

        return response()->json([
            'source' => 'package',
            'survey' => $surveyJson,
            'questions' => $questionsJson,
            'row_count' => count($responsesJson ?? []),
            'is_package' => true,
        ]);
    }
}
