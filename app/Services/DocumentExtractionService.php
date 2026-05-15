<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use RuntimeException;

class DocumentExtractionService
{
    public function extractText(UploadedFile $file, string $storagePath): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $absolutePath = Storage::disk('local')->path($storagePath);

        $text = match ($extension) {
            'pdf' => $this->extractPdfText($absolutePath),
            'csv' => $this->extractCsvText($absolutePath),
            'txt' => $this->extractPlainText($absolutePath),
            'docx' => $this->extractDocxText($absolutePath),
            default => throw new RuntimeException('This file type is not supported yet.'),
        };

        $normalized = $this->normalizeText($text);

        if ($normalized === '') {
            throw new RuntimeException('This document does not contain readable text content.');
        }

        return Str::limit($normalized, 50000, '...');
    }

    private function extractPdfText(string $path): string
    {
        if (!class_exists(\Smalot\PdfParser\Parser::class)) {
            throw new RuntimeException('PDF parsing dependency is not installed.');
        }

        $parser = new \Smalot\PdfParser\Parser();
        $text = trim($parser->parseFile($path)->getText());

        if ($text === '') {
            throw new RuntimeException('This PDF does not contain extractable text. Scanned PDFs are not supported in v1.');
        }

        return $text;
    }

    private function extractCsvText(string $path): string
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to read the uploaded CSV file.');
        }

        $lines = [];
        while (($row = fgetcsv($handle)) !== false) {
            $lines[] = implode(' | ', array_map(fn($value) => trim((string) $value), $row));
        }
        fclose($handle);

        return implode("\n", $lines);
    }

    private function extractPlainText(string $path): string
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('Unable to read the uploaded text file.');
        }

        return $contents;
    }

    private function extractDocxText(string $path): string
    {
        try {
            $document = IOFactory::load($path);
        } catch (\Throwable $e) {
            throw new RuntimeException('This DOCX file could not be read. Please re-save it as a standard Word document and try again.', 0, $e);
        }

        $chunks = [];

        foreach ($document->getSections() as $section) {
            $this->extractPhpWordElements($section->getElements(), $chunks);
        }

        return implode("\n", $chunks);
    }

    private function extractPhpWordElements(iterable $elements, array &$chunks): void
    {
        foreach ($elements as $element) {
            if (method_exists($element, 'getText')) {
                $chunks[] = trim((string) $element->getText());
            }

            if (method_exists($element, 'getElements')) {
                $this->extractPhpWordElements($element->getElements(), $chunks);
            }

            if (method_exists($element, 'getRows')) {
                foreach ($element->getRows() as $row) {
                    foreach ($row->getCells() as $cell) {
                        $this->extractPhpWordElements($cell->getElements(), $chunks);
                    }
                }
            }
        }
    }

    private function normalizeText(string $text): string
    {
        $lines = preg_split('/\R/u', $text) ?: [];

        return collect($lines)
            ->map(fn($line) => preg_replace('/\s+/u', ' ', trim($line)) ?? '')
            ->filter(fn($line) => $line !== '')
            ->implode("\n");
    }
}
