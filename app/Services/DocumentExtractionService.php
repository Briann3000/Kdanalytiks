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

        return Str::limit($normalized, 100000, '...');
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
            $chunks = [];

            foreach ($document->getSections() as $section) {
                $this->extractPhpWordElements($section->getElements(), $chunks);
            }

            $text = trim(implode("\n\n", $chunks));
            if (!empty($text)) {
                return $text;
            }
        } catch (\Throwable $e) {
            // Fallback to direct XML parsing if PhpWord fails
        }

        return $this->extractDocxXmlText($path);
    }

    private function extractDocxXmlText(string $path): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) === true) {
            $xmlContent = $zip->getFromName('word/document.xml');
            $zip->close();
        } else {
            throw new RuntimeException('This DOCX file could not be read. Please re-save it as a standard Word document and try again.');
        }

        if ($xmlContent === false) {
            throw new RuntimeException('This DOCX file does not contain readable document XML.');
        }

        // Parse tables into structured markdown/pipe format before stripping tags
        $xmlContent = preg_replace_callback('/<w:tbl\b[\s\S]*?<\/w:tbl>/u', function ($tblMatch) {
            $tableXml = $tblMatch[0];
            $rows = [];
            if (preg_match_all('/<w:tr\b[\s\S]*?<\/w:tr>/u', $tableXml, $trMatches)) {
                foreach ($trMatches[0] as $trXml) {
                    $cells = [];
                    if (preg_match_all('/<w:tc\b[\s\S]*?<\/w:tc>/u', $trXml, $tcMatches)) {
                        foreach ($tcMatches[0] as $tcXml) {
                            $cellText = strip_tags(str_replace('</w:p>', ' ', $tcXml));
                            $cellText = html_entity_decode($cellText, ENT_QUOTES | ENT_XML1, 'UTF-8');
                            $cells[] = trim(preg_replace('/\s+/u', ' ', $cellText));
                        }
                    }
                    if (!empty($cells) && array_filter($cells, fn($c) => $c !== '')) {
                        $rows[] = '| ' . implode(' | ', $cells) . ' |';
                    }
                }
            }
            return !empty($rows) ? "\n\n" . implode("\n", $rows) . "\n\n" : '';
        }, $xmlContent);

        // Replace paragraph closing tags with double newlines
        $xmlContent = str_replace('</w:p>', "\n\n", $xmlContent);
        $text = strip_tags($xmlContent);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
        return trim($text);
    }

    private function extractPhpWordElements(iterable $elements, array &$chunks): void
    {
        foreach ($elements as $element) {
            if (method_exists($element, 'getRows')) {
                // Table element: render as formatted markdown table rows
                $tableRows = [];
                foreach ($element->getRows() as $row) {
                    $cellTexts = [];
                    foreach ($row->getCells() as $cell) {
                        $cellChunks = [];
                        $this->extractPhpWordElements($cell->getElements(), $cellChunks);
                        $cellTexts[] = trim(implode(' ', $cellChunks));
                    }
                    if (!empty($cellTexts) && array_filter($cellTexts, fn($c) => $c !== '')) {
                        $tableRows[] = '| ' . implode(' | ', $cellTexts) . ' |';
                    }
                }
                if (!empty($tableRows)) {
                    $chunks[] = implode("\n", $tableRows);
                }
                continue;
            }

            if (method_exists($element, 'getText')) {
                $text = trim((string) $element->getText());
                if ($text !== '') {
                    $chunks[] = $text;
                }
            }

            if (method_exists($element, 'getElements')) {
                $this->extractPhpWordElements($element->getElements(), $chunks);
            }
        }
    }

    private function normalizeText(string $text): string
    {
        $paragraphs = preg_split('/(\r?\n){2,}/u', $text) ?: [];

        return collect($paragraphs)
            ->map(fn($p) => preg_replace('/\s+/u', ' ', trim($p)) ?? '')
            ->filter(fn($p) => $p !== '')
            ->implode("\n\n");
    }
}
