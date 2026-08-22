<?php

namespace App\Services\Plagiarism;

use Illuminate\Http\UploadedFile;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use ZipArchive;
use Exception;

class DocumentParserService
{
    /**
     * Parse text content and metadata from an uploaded file or direct string
     */
    public function parseFile(UploadedFile|string $input, ?string $originalFilename = null): array
    {
        if (is_string($input)) {
            $text = trim($input);
            return [
                'content' => $text,
                'file_type' => 'text',
                'original_filename' => $originalFilename ?? 'Direct Text Input',
                'word_count' => $this->calculateWordCount($text),
                'character_count' => mb_strlen($text),
            ];
        }

        $extension = strtolower($input->getClientOriginalExtension());
        $filename = $input->getClientOriginalName();
        $realPath = $input->getRealPath();

        $text = '';
        $fileType = $extension;

        switch ($extension) {
            case 'pdf':
                $text = $this->extractFromPdf($realPath);
                break;

            case 'docx':
                $text = $this->extractFromDocx($realPath);
                break;

            case 'txt':
            case 'md':
            default:
                $text = file_get_contents($realPath);
                // Fix potential character encoding issues
                $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
                $fileType = 'txt';
                break;
        }

        $text = $this->cleanExtractedText($text);

        return [
            'content' => $text,
            'file_type' => $fileType,
            'original_filename' => $filename,
            'word_count' => $this->calculateWordCount($text),
            'character_count' => mb_strlen($text),
        ];
    }

    /**
     * Extract text from PDF file using Smalot PDF Parser with fallback
     */
    private function extractFromPdf(string $path): string
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($path);
            $text = $pdf->getText();
            if (!empty(trim($text))) {
                return $text;
            }
        } catch (Exception $e) {
            // Fallback to basic text stream extraction if parser encounters obscure PDF encoding
        }

        // Basic binary stream extraction fallback
        $content = file_get_contents($path);
        $text = '';
        if (preg_match_all('/BT[\s\S]*?ET/s', $content, $matches)) {
            foreach ($matches[0] as $match) {
                if (preg_match_all('/\((.*?)\)\s*T[jJ]/s', $match, $textMatches)) {
                    $text .= implode(' ', $textMatches[1]) . "\n";
                }
            }
        }

        return !empty(trim($text)) ? $text : 'Unable to extract text from this PDF. Please copy and paste the text directly.';
    }

    /**
     * Extract text from DOCX file using native ZipArchive XML extraction
     */
    private function extractFromDocx(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) === true) {
            $xmlIndex = $zip->locateName('word/document.xml');
            if ($xmlIndex !== false) {
                $xmlData = $zip->getFromIndex($xmlIndex);
                $zip->close();

                // Convert XML paragraph & break tags into newlines and extract text
                $xmlData = str_replace(['<w:p ', '<w:p>', '<w:br/>', '<w:cr/>'], ["\n<w:p ", "\n<w:p>", "\n", "\n"], $xmlData);
                $text = strip_tags($xmlData);
                return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
            }
            $zip->close();
        }

        // Secondary fallback with PHPWord
        try {
            $phpWord = WordIOFactory::load($path, 'Word2007');
            $fullText = '';
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $fullText .= $element->getText() . "\n";
                    }
                }
            }
            return $fullText;
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Clean and normalize raw extracted document text
     */
    private function cleanExtractedText(string $text): string
    {
        // Normalize line breaks
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        // Collapse excessive newlines (more than 2)
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        // Normalize multiple spaces into single space per line
        $lines = explode("\n", $text);
        $cleanLines = array_map(function ($line) {
            return trim(preg_replace('/[ \t]+/', ' ', $line));
        }, $lines);

        return trim(implode("\n", $cleanLines));
    }

    /**
     * Accurate word count supporting multibyte and hyphenated terms
     */
    public function calculateWordCount(string $text): int
    {
        if (empty(trim($text))) {
            return 0;
        }
        return count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY));
    }
}
