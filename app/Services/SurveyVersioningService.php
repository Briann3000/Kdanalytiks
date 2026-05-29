<?php

namespace App\Services;

use App\Models\Survey;
use App\Models\SurveyVersion;

class SurveyVersioningService
{
    /**
     * Compare survey and proposed changes, and create a new version snapshot of the current state if different.
     */
    public function createVersionIfChanged(Survey $survey, array $newFields, ?int $userId): ?SurveyVersion
    {
        if (!$survey->exists) {
            return null; // Don't version new surveys
        }

        $titleChanged = isset($newFields['title']) && $survey->title !== $newFields['title'];
        $descChanged = array_key_exists('description', $newFields) && $survey->description !== $newFields['description'];

        $oldSchemaStr = $survey->json_schema ?? '[]';
        $newSchemaStr = $newFields['json_schema'] ?? $oldSchemaStr;

        $schemaChanged = false;
        $schemaChanges = [];

        if ($oldSchemaStr !== $newSchemaStr) {
            $oldParsed = json_decode($oldSchemaStr, true) ?? [];
            $newParsed = json_decode($newSchemaStr, true) ?? [];

            if ($oldParsed != $newParsed) {
                $schemaChanged = true;
                $schemaChanges = $this->diffSchemas($oldParsed, $newParsed);
            }
        }

        if ($titleChanged || $descChanged || $schemaChanged) {
            // Determine next version number
            $nextVersion = ($survey->versions()->max('version_number') ?? 0) + 1;

            // Generate change summary
            $summaryParts = [];
            if ($titleChanged) {
                $summaryParts[] = "Title updated";
            }
            if ($descChanged) {
                $summaryParts[] = "Description updated";
            }
            if ($schemaChanged) {
                $added = $schemaChanges['added'] ?? 0;
                $removed = $schemaChanges['removed'] ?? 0;
                $modified = $schemaChanges['modified'] ?? 0;

                $schemaParts = [];
                if ($added > 0)
                    $schemaParts[] = "{$added} added";
                if ($removed > 0)
                    $schemaParts[] = "{$removed} removed";
                if ($modified > 0)
                    $schemaParts[] = "{$modified} modified";

                if (!empty($schemaParts)) {
                    $summaryParts[] = "Questions: " . implode(', ', $schemaParts);
                } else {
                    $summaryParts[] = "Questions schema updated";
                }
            }

            $changeSummary = implode('; ', $summaryParts);

            return SurveyVersion::create([
                'survey_id' => $survey->id,
                'version_number' => $nextVersion,
                'json_schema' => $survey->json_schema ?? '[]',
                'title' => $survey->title,
                'description' => $survey->description,
                'change_summary' => $changeSummary ?: 'Survey updated',
                'changed_by' => $userId,
            ]);
        }

        return null;
    }

    /**
     * Compute difference between old and new JSON schemas.
     */
    private function diffSchemas(array $old, array $new): array
    {
        $oldQuestions = [];
        foreach ($old as $q) {
            if (isset($q['name'])) {
                $oldQuestions[$q['name']] = $q;
            }
        }

        $newQuestions = [];
        foreach ($new as $q) {
            if (isset($q['name'])) {
                $newQuestions[$q['name']] = $q;
            }
        }

        $added = 0;
        $removed = 0;
        $modified = 0;

        foreach ($newQuestions as $name => $q) {
            if (!isset($oldQuestions[$name])) {
                $added++;
            } else {
                if ($oldQuestions[$name] != $q) {
                    $modified++;
                }
            }
        }

        foreach ($oldQuestions as $name => $q) {
            if (!isset($newQuestions[$name])) {
                $removed++;
            }
        }

        return [
            'added' => $added,
            'removed' => $removed,
            'modified' => $modified,
        ];
    }
}
