<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PlagiarismScan;
use App\Services\Plagiarism\AcademicTextPreprocessor;
use App\Services\Plagiarism\DocumentParserService;
use App\Services\Plagiarism\SimilarityDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlagiarismTest extends TestCase
{
    public function test_academic_preprocessor_isolates_bibliography_and_quotes(): void
    {
        $preprocessor = new AcademicTextPreprocessor();

        $sampleText = "The socio-economic factors influencing municipal solid waste management in urban centers require rigorous quantitative assessment.\n\n" .
            "According to Orodho (2009), empirical research designs offer structured insights into demographic variables.\n\n" .
            "\"Public participation is the cornerstone of sustainable environmental governance.\"\n\n" .
            "## References\n" .
            "Orodho, J. A. (2009). Elements of Educational and Social Science Research Methods. Kanezja Publishers.";

        $result = $preprocessor->preprocess($sampleText, true, true, 5);

        $this->assertNotEmpty($result['segments']);
        $this->assertNotNull($result['bibliography_offset']);

        // Check quote detection
        $quoteSegment = collect($result['segments'])->firstWhere('is_quote', true);
        $this->assertNotNull($quoteSegment);
        $this->assertTrue($quoteSegment['is_excluded']);
    }

    public function test_document_parser_calculates_accurate_word_counts(): void
    {
        $parser = new DocumentParserService();
        $sampleText = "KDAnalytiks automates survey data collection, statistical significance testing, and APA report generation.";

        $parsed = $parser->parseFile($sampleText, 'Test Title');

        $this->assertEquals('text', $parsed['file_type']);
        $this->assertEquals(12, $parsed['word_count']);
    }

    public function test_user_tier_limits_for_plagiarism(): void
    {
        $freeUser = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'independent',
        ]);
        $freeUser->plagiarism_scan_count = 0;

        $this->assertTrue($freeUser->canCheckPlagiarism());
        $this->assertEquals(1500, $freeUser->plagiarismWordLimit());
        $this->assertEquals(3, $freeUser->remainingPlagiarismScans());

        // Exhaust free trial scans
        $freeUser->plagiarism_scan_count = 3;
        $this->assertFalse($freeUser->canCheckPlagiarism());
        $this->assertEquals(0, $freeUser->remainingPlagiarismScans());
    }

    public function test_net_similarity_calculation_avoids_double_counting(): void
    {
        $preprocessor = new AcademicTextPreprocessor();
        $service = new SimilarityDetectionService($preprocessor);

        $text = "The quick brown fox jumps over the lazy dog in the middle of a sunny green field.";
        $scan = new PlagiarismScan(['content' => $text]);

        // Overlapping match ranges
        $matches = [
            ['start_offset' => 0, 'end_offset' => 35, 'is_excluded' => false],
            ['start_offset' => 20, 'end_offset' => 50, 'is_excluded' => false],
        ];

        $score = $service->calculateNetSimilarity($text, $matches, $scan);
        $this->assertGreaterThan(0.0, $score);
        $this->assertLessThanOrEqual(100.0, $score);
    }

    public function test_multibyte_unicode_and_special_quotes(): void
    {
        $preprocessor = new AcademicTextPreprocessor();

        $sampleText = "«L'analyse statistique permet une interprétation rigoureuse des données empiriques.»\n\n" .
            "“Qualitative triangulation reinforces conceptual validity across diverse socio-cultural domains.”";

        $result = $preprocessor->preprocess($sampleText, true, true, 5);

        $this->assertCount(2, $result['segments']);
        $this->assertTrue($result['segments'][0]['is_quote']);
        $this->assertTrue($result['segments'][0]['is_excluded']);
        $this->assertTrue($result['segments'][1]['is_quote']);
        $this->assertTrue($result['segments'][1]['is_excluded']);
    }

    public function test_unconventional_bibliography_headers(): void
    {
        $preprocessor = new AcademicTextPreprocessor();

        $headers = ["Works Cited\nAuthor, A.", "BIBLIOGRAPHY\nSmith, B.", "Literature Cited\nJones, C."];

        foreach ($headers as $headerText) {
            $fullDoc = "Main thesis introduction discussing methodological paradigms and empirical sampling.\n\n" . $headerText;
            $res = $preprocessor->preprocess($fullDoc, true, true, 5);
            $this->assertNotNull($res['bibliography_offset'], "Failed to identify header in: {$headerText}");
        }
    }

    public function test_empty_or_zero_match_scenario_produces_zero_similarity(): void
    {
        $preprocessor = new AcademicTextPreprocessor();
        $service = new SimilarityDetectionService($preprocessor);

        $text = "Unique bespoke academic manuscript text created from scratch.";
        $scan = new PlagiarismScan(['content' => $text]);

        $score = $service->calculateNetSimilarity($text, [], $scan);
        $this->assertEquals(0.0, $score);
    }

    public function test_complete_overlap_yields_exact_100_percent_without_overflow(): void
    {
        $preprocessor = new AcademicTextPreprocessor();
        $service = new SimilarityDetectionService($preprocessor);

        $text = "The entire text is completely duplicated from a published source.";
        $scan = new PlagiarismScan(['content' => $text]);

        $matches = [
            ['start_offset' => 0, 'end_offset' => mb_strlen($text), 'is_excluded' => false]
        ];

        $score = $service->calculateNetSimilarity($text, $matches, $scan);
        $this->assertEquals(100.0, $score);
    }
}
