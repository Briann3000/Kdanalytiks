<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ProposalBaselineService
{
    /**
     * Get baseline proposal rules/documents text from hidden storage/app/system_kb/
     */
    public function getBaselineText(): string
    {
        $dir = storage_path('app/system_kb');

        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $files = File::files($dir);
        if (empty($files)) {
            return "";
        }

        $combinedText = [];
        $extractionService = app(DocumentExtractionService::class);

        foreach ($files as $file) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['docx', 'doc', 'pdf', 'txt', 'md'])) {
                try {
                    $text = $extractionService->extractTextFromFile($file->getRealPath());
                    if (!empty($text)) {
                        $combinedText[] = "=== SYSTEM PROPOSAL BASELINE [{$file->getFilename()}] ===\n" . trim($text);
                    }
                } catch (\Throwable $e) {
                    Log::error("Failed to extract baseline file {$file->getFilename()}: " . $e->getMessage());
                }
            }
        }

        return implode("\n\n", $combinedText);
    }
}
