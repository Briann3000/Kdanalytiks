<?php

namespace Tests\Feature;

use App\Http\Controllers\SurveyController;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Response;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyReportAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected SurveyController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->controller = new SurveyController();
    }

    /** @test */
    public function it_treats_missing_tokens_as_skipped_and_computes_stats_correctly()
    {
        $survey = Survey::factory()->create([
            'created_by' => $this->user->id,
            'organization_id' => $this->user->organization_id,
            'json_schema' => json_encode([
                [
                    'name' => 'gender',
                    'label' => 'Gender',
                    'type' => 'radio',
                ]
            ])
        ]);

        // Create responses with Male, Female, and missing values ('-', 'N/A', '')
        $values = ['Male', 'Female', 'Male', '-', 'N/A', 'Female', 'Male', '-'];
        foreach ($values as $val) {
            $resp = Response::create([
                'survey_id' => $survey->id,
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);
            Answer::create([
                'response_id' => $resp->id,
                'question_id' => null,
                'value' => json_encode([
                    ['name' => 'gender', 'userData' => $val]
                ])
            ]);
        }

        $responses = $survey->responses()->with('answers')->get();
        $analyticalData = $this->controller->getAnalyticalData($survey, $responses);
        $item = $analyticalData['analysis'][0];

        $this->assertEquals(5, $item['answered_count']);
        $this->assertEquals(3, $item['missing_count']);

        // Check stats array
        $stats = collect($item['stats']);
        $skippedStat = $stats->firstWhere('value', 'Missing') ?? $stats->firstWhere('value', 'Skipped');
        $this->assertNotNull($skippedStat);
        $this->assertEquals(3, $skippedStat['count']);
        $this->assertTrue($skippedStat['is_missing']);

        // Frequency count for Male and Female
        $maleStat = $stats->firstWhere('value', 'Male');
        $this->assertNotNull($maleStat);
        $this->assertEquals(3, $maleStat['count']);

        $femaleStat = $stats->firstWhere('value', 'Female');
        $this->assertNotNull($femaleStat);
        $this->assertEquals(2, $femaleStat['count']);

        // Ensure '-' or 'N/A' are not separate categories
        $this->assertNull($stats->firstWhere('value', '-'));
        $this->assertNull($stats->firstWhere('value', 'N/A'));
    }

    /** @test */
    public function it_suppresses_identifier_columns_from_charting_and_analysis()
    {
        $survey = Survey::factory()->create([
            'created_by' => $this->user->id,
            'organization_id' => $this->user->organization_id,
            'json_schema' => json_encode([
                [
                    'name' => 'id_col',
                    'label' => '#1 ID',
                    'type' => 'number',
                ],
                [
                    'name' => 'respondent_id',
                    'label' => 'Respondent ID',
                    'type' => 'text',
                ],
                [
                    'name' => 'score',
                    'label' => 'Customer Score',
                    'type' => 'number',
                ]
            ])
        ]);

        $resp = Response::create([
            'survey_id' => $survey->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);
        Answer::create([
            'response_id' => $resp->id,
            'question_id' => null,
            'value' => json_encode([
                ['name' => 'id_col', 'userData' => 1],
                ['name' => 'respondent_id', 'userData' => 'RESP-001'],
                ['name' => 'score', 'userData' => 85],
            ])
        ]);

        $responses = $survey->responses()->with('answers')->get();
        $analyticalData = $this->controller->getAnalyticalData($survey, $responses);
        $analysis = collect($analyticalData['analysis']);

        $idCol = $analysis->firstWhere('id', 'id_col');
        $this->assertFalse($idCol['isChartable']);
        $this->assertFalse($idCol['isAnalyzable']);

        $respId = $analysis->firstWhere('id', 'respondent_id');
        $this->assertFalse($respId['isChartable']);
        $this->assertFalse($respId['isAnalyzable']);

        $scoreCol = $analysis->firstWhere('id', 'score');
        $this->assertTrue($scoreCol['isChartable']);
    }

    /** @test */
    public function it_auto_bins_numeric_variables_with_more_than_15_unique_values()
    {
        $survey = Survey::factory()->create([
            'created_by' => $this->user->id,
            'organization_id' => $this->user->organization_id,
            'json_schema' => json_encode([
                [
                    'name' => 'altitude',
                    'label' => 'Altitude (Meters)',
                    'type' => 'decimal',
                ]
            ])
        ]);

        // Generate 30 distinct numeric values like "1000M", "1015M", "1030M", ...
        for ($i = 0; $i < 30; $i++) {
            $resp = Response::create([
                'survey_id' => $survey->id,
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);
            $val = (1000 + ($i * 15)) . 'M';
            Answer::create([
                'response_id' => $resp->id,
                'question_id' => null,
                'value' => json_encode([
                    ['name' => 'altitude', 'userData' => $val]
                ])
            ]);
        }

        $responses = $survey->responses()->with('answers')->get();
        $analyticalData = $this->controller->getAnalyticalData($survey, $responses);
        $item = $analyticalData['analysis'][0];

        $this->assertTrue($item['isChartable']);
        $this->assertNotNull($item['summary_stats']);
        $this->assertEquals(30, $item['summary_stats']['count']);
        $this->assertEquals('M', $item['summary_stats']['unit']);
        $this->assertGreaterThan(0, $item['summary_stats']['mean']);
        $this->assertGreaterThan(0, $item['summary_stats']['std_dev']);

        // Since 30 unique values > 15, the categories in stats should be binned (typically 5 to 7 bins)
        $categories = collect($item['stats'])->filter(fn($s) => empty($s['is_missing']));
        $this->assertLessThanOrEqual(10, $categories->count());
        $this->assertGreaterThanOrEqual(4, $categories->count());

        // Check that category labels are interval formatted
        $firstCategory = $categories->first()['value'];
        $this->assertStringContainsString('–', $firstCategory);
    }

    /** @test */
    public function it_keeps_discrete_values_when_unique_numbers_are_15_or_less()
    {
        $survey = Survey::factory()->create([
            'created_by' => $this->user->id,
            'organization_id' => $this->user->organization_id,
            'json_schema' => json_encode([
                [
                    'name' => 'household_size',
                    'label' => 'Household Size',
                    'type' => 'number',
                ]
            ])
        ]);

        // Generate responses with values 1, 2, 3, 4, 5 (5 unique values <= 15)
        for ($i = 0; $i < 20; $i++) {
            $resp = Response::create([
                'survey_id' => $survey->id,
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);
            $val = ($i % 5) + 1; // 1 to 5
            Answer::create([
                'response_id' => $resp->id,
                'question_id' => null,
                'value' => json_encode([
                    ['name' => 'household_size', 'userData' => $val]
                ])
            ]);
        }

        $responses = $survey->responses()->with('answers')->get();
        $analyticalData = $this->controller->getAnalyticalData($survey, $responses);
        $item = $analyticalData['analysis'][0];

        $this->assertTrue($item['isChartable']);
        $this->assertNotNull($item['summary_stats']);
        $this->assertEquals(20, $item['summary_stats']['count']);
        $this->assertEquals(3.0, $item['summary_stats']['mean']);
        $this->assertEquals(1.0, $item['summary_stats']['min']);
        $this->assertEquals(5.0, $item['summary_stats']['max']);

        // Since 5 unique values <= 15, stats categories should be the discrete numbers 1, 2, 3, 4, 5
        $categories = collect($item['stats'])->filter(fn($s) => empty($s['is_missing']));
        $this->assertEquals(5, $categories->count());
        $values = $categories->pluck('value')->all();
        $this->assertEquals(['1', '2', '3', '4', '5'], $values);
    }

    /** @test */
    public function it_clips_extreme_outliers_in_auto_binning_correctly()
    {
        $survey = Survey::factory()->create([
            'created_by' => $this->user->id,
            'organization_id' => $this->user->organization_id,
            'json_schema' => json_encode([
                [
                    'name' => 'altitude',
                    'label' => 'Altitude',
                    'type' => 'number',
                ]
            ])
        ]);

        // 30 responses around 1200 - 1350M, and 2 extreme outlier rows (123790M, 138660M)
        for ($i = 0; $i < 30; $i++) {
            $resp = Response::create([
                'survey_id' => $survey->id,
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);
            $val = (1200 + $i * 5) . 'M';
            Answer::create([
                'response_id' => $resp->id,
                'question_id' => null,
                'value' => json_encode([
                    ['name' => 'altitude', 'userData' => $val]
                ])
            ]);
        }

        // Add 2 extreme outlier responses
        foreach (['123790M', '138660M'] as $outlier) {
            $resp = Response::create([
                'survey_id' => $survey->id,
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);
            Answer::create([
                'response_id' => $resp->id,
                'question_id' => null,
                'value' => json_encode([
                    ['name' => 'altitude', 'userData' => $outlier]
                ])
            ]);
        }

        $responses = $survey->responses()->with('answers')->get();
        $analyticalData = $this->controller->getAnalyticalData($survey, $responses);
        $item = $analyticalData['analysis'][0];

        $categories = collect($item['stats'])->filter(fn($s) => empty($s['is_missing']));
        // Should have main binned ranges around 1200 - 1350M and an overflow bin '> 1,350 M' or similar
        $this->assertLessThanOrEqual(10, $categories->count());
        $labels = $categories->pluck('value')->all();
        $hasOverflow = collect($labels)->contains(fn($l) => str_starts_with($l, '>') || str_starts_with($l, 'Above') || str_contains($l, '1,3') || str_contains($l, '1,4'));
        $this->assertTrue($hasOverflow);
    }

    /** @test */
    public function it_formats_microdegree_coordinates_correctly()
    {
        $latFormatted = SurveyController::formatCoordinateValue('15460938', 'LATITUDE');
        $this->assertEquals('1.546094° N', $latFormatted);

        $lngFormatted = SurveyController::formatCoordinateValue('3768621314', 'LONGITUDE');
        $this->assertEquals('37.686213° E', $lngFormatted);

        $commaPair = SurveyController::formatCoordinateValue('-0.1546, 37.6862', 'GPS');
        $this->assertEquals('📍 -0.1546, 37.6862', $commaPair);
    }

    /** @test */
    public function it_maps_extent_scales_to_numeric_likert_values()
    {
        $reflection = new \ReflectionClass(SurveyController::class);
        $method = $reflection->getMethod('convertValueToNumeric');
        $method->setAccessible(true);

        $uniqueVals = [];
        $val1 = $method->invokeArgs($this->controller, ['To no extent', &$uniqueVals, null]);
        $this->assertEquals(1.0, $val1);

        $val2 = $method->invokeArgs($this->controller, ['To some extent', &$uniqueVals, null]);
        $this->assertEquals(2.0, $val2);

        $val3 = $method->invokeArgs($this->controller, ['To a moderate extent', &$uniqueVals, null]);
        $this->assertEquals(3.0, $val3);

        $val4 = $method->invokeArgs($this->controller, ['To a great extent', &$uniqueVals, null]);
        $this->assertEquals(4.0, $val4);

        $val5 = $method->invokeArgs($this->controller, ['To a very great extent', &$uniqueVals, null]);
        $this->assertEquals(5.0, $val5);

        $valAlways = $method->invokeArgs($this->controller, ['Always', &$uniqueVals, null]);
        $this->assertEquals(5.0, $valAlways);

        $valNever = $method->invokeArgs($this->controller, ['Never', &$uniqueVals, null]);
        $this->assertEquals(1.0, $valNever);
    }

    /** @test */
    public function it_handles_chart_image_caching_and_mocking_gracefully()
    {
        $dummyConfig = [
            'type' => 'bar',
            'data' => [
                'labels' => ['A', 'B'],
                'datasets' => [['data' => [10, 20]]]
            ]
        ];

        // Ensure method returns null or valid file path without throwing exceptions
        $path = SurveyController::fetchChartImageLocalPath($dummyConfig, null);
        if ($path !== null) {
            $this->assertFileExists($path);
        } else {
            $this->assertNull($path);
        }
    }

    /** @test */
    public function it_handles_large_bulk_surveys_with_many_questions_without_cut_offs()
    {
        $schema = [];
        for ($i = 1; $i <= 50; $i++) {
            $schema[] = [
                'name' => 'q_' . $i,
                'label' => 'Question Item ' . $i,
                'type' => 'radio-group',
                'values' => [
                    ['label' => 'Agree', 'value' => 'Agree'],
                    ['label' => 'Neutral', 'value' => 'Neutral'],
                    ['label' => 'Disagree', 'value' => 'Disagree']
                ]
            ];
        }

        $user = \App\Models\User::factory()->create();
        $survey = \App\Models\Survey::factory()->create([
            'created_by' => $user->id,
            'json_schema' => json_encode($schema),
            'reporting_style' => 'apa'
        ]);

        $responses = collect();
        for ($r = 1; $r <= 5; $r++) {
            $answersData = [];
            for ($i = 1; $i <= 50; $i++) {
                $answersData[] = [
                    'name' => 'q_' . $i,
                    'userData' => ($r % 2 === 0) ? 'Agree' : 'Neutral'
                ];
            }
            $resp = new \App\Models\Response([
                'survey_id' => $survey->id,
                'guest_name' => 'Respondent ' . $r
            ]);
            $resp->id = $r;
            $ans = new \App\Models\Answer([
                'value' => json_encode($answersData)
            ]);
            $resp->setRelation('answers', collect([$ans]));
            $responses->push($resp);
        }

        $this->actingAs($user);
        $analyticalData = $this->controller->getAnalyticalData($survey, $responses, true, true);
        $analysis = $analyticalData['analysis'];

        // Ensure all 50 questions were analyzed and none cut off
        $this->assertCount(50, $analysis);
        foreach ($analysis as $item) {
            $this->assertNotEmpty($item['aiInsight']);
            $this->assertStringContainsString('Descriptive', $item['aiInsight']);
        }
    }
}

