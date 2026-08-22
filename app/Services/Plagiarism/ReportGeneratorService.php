<?php

namespace App\Services\Plagiarism;

use App\Models\PlagiarismScan;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ReportGeneratorService
{
    /**
     * Generate downloadable PDF similarity report
     */
    public function generatePdf(PlagiarismScan $scan): \Illuminate\Http\Response
    {
        $scan->load([
            'matches' => function ($q) {
                $q->where('is_excluded', false)->orderBy('start_offset', 'asc');
            },
            'user',
            'organization'
        ]);

        $pdf = Pdf::loadView('plagiarism.pdf', [
            'scan' => $scan,
            'matches' => $scan->matches,
            'metadata' => $scan->summary_metadata ?? [],
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        $safeTitle = \Illuminate\Support\Str::slug($scan->title ?: 'similarity-report');
        $filename = "KDAnalytiks_Originality_Report_{$safeTitle}_{$scan->id}.pdf";

        return $pdf->download($filename);
    }
}
