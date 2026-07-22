<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\Question;
use App\Models\Response;
use App\Models\Answer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SurveyExportPackageController extends Controller
{
    use AuthorizesRequests;
    // ─────────────────────────────────────────────────────────────────────
    // 1. Export .kmsurvey ZIP bundle (portable JSON)
    // ─────────────────────────────────────────────────────────────────────

    public function exportPackage(Survey $survey)
    {
        $this->authorize('view', $survey);

        $questions = $survey->questions()->orderBy('position')->get()->map(fn($q) => [
            'id' => $q->id,
            'text' => $q->text,
            'type' => $q->type,
            'options' => $q->options,
            'required' => $q->required,
            'position' => $q->position,
        ])->toArray();

        $responses = $survey->responses()->with('answers')->get()->map(fn($r) => [
            'id' => $r->id,
            'guest_name' => $r->guest_name,
            'created_at' => $r->created_at?->toIso8601String(),
            'answers' => $r->answers->map(fn($a) => [
                'question_id' => $a->question_id,
                'value' => $a->value,
            ])->toArray(),
        ])->toArray();

        $surveyData = [
            'title' => $survey->title,
            'description' => $survey->description,
            'category' => $survey->category?->value ?? null,
            'json_schema' => json_decode($survey->json_schema ?? '[]', true),
            'exported_at' => now()->toIso8601String(),
            'exported_by' => Auth::user()?->name,
        ];

        $readme = "KM Survey Package\n"
            . "=================\n"
            . "Survey: {$survey->title}\n"
            . "Exported: " . now()->format('Y-m-d H:i') . "\n\n"
            . "To re-import, use the 'Import Data' option in the Create Survey menu\n"
            . "and upload this .kmsurvey file.\n";

        // Build ZIP in memory
        $zipPath = storage_path('app/exports/' . Str::uuid() . '.zip');
        @mkdir(dirname($zipPath), 0777, true);

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('survey.json', json_encode($surveyData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('questions.json', json_encode($questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('responses.json', json_encode($responses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('README.txt', $readme);
        $zip->close();

        $filename = Str::slug($survey->title) . '_export.kmsurvey';

        return response()->download($zipPath, $filename, [
            'Content-Type' => 'application/octet-stream',
        ])->deleteFileAfterSend(true);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 2. Export SPSS .sav round-trip
    // ─────────────────────────────────────────────────────────────────────

    public function exportSpssPackage(Survey $survey)
    {
        $this->authorize('view', $survey);

        $questions = $survey->questions()->orderBy('position')->get();
        $responses = $survey->responses()->with('answers')->get();

        // Build SPSS writer structure using tiamo/spss
        $writer = new \SPSS\Writer([
            'header' => [
                'prodName' => '@(#) IBM SPSS STATISTICS',
                'fileLabel' => $survey->title,
                'caseCount' => $responses->count(),
            ],
        ]);

        // Define variables
        $variables = [];
        foreach ($questions as $i => $question) {
            $varName = 'VAR' . str_pad($i + 1, 5, '0', STR_PAD_LEFT);
            $variable = new \SPSS\Sav\Record\Variable();
            $variable->name = $varName;
            $variable->label = mb_substr($question->text, 0, 120); // SPSS label max 120 chars

            // If the question has options, map them as value labels (numeric codes)
            $valueLabels = [];
            if (!empty($question->options)) {
                foreach (array_values($question->options) as $idx => $opt) {
                    $code = $idx + 1;
                    $label = is_array($opt) ? ($opt['label'] ?? $opt['value'] ?? (string) $opt) : (string) $opt;
                    $valueLabels[$code] = $label;
                }
                $variable->width = 0;  // numeric
                $variable->decimals = 0;
            } else {
                $variable->width = 255;  // string variable
            }

            $variables[$i] = [
                'model' => $variable,
                'question_id' => $question->id,
                'value_map' => array_flip(array_map(
                    fn($opt) => is_array($opt) ? ($opt['label'] ?? '') : (string) $opt,
                    $question->options ?? []
                ))
            ];

            $writer->addVariable($variable);
            if ($valueLabels) {
                $writer->addValueLabels($variable, $valueLabels);
            }
        }

        // Write cases
        foreach ($responses as $response) {
            $answerMap = $response->answers->keyBy('question_id');
            $caseValues = [];
            foreach ($variables as $varDef) {
                $qId = $varDef['question_id'];
                $answer = $answerMap->get($qId);
                $raw = $answer?->value ?? '';

                // If numeric options exist, try to recover the code
                if (!empty($varDef['value_map']) && isset($varDef['value_map'][$raw])) {
                    $caseValues[] = (int) ($varDef['value_map'][$raw]) + 1;
                } else {
                    $caseValues[] = is_numeric($raw) ? (float) $raw : $raw;
                }
            }
            $writer->addCase($caseValues);
        }

        $tmpPath = storage_path('app/exports/' . Str::uuid() . '.sav');
        @mkdir(dirname($tmpPath), 0777, true);
        $writer->save($tmpPath);

        $filename = Str::slug($survey->title) . '_data.sav';

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/octet-stream',
        ])->deleteFileAfterSend(true);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 3. Export PDF Summary
    // ─────────────────────────────────────────────────────────────────────

    public function exportPdfSummary(Survey $survey)
    {
        $this->authorize('view', $survey);

        $questions = $survey->questions()->with('answers')->orderBy('position')->get();

        // Build aggregate frequency for each question
        $summaryData = [];
        foreach ($questions as $question) {
            $frequencies = $question->answers->groupBy('value')->map->count()->sortDesc();
            $totalAnswers = $question->answers->count();

            $summaryData[] = [
                'question' => $question,
                'frequencies' => $frequencies,
                'total' => $totalAnswers,
            ];
        }

        $pdf = Pdf::loadView('surveys.export_pdf_summary', [
            'survey' => $survey,
            'summaryData' => $summaryData,
            'exportedAt' => now()->format('F j, Y'),
        ])->setPaper('a4');

        $filename = Str::slug($survey->title) . '_summary.pdf';

        return $pdf->download($filename);
    }
}
