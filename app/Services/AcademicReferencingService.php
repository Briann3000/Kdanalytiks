<?php

namespace App\Services;

class AcademicReferencingService
{
    /**
     * Format a citation according to the specified style.
     */
    public function formatCitation(array $data, string $style = 'apa7'): string
    {
        return match ($style) {
            'apa7' => $this->formatApa7($data),
            'mla9' => $this->formatMla9($data),
            'harvard' => $this->formatHarvard($data),
            default => $this->formatApa7($data),
        };
    }

    /**
     * APA 7th Edition formatting: Author, A. A. (Year). Title. Source.
     */
    private function formatApa7(array $data): string
    {
        $author = $data['author'] ?? 'Unknown Author';
        $year = $data['year'] ?? 'n.d.';
        $title = $data['title'] ?? 'Untitled';
        $source = $data['source'] ?? '';

        return "{$author} ({$year}). *{$title}*. {$source}";
    }

    /**
     * MLA 9th Edition formatting: Author. "Title." Source, Year.
     */
    private function formatMla9(array $data): string
    {
        $author = $data['author'] ?? 'Unknown Author';
        $title = $data['title'] ?? 'Untitled';
        $source = $data['source'] ?? '';
        $year = $data['year'] ?? 'n.d.';

        return "{$author}. \"{$title}.\" *{$source}*, {$year}.";
    }

    /**
     * Harvard formatting: Author (Year) Title. Source.
     */
    private function formatHarvard(array $data): string
    {
        $author = $data['author'] ?? 'Unknown Author';
        $year = $data['year'] ?? 'n.d.';
        $title = $data['title'] ?? 'Untitled';
        $source = $data['source'] ?? '';

        return "{$author} ({$year}) *{$title}*. {$source}.";
    }
}
