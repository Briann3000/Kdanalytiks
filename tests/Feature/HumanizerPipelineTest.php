<?php

namespace Tests\Feature;

use App\Services\AiHumanizerService;
use App\Services\Ai\UnifiedAiDetectorService;
use App\Services\GroqStreamingClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class HumanizerPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_humanizer_process_endpoint_returns_valid_structure(): void
    {
        $user = \App\Models\User::factory()->create();

        $groqMock = Mockery::mock(GroqStreamingClient::class);
        $groqMock->shouldReceive('streamChatCompletion')
            ->andReturn([
                'content' => 'This is humanized empirical prose. It preserves (Smith et al., 2021) data.',
                'usage' => ['total_tokens' => 50]
            ]);

        $this->app->instance(GroqStreamingClient::class, $groqMock);

        $response = $this->actingAs($user)->postJson(route('humanizer.process'), [
            'text' => 'In today\'s rapidly evolving digital landscape, artificial intelligence plays a pivotal role. Furthermore, it is crucial to recognize that (Smith et al., 2021) highlighted this.',
            'mode' => 'academic',
            'intensity' => 'medium'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'original_analysis' => [
                'aiProbability',
                'perplexity',
                'burstiness',
                'riskLevel',
            ],
            'humanized_text',
            'humanized_analysis' => [
                'aiProbability',
                'perplexity',
                'burstiness',
                'riskLevel',
            ]
        ]);
    }

    public function test_max_text_validation_rejects_oversized_text(): void
    {
        $user = \App\Models\User::factory()->create();

        $oversizedText = str_repeat('A large chunk of words and sentences. ', 900); // > 30,000 characters

        $response = $this->actingAs($user)->postJson(route('humanizer.process'), [
            'text' => $oversizedText,
            'mode' => 'standard',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['text']);
    }

    public function test_quota_limit_blocks_exceeded_user_on_humanize(): void
    {
        $user = \App\Models\User::factory()->create([
            'proofread_count' => 10, // Max for early access Free tier is 5
        ]);

        $response = $this->actingAs($user)->postJson(route('humanizer.process'), [
            'text' => 'This is an AI text waiting to be humanized.',
            'analyze_only' => false,
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'Quota exceeded',
        ]);
    }

    public function test_quota_limit_allows_analyze_only_even_if_quota_reached(): void
    {
        $user = \App\Models\User::factory()->create([
            'proofread_count' => 10,
        ]);

        $groqMock = Mockery::mock(GroqStreamingClient::class);
        $this->app->instance(GroqStreamingClient::class, $groqMock);

        $response = $this->actingAs($user)->postJson(route('humanizer.process'), [
            'text' => 'This is a short sample to test AI percentage detection.',
            'analyze_only' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['analysis']);
    }

    public function test_detector_does_not_split_sentences_on_academic_abbreviations(): void
    {
        $detector = new UnifiedAiDetectorService();
        $sample = "According to empirical research (Smith et al., 2021), the hypothesis holds. Furthermore, i.e., in specific controlled environments, results vary by 14.5% compared to baseline.";
        
        $analysis = $detector->analyze($sample);
        
        $breakdown = $analysis['sentenceBreakdown'];
        // Should produce exactly 2 sentences, not 5+ fragmented chunks
        $this->assertCount(2, $breakdown);
        $this->assertStringContainsString('et al.', $breakdown[0]['text']);
        $this->assertStringContainsString('i.e.', $breakdown[1]['text']);
    }

    public function test_pivot_translate_completes_forward_and_reverse_cycle(): void
    {
        $groqMock = Mockery::mock(GroqStreamingClient::class);
        
        // Call 1: English -> Spanish
        $groqMock->shouldReceive('streamChatCompletion')
            ->once()
            ->andReturn([
                'content' => 'En el panorama educativo contemporáneo, los sistemas adaptativos mejoran el rendimiento (García, 2022).',
                'usage' => ['total_tokens' => 30]
            ]);

        // Call 2: Spanish -> English
        $groqMock->shouldReceive('streamChatCompletion')
            ->once()
            ->andReturn([
                'content' => 'In contemporary educational environments, adaptive systems enhance overall performance (García, 2022).',
                'usage' => ['total_tokens' => 30]
            ]);

        $service = new AiHumanizerService($groqMock);
        $result = $service->pivotTranslate(
            'In today\'s education landscape, adaptive systems improve overall performance (García, 2022).',
            'Spanish'
        );

        $this->assertStringContainsString('adaptive systems', $result);
        $this->assertStringContainsString('(García, 2022)', $result);
    }

    public function test_pivot_translate_falls_back_gracefully_on_failure(): void
    {
        $groqMock = Mockery::mock(GroqStreamingClient::class);
        $groqMock->shouldReceive('streamChatCompletion')
            ->andThrow(new \RuntimeException('Groq temporary failure'));

        $service = new AiHumanizerService($groqMock);
        $original = 'A robust paragraph about machine learning.';
        
        $result = $service->pivotTranslate($original, 'Spanish');
        $this->assertEquals($original, $result);
    }

    public function test_humanize_text_runs_full_pipeline_with_low_ai_score(): void
    {
        $groqMock = Mockery::mock(GroqStreamingClient::class);
        
        $groqMock->shouldReceive('streamChatCompletion')
            ->andReturn([
                'content' => 'Adaptive learning structures actively shift student outcomes, as documented in empirical trials.',
                'usage' => ['total_tokens' => 40]
            ]);

        $service = new AiHumanizerService($groqMock);
        $output = $service->humanizeText(
            'Furthermore, it is pivotal to delve into how artificial intelligence revolutionizes schools.',
            'academic',
            'high'
        );

        $this->assertNotEmpty($output);
        $this->assertStringContainsString('Adaptive learning structures', $output);
    }

    public function test_docx_download_endpoint_generates_file(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->post(route('humanizer.download'), [
            'text' => "Paragraph 1 of research analysis.\n\nParagraph 2 with empirical findings.",
            'filename' => 'test_document'
        ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }
}
