<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PlagiarismScan;
use App\Models\PlagiarismMatch;
use App\Services\Ai\UnifiedAiDetectorService;
use App\Services\AiHumanizerService;
use App\Services\Plagiarism\AcademicTextPreprocessor;
use App\Services\Plagiarism\DeepTextAlignmentService;
use App\Services\Plagiarism\SimilarityDetectionService;
use App\Services\Plagiarism\WebDocumentScraperService;
use Tests\TestCase;

class DeepPlagiarismTest extends TestCase
{
    public function test_ai_probability_calibration_distinguishes_ai_from_human(): void
    {
        $detector = new UnifiedAiDetectorService();

        // 1. Classical AI Generated Text (formulaic transitions, uniform sentence length, no contractions)
        $aiText = "In conclusion, it is crucial to delve into the multifaceted realm of environmental policy. " .
            "Furthermore, this study serves as a testament to the revolutionary potential of green infrastructure. " .
            "Moreover, optimizing municipal frameworks plays a pivotal role in showcasing sustainable governance. " .
            "In summary, the interconnected tapestry of socio-economic factors underscores the paramount necessity of holistic reform.";

        $aiResult = $detector->analyze($aiText);
        $this->assertGreaterThanOrEqual(70.0, $aiResult['aiProbability'], "AI text should score >= 70% AI probability");
        $this->assertEquals('Highly Likely AI', $aiResult['riskLevel']);

        // 2. Natural Human Written Text (irregular burstiness, personal contractions, varied rhythm)
        $humanText = "I wasn't sure about the results at first. We collected about 400 survey forms across Nairobi, but half the respondents didn't even know what recycling meant. It's frustrating! But we kept going anyway.";

        $humanResult = $detector->analyze($humanText);
        $this->assertLessThanOrEqual(35.0, $humanResult['aiProbability'], "Human text should score <= 35% AI probability");
        $this->assertEquals('Likely Human', $humanResult['riskLevel']);
    }

    public function test_ai_detector_harmonization_matches_humanizer_score(): void
    {
        $groqClient = $this->createMock(\App\Services\GroqStreamingClient::class);
        $detector = new UnifiedAiDetectorService($groqClient);
        $humanizer = new AiHumanizerService($groqClient, $detector);

        $sampleText = "The socio-economic determinants influencing solid waste management in urban informal settlements require rigorous empirical inquiry.";

        $detectorScore = $detector->analyze($sampleText);
        $humanizerScore = $humanizer->analyzeText($sampleText);

        $this->assertEquals(
            $detectorScore['aiProbability'],
            $humanizerScore['aiProbability'],
            "AI Humanizer and Plagiarism detector must produce identical AI probability scores"
        );
    }

    public function test_deep_text_alignment_detects_100_percent_duplication(): void
    {
        $alignment = new DeepTextAlignmentService();

        $publishedWebArticle = "Solid waste governance in urban Kenya requires decentralization of waste collection trucks, enforcement of statutory penalties by NEMA, and community-led recycling initiatives.";

        $segment = [
            'text' => "Solid waste governance in urban Kenya requires decentralization of waste collection trucks, enforcement of statutory penalties by NEMA, and community-led recycling initiatives."
        ];

        $res = $alignment->alignSegmentAgainstDocument($segment, $publishedWebArticle, 6);

        $this->assertNotNull($res);
        $this->assertEquals(100.0, $res['similarity_score']);
    }

    public function test_domain_whitelisting_subtracts_matches(): void
    {
        $preprocessor = new AcademicTextPreprocessor();
        $scraper = new WebDocumentScraperService();
        $alignment = new DeepTextAlignmentService();
        $similarityService = new SimilarityDetectionService($preprocessor, $scraper, $alignment);

        $text = "The quick brown fox jumps over the lazy dog in the middle of a sunny green field.";

        $scan = new PlagiarismScan([
            'content' => $text,
            'excluded_domains' => ['kenpro.org'] // Whitelisted domain
        ]);

        $matches = [
            [
                'start_offset' => 0,
                'end_offset' => mb_strlen($text),
                'source_domain' => 'kenpro.org',
                'is_excluded' => false
            ],
            [
                'start_offset' => 0,
                'end_offset' => 20,
                'source_domain' => 'wikipedia.org',
                'is_excluded' => false
            ]
        ];

        // Should ignore the kenpro.org match because it's in excluded_domains
        $score = $similarityService->calculateNetSimilarity($text, $matches, $scan);

        // Only wikipedia (20 chars / ~4 words out of 16 words) should count
        $this->assertLessThan(50.0, $score);
    }

    public function test_citation_exclusion_preprocessor(): void
    {
        $preprocessor = new AcademicTextPreprocessor();

        $text = "Quantitative methodology ensures high structural validity.\n\n" .
            "(Orodho & Kombo, 2002)\n\n" .
            "Empirical data collection was conducted across Nairobi County.";

        $res = $preprocessor->preprocess($text, true, true, 5, true);

        $citationSeg = collect($res['segments'])->firstWhere('is_citation', true);
        $this->assertNotNull($citationSeg);
        $this->assertTrue($citationSeg['is_excluded']);
        $this->assertEquals('In-Text Citation', $citationSeg['exclusion_reason']);
    }
}
