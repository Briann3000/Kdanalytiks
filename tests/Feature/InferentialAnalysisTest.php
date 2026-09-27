<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Response;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InferentialAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Survey $survey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);

        // Create survey with JSON schema having categorical and numeric variables
        $this->survey = Survey::factory()->create([
            'created_by' => $this->user->id,
            'organization_id' => $this->user->organization_id,
            'json_schema' => json_encode([
                ['name' => 'gender', 'label' => 'Gender', 'type' => 'radio'],
                ['name' => 'satisfaction', 'label' => 'Satisfaction', 'type' => 'radio'],
                ['name' => 'score1', 'label' => 'Performance Score 1', 'type' => 'number'],
                ['name' => 'score2', 'label' => 'Performance Score 2', 'type' => 'number'],
                ['name' => 'score3', 'label' => 'Performance Score 3', 'type' => 'number'],
                ['name' => 'department', 'label' => 'Department', 'type' => 'select'],
            ])
        ]);

        // Seed 10 responses with rich data
        $dataset = [
            ['gender' => 'Male', 'satisfaction' => 'Satisfied', 'score1' => 85, 'score2' => 88, 'score3' => 80, 'department' => 'Sales'],
            ['gender' => 'Female', 'satisfaction' => 'Satisfied', 'score1' => 90, 'score2' => 92, 'score3' => 89, 'department' => 'Marketing'],
            ['gender' => 'Male', 'satisfaction' => 'Dissatisfied', 'score1' => 60, 'score2' => 65, 'score3' => 62, 'department' => 'Sales'],
            ['gender' => 'Female', 'satisfaction' => 'Dissatisfied', 'score1' => 65, 'score2' => 70, 'score3' => 68, 'department' => 'Support'],
            ['gender' => 'Male', 'satisfaction' => 'Satisfied', 'score1' => 82, 'score2' => 80, 'score3' => 84, 'department' => 'Support'],
            ['gender' => 'Female', 'satisfaction' => 'Satisfied', 'score1' => 88, 'score2' => 85, 'score3' => 87, 'department' => 'Marketing'],
            ['gender' => 'Male', 'satisfaction' => 'Dissatisfied', 'score1' => 58, 'score2' => 60, 'score3' => 59, 'department' => 'Sales'],
            ['gender' => 'Female', 'satisfaction' => 'Satisfied', 'score1' => 92, 'score2' => 95, 'score3' => 91, 'department' => 'Marketing'],
            ['gender' => 'Male', 'satisfaction' => 'Satisfied', 'score1' => 78, 'score2' => 76, 'score3' => 79, 'department' => 'Support'],
            ['gender' => 'Female', 'satisfaction' => 'Dissatisfied', 'score1' => 70, 'score2' => 72, 'score3' => 71, 'department' => 'Sales'],
        ];

        foreach ($dataset as $row) {
            $resp = Response::create([
                'survey_id' => $this->survey->id,
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);
            $userData = [];
            foreach ($row as $k => $v) {
                $userData[] = ['name' => $k, 'userData' => $v];
            }
            Answer::create([
                'response_id' => $resp->id,
                'question_id' => null,
                'value' => json_encode($userData)
            ]);
        }
    }

    /** @test */
    public function it_runs_crosstab_with_totals_and_expected_matrices()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $this->survey), [
                'method' => 'crosstab',
                'row' => 'gender',
                'col' => 'satisfaction',
            ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertEquals('crosstab', $data['method']);
        $this->assertArrayHasKey('totalPercentages', $data);
        $this->assertArrayHasKey('colTotalPercentages', $data);
        $this->assertArrayHasKey('expectedMatrix', $data);
        $this->assertArrayHasKey('residuals', $data);
        $this->assertEquals(10, $data['grandTotal']);
    }

    /** @test */
    public function it_runs_chisquare_with_yates_fisher_and_symmetric_measures()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $this->survey), [
                'method' => 'chisquare',
                'row' => 'gender',
                'col' => 'satisfaction',
            ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertEquals('chisquare', $data['method']);
        $this->assertArrayHasKey('chiSquare', $data);
        $this->assertArrayHasKey('df', $data);
        $this->assertArrayHasKey('pValue', $data);
        $this->assertArrayHasKey('cramersV', $data);
        $this->assertArrayHasKey('phi', $data);
        $this->assertArrayHasKey('contingencyCoeff', $data);

        // Gender x Satisfaction is 2x2
        $this->assertTrue($data['isTwoByTwo']);
        $this->assertArrayHasKey('yatesChiSquare', $data);
        $this->assertArrayHasKey('yatesPValue', $data);
        $this->assertArrayHasKey('fisherExact2Sided', $data);
        $this->assertArrayHasKey('fisherExact1Sided', $data);

        // Symmetric Measures table
        $this->assertArrayHasKey('symmetricMeasures', $data);
        $this->assertCount(3, $data['symmetricMeasures']);
        $this->assertEquals('Phi', $data['symmetricMeasures'][0]['measure']);
        $this->assertEquals("Cramer's V", $data['symmetricMeasures'][1]['measure']);
        $this->assertEquals('Contingency Coefficient', $data['symmetricMeasures'][2]['measure']);

        // Crosstab matrix and percentages for Chi-Square
        $this->assertArrayHasKey('matrix', $data);
        $this->assertArrayHasKey('expectedMatrix', $data);
        $this->assertArrayHasKey('rowPercentages', $data);
        $this->assertArrayHasKey('colPercentages', $data);
        $this->assertArrayHasKey('totalPercentages', $data);
        $this->assertArrayHasKey('colTotalPercentages', $data);
        $this->assertArrayHasKey('assumptionWarning', $data);
        $this->assertArrayHasKey('is_violated', $data['assumptionWarning']);
        $this->assertArrayHasKey('cells_under_5_pct', $data['assumptionWarning']);
        $this->assertArrayHasKey('min_expected', $data['assumptionWarning']);
    }

    /** @test */
    public function it_runs_cronbach_alpha_with_item_stats_inter_item_matrix_and_smc()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $this->survey), [
                'method' => 'cronbach',
                'items' => 'score1,score2,score3',
            ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertEquals('cronbach', $data['method']);
        $this->assertArrayHasKey('alpha', $data);
        $this->assertArrayHasKey('std_alpha', $data);
        $this->assertEquals(3, $data['k_items']);
        $this->assertEquals(10, $data['valid_n']);

        // Item Descriptives
        $this->assertArrayHasKey('item_descriptives', $data);
        $this->assertCount(3, $data['item_descriptives']);
        $this->assertArrayHasKey('mean', $data['item_descriptives'][0]);
        $this->assertArrayHasKey('stdDev', $data['item_descriptives'][0]);

        // Inter-Item Correlation Matrix
        $this->assertArrayHasKey('inter_item_matrix', $data);
        $this->assertEquals(1.0, $data['inter_item_matrix']['score1']['score1']);

        // Item-Total Statistics has squared multiple correlation
        $this->assertArrayHasKey('item_stats', $data);
        $this->assertArrayHasKey('squared_multiple_corr', $data['item_stats'][0]);

        // Scale Statistics
        $this->assertArrayHasKey('scale_statistics', $data);
        $this->assertArrayHasKey('mean', $data['scale_statistics']);
        $this->assertArrayHasKey('variance', $data['scale_statistics']);
        $this->assertArrayHasKey('stdDev', $data['scale_statistics']);
    }

    /** @test */
    public function it_runs_ttest_with_accurate_ci_hedges_g_and_one_tailed_p()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $this->survey), [
                'method' => 'ttest',
                'dep' => 'score1',
                'group' => 'gender',
            ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('leveneF', $data);
        $this->assertArrayHasKey('leveneSig', $data);
        $this->assertArrayHasKey('tValue', $data);
        $this->assertArrayHasKey('df', $data);
        $this->assertArrayHasKey('pValue', $data);
        $this->assertArrayHasKey('pValue1Tailed', $data);
        $this->assertArrayHasKey('cohensD', $data);
        $this->assertArrayHasKey('cohensDEffect', $data);
        $this->assertArrayHasKey('dEffectLabel', $data);
        $this->assertArrayHasKey('hedgesG', $data);
        $this->assertArrayHasKey('hedgesGEffect', $data);
        $this->assertArrayHasKey('gEffectLabel', $data);
        $this->assertArrayHasKey('pValueWelch1Tailed', $data);
        $this->assertArrayHasKey('ciLower', $data);
        $this->assertArrayHasKey('ciUpper', $data);
    }

    /** @test */
    public function it_runs_correlation_with_spearman_descriptives_and_scatter_data()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $this->survey), [
                'method' => 'correlation',
                'varX' => 'score1',
                'varY' => 'score2',
            ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('r', $data);
        $this->assertArrayHasKey('r2', $data);
        $this->assertArrayHasKey('pValue', $data);
        $this->assertArrayHasKey('spearmanRho', $data);
        $this->assertArrayHasKey('spearmanPValue', $data);
        $this->assertArrayHasKey('descriptives', $data);
        $this->assertCount(2, $data['descriptives']);
        $this->assertArrayHasKey('scatterPoints', $data);
        $this->assertArrayHasKey('trendline', $data);
    }

    /** @test */
    public function it_runs_anova_with_levene_test_and_post_hoc_comparisons()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $this->survey), [
                'method' => 'anova',
                'dep' => 'score1',
                'group' => 'department',
            ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('fValue', $data);
        $this->assertArrayHasKey('pValue', $data);
        $this->assertArrayHasKey('etaSquared', $data);

        // Levene test for homogeneity of variance
        $this->assertArrayHasKey('levene', $data);
        $this->assertArrayHasKey('statistic', $data['levene']);
        $this->assertArrayHasKey('df1', $data['levene']);
        $this->assertArrayHasKey('df2', $data['levene']);
        $this->assertArrayHasKey('sig', $data['levene']);

        // Post-Hoc comparisons
        $this->assertArrayHasKey('postHoc', $data);
        $this->assertNotEmpty($data['postHoc']);
        $this->assertArrayHasKey('meanDiff', $data['postHoc'][0]);
        $this->assertArrayHasKey('sig', $data['postHoc'][0]);
    }

    /** @test */
    public function it_runs_simple_regression_with_durbin_watson_and_residuals_stats()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $this->survey), [
                'method' => 'regression',
                'dep' => 'score2',
                'ind' => 'score1',
            ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('r', $data);
        $this->assertArrayHasKey('r2', $data);
        $this->assertArrayHasKey('adjR2', $data);
        $this->assertArrayHasKey('durbinWatson', $data);
        $this->assertArrayHasKey('residualsStats', $data);
        $this->assertArrayHasKey('scatterPoints', $data);
        $this->assertArrayHasKey('trendline', $data);

        // Coefficients beta and CI
        $this->assertArrayHasKey('coefficients', $data);
        $this->assertArrayHasKey('beta', $data['coefficients']['slope']);
    }

    /** @test */
    public function it_runs_multiple_regression_with_vif_tolerance_and_durbin_watson()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $this->survey), [
                'method' => 'regression_multiple',
                'dep' => 'score3',
                'ind' => 'score1,score2',
            ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('r', $data);
        $this->assertArrayHasKey('r2', $data);
        $this->assertArrayHasKey('durbinWatson', $data);
        $this->assertArrayHasKey('residualsStats', $data);
        $this->assertArrayHasKey('equation', $data);

        // Coefficients have tolerance and VIF
        $this->assertArrayHasKey('coefficients', $data);
        $slopes = array_filter($data['coefficients'], fn($c) => $c['variable'] !== '(Constant)');
        $firstSlope = reset($slopes);
        $this->assertArrayHasKey('tolerance', $firstSlope);
        $this->assertArrayHasKey('vif', $firstSlope);
    }

    /** @test */
    public function it_handles_matrix_likert_grid_sub_items_and_resolves_scale_labels_and_numeric_values()
    {
        $matrixSurvey = Survey::factory()->create([
            'created_by' => $this->user->id,
            'organization_id' => $this->user->organization_id,
            'json_schema' => json_encode([
                [
                    'name' => 'grid_feedback',
                    'label' => 'Service Quality Evaluation',
                    'type' => 'matrix',
                    'rows' => [
                        ['id' => 'r1', 'value' => 'r1', 'label' => 'Promptness of Service'],
                        ['id' => 'r2', 'value' => 'r2', 'label' => 'Staff Professionalism'],
                        ['id' => 'r3', 'value' => 'r3', 'label' => 'Issue Resolution Quality'],
                    ],
                    'columns' => [
                        ['id' => 'scale-1', 'value' => 'scale-1', 'label' => 'Strongly Disagree'],
                        ['id' => 'scale-2', 'value' => 'scale-2', 'label' => 'Disagree'],
                        ['id' => 'scale-3', 'value' => 'scale-3', 'label' => 'Neutral'],
                        ['id' => 'scale-4', 'value' => 'scale-4', 'label' => 'Agree'],
                        ['id' => 'scale-5', 'value' => 'scale-5', 'label' => 'Strongly Agree'],
                    ]
                ],
                [
                    'name' => 'tier',
                    'label' => 'Customer Tier',
                    'type' => 'radio',
                    'values' => [
                        ['label' => 'Standard', 'value' => 'standard'],
                        ['label' => 'Premium', 'value' => 'premium'],
                    ]
                ]
            ])
        ]);

        // Seed 10 responses with matrix JSON answers
        $matrixData = [
            ['r1' => 'scale-4', 'r2' => 'scale-5', 'r3' => 'scale-4', 'tier' => 'premium'],
            ['r1' => 'scale-5', 'r2' => 'scale-5', 'r3' => 'scale-5', 'tier' => 'premium'],
            ['r1' => 'scale-4', 'r2' => 'scale-4', 'r3' => 'scale-4', 'tier' => 'premium'],
            ['r1' => 'scale-5', 'r2' => 'scale-4', 'r3' => 'scale-5', 'tier' => 'premium'],
            ['r1' => 'scale-4', 'r2' => 'scale-4', 'r3' => 'scale-5', 'tier' => 'premium'],
            ['r1' => 'scale-2', 'r2' => 'scale-3', 'r3' => 'scale-2', 'tier' => 'standard'],
            ['r1' => 'scale-1', 'r2' => 'scale-2', 'r3' => 'scale-1', 'tier' => 'standard'],
            ['r1' => 'scale-3', 'r2' => 'scale-3', 'r3' => 'scale-2', 'tier' => 'standard'],
            ['r1' => 'scale-2', 'r2' => 'scale-2', 'r3' => 'scale-3', 'tier' => 'standard'],
            ['r1' => 'scale-3', 'r2' => 'scale-4', 'r3' => 'scale-3', 'tier' => 'standard'],
        ];

        foreach ($matrixData as $item) {
            $resp = Response::create([
                'survey_id' => $matrixSurvey->id,
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);
            $userData = [
                ['name' => 'grid_feedback', 'userData' => [
                    'r1' => $item['r1'],
                    'r2' => $item['r2'],
                    'r3' => $item['r3'],
                ]],
                ['name' => 'tier', 'userData' => $item['tier']]
            ];
            Answer::create([
                'response_id' => $resp->id,
                'question_id' => null,
                'value' => json_encode($userData)
            ]);
        }

        // 1. Crosstab on sub-item (grid_feedback__r1) vs tier
        $crosstabResp = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $matrixSurvey), [
                'method' => 'crosstab',
                'row' => 'grid_feedback__r1',
                'col' => 'tier',
            ]);

        $crosstabResp->assertOk();
        $ctData = $crosstabResp->json();
        $this->assertTrue($ctData['success']);
        // Verify labels are human-readable (e.g. 'Agree', 'Strongly Agree', 'Disagree', etc.), not 'scale-xxx'
        foreach ($ctData['rows'] as $rowCat) {
            $this->assertStringNotContainsString('scale-', $rowCat);
        }

        // 1b. Crosstab using parent matrix variable with chart- prefix
        $parentCtResp = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $matrixSurvey), [
                'method' => 'crosstab',
                'row' => 'chart-grid_feedback',
                'col' => 'chart-tier',
            ]);

        $parentCtResp->assertOk();
        $parentCtData = $parentCtResp->json();
        $this->assertTrue($parentCtData['success']);
        foreach ($parentCtData['rows'] as $rowCat) {
            $this->assertStringNotContainsString('scale-', $rowCat);
            $this->assertStringNotContainsString('{"', $rowCat);
        }
        foreach ($parentCtData['columns'] as $colCat) {
            $this->assertStringNotContainsString('scale-', $colCat);
        }

        // 2. Cronbach's Alpha on sub-items (grid_feedback__r1, grid_feedback__r2, grid_feedback__r3)
        $cronbachResp = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $matrixSurvey), [
                'method' => 'cronbach',
                'items' => 'grid_feedback__r1,grid_feedback__r2,grid_feedback__r3',
            ]);

        $cronbachResp->assertOk();
        $cbData = $cronbachResp->json();
        $this->assertTrue($cbData['success']);
        $this->assertEquals(3, $cbData['k_items']);
        $this->assertGreaterThan(0.7, $cbData['alpha']);
        $this->assertCount(3, $cbData['item_stats']);
        // Verify item stats labels are descriptive
        $this->assertStringContainsString('Promptness of Service', $cbData['item_stats'][0]['label']);

        // 3. T-Test on sub-item (dep: grid_feedback__r1, group: tier)
        $ttestResp = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $matrixSurvey), [
                'method' => 'ttest',
                'dep' => 'chart-grid_feedback__r1',
                'group' => 'chart-tier',
            ]);

        $ttestResp->assertOk();
        $ttData = $ttestResp->json();
        $this->assertTrue($ttData['success']);
        $this->assertArrayHasKey('tValue', $ttData);
        $this->assertArrayHasKey('pValue', $ttData);
        $this->assertGreaterThan(0, $ttData['meanDiff']); // Premium mean > Standard mean
        $this->assertCount(2, $ttData['groups']);
    }

    /** @test */
    public function it_deletes_saved_inferential_analysis()
    {
        $saved = \App\Models\SurveyInferentialAnalysis::create([
            'survey_id' => $this->survey->id,
            'user_id' => $this->user->id,
            'method' => 'crosstab',
            'title' => 'Test Analysis To Delete',
            'variables' => 'Gender vs Satisfaction',
            'ai_summary' => 'Test summary',
            'payload' => ['vars' => ['rowVar' => 'gender', 'colVar' => 'satisfaction'], 'data' => []]
        ]);

        $this->assertDatabaseHas('survey_inferential_analyses', ['id' => $saved->id]);

        $deleteResp = $this->actingAs($this->user)
            ->deleteJson(route('surveys.reports.inferential.delete', [$this->survey, $saved->id]));

        $deleteResp->assertOk();
        $this->assertDatabaseMissing('survey_inferential_analyses', ['id' => $saved->id]);
    }

    /** @test */
    public function it_excludes_missing_data_listwise_and_outputs_case_processing_summary()
    {
        // Add 2 responses with incomplete/missing fields
        // Missing satisfaction
        $respMissingCol = Response::create([
            'survey_id' => $this->survey->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);
        Answer::create([
            'response_id' => $respMissingCol->id,
            'question_id' => null,
            'value' => json_encode([['name' => 'gender', 'userData' => 'Male']])
        ]);

        // Missing gender
        $respMissingRow = Response::create([
            'survey_id' => $this->survey->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);
        Answer::create([
            'response_id' => $respMissingRow->id,
            'question_id' => null,
            'value' => json_encode([['name' => 'satisfaction', 'userData' => 'Satisfied']])
        ]);

        // Total responses now = 10 (valid) + 2 (incomplete) = 12

        $crosstabResp = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $this->survey), [
                'method' => 'crosstab',
                'row' => 'gender',
                'col' => 'satisfaction',
            ]);

        $crosstabResp->assertOk();
        $ctData = $crosstabResp->json();

        $this->assertTrue($ctData['success']);
        // [Missing] must not be a row or column
        $this->assertNotContains('[Missing]', $ctData['rows']);
        $this->assertNotContains('[Missing]', $ctData['columns']);
        // Valid sample must be exactly 10, total 12
        $this->assertEquals(10, $ctData['grandTotal']);
        $this->assertEquals(10, $ctData['case_summary']['valid_n']);
        $this->assertEquals(2, $ctData['case_summary']['missing_n']);
        $this->assertEquals(12, $ctData['case_summary']['total_n']);
        $this->assertEquals(83.3, $ctData['case_summary']['valid_pct']);
        $this->assertEquals(16.7, $ctData['case_summary']['missing_pct']);

        // Check Chi-Square as well
        $chiResp = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $this->survey), [
                'method' => 'chisquare',
                'row' => 'gender',
                'col' => 'satisfaction',
            ]);

        $chiResp->assertOk();
        $chiData = $chiResp->json();
        $this->assertNotContains('[Missing]', $chiData['rows']);
        $this->assertNotContains('[Missing]', $chiData['columns']);
        $this->assertEquals(10, $chiData['grandTotal']);
        $this->assertEquals(10, $chiData['case_summary']['valid_n']);
        $this->assertEquals(2, $chiData['case_summary']['missing_n']);
    }

    /** @test */
    public function it_handles_formbuilder_json_string_wrapped_matrix_answers_in_reliability_analysis()
    {
        $matrixSurvey = Survey::factory()->create([
            'created_by' => $this->user->id,
            'organization_id' => $this->user->organization_id,
            'json_schema' => json_encode([
                [
                    'name' => 'construct_finances',
                    'label' => 'Financial Sustainability Perception',
                    'type' => 'likert_matrix',
                    'rows' => [
                        ['id' => 'item_1', 'value' => 'item_1', 'label' => 'Adequate financial resources'],
                        ['id' => 'item_2', 'value' => 'item_2', 'label' => 'Consistent availability of funds'],
                        ['id' => 'item_3', 'value' => 'item_3', 'label' => 'Reliable income sources'],
                    ],
                    'columns' => [
                        ['id' => 'scale_1', 'value' => 'scale_1', 'label' => 'Strongly Disagree'],
                        ['id' => 'scale_2', 'value' => 'scale_2', 'label' => 'Disagree'],
                        ['id' => 'scale_3', 'value' => 'scale_3', 'label' => 'Neutral'],
                        ['id' => 'scale_4', 'value' => 'scale_4', 'label' => 'Agree'],
                        ['id' => 'scale_5', 'value' => 'scale_5', 'label' => 'Strongly Agree'],
                    ]
                ]
            ])
        ]);

        // Seed responses where userData is a JSON string array: ["{\"item_1\":\"scale_4\",\"item_2\":\"scale_5\",\"item_3\":\"scale_4\"}"]
        for ($i = 0; $i < 6; $i++) {
            $resp = Response::create([
                'survey_id' => $matrixSurvey->id,
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);
            $userData = [
                [
                    'name' => 'construct_finances',
                    'userData' => [
                        json_encode([
                            'item_1' => 'scale_4',
                            'item_2' => 'scale_5',
                            'item_3' => 'scale_4',
                        ])
                    ]
                ]
            ];
            Answer::create([
                'response_id' => $resp->id,
                'question_id' => null,
                'value' => json_encode($userData)
            ]);
        }

        $cronbachResp = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $matrixSurvey), [
                'method' => 'cronbach',
                'items' => 'construct_finances__item_1,construct_finances__item_2,construct_finances__item_3',
            ]);

        $cronbachResp->assertOk();
        $cbData = $cronbachResp->json();
        $this->assertTrue($cbData['success']);
        $this->assertEquals(3, $cbData['k_items']);
        $this->assertEquals(6, $cbData['sample_n']);
        $this->assertCount(3, $cbData['item_stats']);
    }

    /** @test */
    public function it_outputs_case_summary_for_ttest_and_blocks_more_than_two_groups_without_subset()
    {
        // 1. Standard t-test returns case summary
        $response = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $this->survey), [
                'method' => 'ttest',
                'dep' => 'score1',
                'group' => 'gender',
            ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('case_summary', $data);
        $this->assertEquals(10, $data['case_summary']['valid_n']);
        $this->assertEquals(10, $data['case_summary']['total_n']);
        $this->assertEquals(100.0, $data['case_summary']['valid_pct']);

        // 2. Guard against >= 3 groups (e.g. department has Sales, Marketing, Support in setup)
        $badResp = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $this->survey), [
                'method' => 'ttest',
                'dep' => 'score1',
                'group' => 'department',
            ]);

        $badResp->assertStatus(400);
        $this->assertStringContainsString('One-Way ANOVA', $badResp->json('message'));

        // Calling ttest with pairwise selection (group1, group2) succeeds
        $pairResp = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $this->survey), [
                'method' => 'ttest',
                'dep' => 'score1',
                'group' => 'department',
                'group1' => 'Sales',
                'group2' => 'Marketing',
            ]);

        $pairResp->assertOk();
        $this->assertTrue($pairResp->json('success'));
        $this->assertEquals('Sales', $pairResp->json('groups.0.name'));
        $this->assertEquals('Marketing', $pairResp->json('groups.1.name'));
    }

    /** @test */
    public function it_sanitizes_uploaded_dataset_values_in_ttest()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $this->survey), [
                'method' => 'ttest',
                'scope' => 'upload',
                'dep' => 'score1',
                'dataset_label' => 'Benchmark',
                'dataset_values' => ['85.5', 'not-a-number', '92.0', '', '78.5', '45.0'],
            ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['success']);
        // Should only have 4 valid numbers
        $this->assertEquals(4, $data['groups'][1]['n']);
        $this->assertEquals('Benchmark', $data['groups'][1]['name']);
    }

    /** @test */
    public function it_calculates_statistically_accurate_p_values_and_confidence_intervals_for_large_df_and_welch()
    {
        // 1. Test external upload dataset with large N to test df > 100
        $benchmarkVals = [];
        // Generate 150 values around 75 with SD ~ 10
        for ($i = 0; $i < 150; $i++) {
            $benchmarkVals[] = 75 + (($i % 11) - 5) * 2;
        }

        $response = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $this->survey), [
                'method' => 'ttest',
                'scope' => 'upload',
                'dep' => 'score1',
                'dataset_label' => 'Large Cohort',
                'dataset_values' => $benchmarkVals,
            ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['success']);

        $df = $data['df'];
        $this->assertGreaterThan(100, $df);

        // p-value must be between 0 and 1 (never negative or NaN)
        $this->assertGreaterThanOrEqual(0.0, $data['pValue']);
        $this->assertLessThanOrEqual(1.0, $data['pValue']);

        // Confidence interval width must be non-zero (ciUpper > ciLower)
        $this->assertGreaterThan($data['ciLower'], $data['ciUpper']);
        $this->assertGreaterThan($data['ciLowerWelch'], $data['ciUpperWelch']);

        // For df > 100, critical t is approximately 1.97 to 1.99
        $expectedMargin = ($data['ciUpper'] - $data['ciLower']) / 2.0;
        $this->assertGreaterThan(0.0, $expectedMargin);
        $this->assertEqualsWithDelta($data['meanDiff'] - $expectedMargin, $data['ciLower'], 0.001);
        $this->assertEqualsWithDelta($data['meanDiff'] + $expectedMargin, $data['ciUpper'], 0.001);
    }

    /** @test */
    public function it_accurately_computes_exact_statistical_distributions_benchmarks()
    {
        $controller = app(\App\Http\Controllers\SurveyController::class);
        $ref = new \ReflectionClass($controller);

        $tProbMethod = $ref->getMethod('tProbability');
        $tProbMethod->setAccessible(true);

        $invTMethod = $ref->getMethod('invTDistribution');
        $invTMethod->setAccessible(true);

        $fProbMethod = $ref->getMethod('fProbability');
        $fProbMethod->setAccessible(true);

        // 1. User screenshot case: t = -0.9019, df = 167
        $pVal1 = $tProbMethod->invoke($controller, -0.9019, 167);
        $this->assertEqualsWithDelta(0.3684, $pVal1, 0.001);

        // 2. User screenshot Welch case: t = -0.8309, df = 17.66
        $pValWelch = $tProbMethod->invoke($controller, -0.8309, 17.66);
        $this->assertEqualsWithDelta(0.4172, $pValWelch, 0.001);

        // 3. Inverse t-distribution critical values
        $tCrit167 = $invTMethod->invoke($controller, 0.05, 167);
        $this->assertEqualsWithDelta(1.9743, $tCrit167, 0.001);

        $tCritWelch = $invTMethod->invoke($controller, 0.05, 17.66);
        $this->assertEqualsWithDelta(2.1037, $tCritWelch, 0.001);

        // 4. F-distribution probability (Levene F = 1.5215, df1 = 1, df2 = 167)
        $fVal = $fProbMethod->invoke($controller, 1.5215, 1, 167);
        $this->assertEqualsWithDelta(0.2191, $fVal, 0.001);
    }

    /** @test */
    public function it_runs_cross_survey_ttest_with_matrix_items_and_array_answers()
    {
        $targetSurvey = Survey::factory()->create([
            'created_by' => $this->user->id,
            'organization_id' => $this->user->organization_id,
            'json_schema' => json_encode([
                [
                    'name' => 'matrix_q',
                    'label' => 'Matrix Question',
                    'type' => 'likert_matrix',
                    'rows' => [
                        ['value' => 'r1', 'label' => 'Sub item 1'],
                        ['value' => 'r2', 'label' => 'Sub item 2']
                    ],
                    'columns' => [
                        ['value' => '1', 'label' => 'Strongly Disagree'],
                        ['value' => '2', 'label' => 'Disagree'],
                        ['value' => '3', 'label' => 'Neutral'],
                        ['value' => '4', 'label' => 'Agree'],
                        ['value' => '5', 'label' => 'Strongly Agree'],
                    ]
                ]
            ])
        ]);

        for ($i = 0; $i < 5; $i++) {
            $resp = Response::create([
                'survey_id' => $targetSurvey->id,
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);
            Answer::create([
                'response_id' => $resp->id,
                'question_id' => null,
                'value' => json_encode([
                    [
                        'name' => 'matrix_q',
                        'userData' => [
                            'r1' => ['4'], // array wrapped
                            'r2' => '5'
                        ]
                    ]
                ])
            ]);
        }

        $response = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $this->survey), [
                'method' => 'ttest',
                'scope' => 'cross_survey',
                'dep' => 'score1',
                'target_survey_id' => $targetSurvey->id,
                'target_dep' => 'matrix_q__r1',
            ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertEquals('cross_survey', $data['scope']);
        $this->assertArrayHasKey('tValue', $data);
        $this->assertArrayHasKey('pValue', $data);
        $this->assertArrayHasKey('groups', $data);
        $this->assertCount(2, $data['groups']);
        $this->assertEquals(5, $data['groups'][1]['n']);
        $this->assertEquals(4.0, $data['groups'][1]['mean']);

        // Assert Case Processing Summary has per-group breakdown
        $this->assertArrayHasKey('case_summary', $data);
        $this->assertArrayHasKey('group1', $data['case_summary']);
        $this->assertArrayHasKey('group2', $data['case_summary']);
        $this->assertArrayHasKey('total', $data['case_summary']);
        $this->assertEquals(5, $data['case_summary']['group2']['valid_n']);
        $this->assertEquals(5, $data['case_summary']['group2']['total_n']);
        $this->assertEquals(0, $data['case_summary']['group2']['missing_n']);
    }

    public function test_safe_delete_inferential_analysis()
    {
        // Deleting non-existent analysis should return success false or 200 without 500 exception
        $response = $this->actingAs($this->user)
            ->deleteJson(route('surveys.reports.inferential.delete', ['survey' => $this->survey, 'analysisId' => 999999]));

        $response->assertOk();
        $this->assertTrue($response->json('success'));
    }

    public function test_anova_cross_survey_single_target()
    {
        $targetSurvey = Survey::factory()->create([
            'created_by' => $this->user->id,
            'json_schema' => json_encode([
                [
                    'type' => 'number',
                    'name' => 'target_score',
                    'label' => 'Target Performance Score',
                ]
            ])
        ]);

        for ($i = 0; $i < 5; $i++) {
            $resp = Response::create([
                'survey_id' => $targetSurvey->id,
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);
            Answer::create([
                'response_id' => $resp->id,
                'question_id' => null,
                'value' => json_encode([
                    ['name' => 'target_score', 'userData' => (string) (75 + $i * 2)]
                ])
            ]);
        }

        $response = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $this->survey), [
                'method' => 'anova',
                'scope' => 'cross_survey',
                'dep' => 'score1',
                'target_survey_id' => $targetSurvey->id,
                'target_dep' => 'target_score',
            ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('fValue', $data);
        $this->assertArrayHasKey('groupStats', $data);
        $this->assertCount(2, $data['groupStats']);
    }

    /** @test */
    public function test_ttest_independent_samples_effect_sizes()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $this->survey), [
                'method' => 'ttest',
                'dep' => 'score1',
                'group' => 'gender',
                'group1' => 'Male',
                'group2' => 'Female',
            ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('effect_sizes', $data);

        $es = $data['effect_sizes'];
        $this->assertIsArray($es);
        $this->assertCount(3, $es);

        $this->assertEquals("Cohen's d", $es[0]['name']);
        $this->assertArrayHasKey('point_estimate', $es[0]);
        $this->assertArrayHasKey('standardizer', $es[0]);
        $this->assertArrayHasKey('ci_lower', $es[0]);
        $this->assertArrayHasKey('ci_upper', $es[0]);

        $this->assertEquals("Hedges' correction", $es[1]['name']);
        $this->assertArrayHasKey('point_estimate', $es[1]);

        $this->assertEquals("Glass's delta", $es[2]['name']);
        $this->assertArrayHasKey('point_estimate', $es[2]);
    }

    /** @test */
    public function test_anova_robust_tests_and_post_hoc_extensions()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $this->survey), [
                'method' => 'anova',
                'dep' => 'score1',
                'group' => 'department',
            ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['success']);

        // Check Robust Tests (Welch & Brown-Forsythe)
        $this->assertArrayHasKey('robust_tests', $data);
        $this->assertArrayHasKey('welch', $data['robust_tests']);
        $this->assertArrayHasKey('brown_forsythe', $data['robust_tests']);
        $this->assertArrayHasKey('statistic', $data['robust_tests']['welch']);
        $this->assertArrayHasKey('df1', $data['robust_tests']['welch']);
        $this->assertArrayHasKey('df2', $data['robust_tests']['welch']);
        $this->assertArrayHasKey('sig', $data['robust_tests']['welch']);

        // Check Games-Howell Post-Hoc Comparisons
        $this->assertArrayHasKey('postHocGamesHowell', $data);
        $this->assertNotEmpty($data['postHocGamesHowell']);
        $firstGh = $data['postHocGamesHowell'][0];
        $this->assertArrayHasKey('groupI', $firstGh);
        $this->assertArrayHasKey('groupJ', $firstGh);
        $this->assertArrayHasKey('meanDiff', $firstGh);
        $this->assertArrayHasKey('stdError', $firstGh);
        $this->assertArrayHasKey('df', $firstGh);
        $this->assertArrayHasKey('sig', $firstGh);

        // Check Homogeneous Subsets
        $this->assertArrayHasKey('homogeneousSubsets', $data);
        $this->assertArrayHasKey('numSubsets', $data['homogeneousSubsets']);
        $this->assertArrayHasKey('rows', $data['homogeneousSubsets']);
        $this->assertArrayHasKey('harmonicN', $data['homogeneousSubsets']);
    }

    /** @test */
    public function test_anova_multi_survey_comparison_builder()
    {
        $targetSurvey1 = Survey::factory()->create([
            'created_by' => $this->user->id,
            'json_schema' => json_encode([
                ['type' => 'number', 'name' => 'dept_score_1', 'label' => 'Dept 1 Score']
            ])
        ]);

        $targetSurvey2 = Survey::factory()->create([
            'created_by' => $this->user->id,
            'json_schema' => json_encode([
                ['type' => 'number', 'name' => 'dept_score_2', 'label' => 'Dept 2 Score']
            ])
        ]);

        foreach ([$targetSurvey1, $targetSurvey2] as $idx => $ts) {
            for ($i = 0; $i < 4; $i++) {
                $resp = Response::create([
                    'survey_id' => $ts->id,
                    'user_id' => $this->user->id,
                    'status' => 'completed',
                ]);
                Answer::create([
                    'response_id' => $resp->id,
                    'question_id' => null,
                    'value' => json_encode([
                        ['name' => ($idx === 0 ? 'dept_score_1' : 'dept_score_2'), 'userData' => (string) (70 + $idx * 10 + $i)]
                    ])
                ]);
            }
        }

        $response = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential', $this->survey), [
                'method' => 'anova',
                'scope' => 'cross_survey',
                'dep' => 'score1',
                'target_surveys' => [
                    ['survey_id' => $targetSurvey1->id, 'dep' => 'dept_score_1'],
                    ['survey_id' => $targetSurvey2->id, 'dep' => 'dept_score_2'],
                ]
            ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('fValue', $data);
        $this->assertArrayHasKey('groupStats', $data);
        // Base survey + 2 target surveys = 3 comparison groups
        $this->assertCount(3, $data['groupStats']);
    }

    /** @test */
    public function test_toggle_inferential_analysis_report_inclusion()
    {
        $saved = \App\Models\SurveyInferentialAnalysis::create([
            'survey_id' => $this->survey->id,
            'user_id' => $this->user->id,
            'method' => 'regression',
            'title' => 'Test Analysis Inclusion',
            'variables' => 'Dep: score2, Grp: score1',
            'is_included_in_report' => false,
            'payload' => ['data' => []]
        ]);

        $this->assertFalse($saved->is_included_in_report);

        // Toggle ON
        $response = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential.toggle-report', [$this->survey, $saved->id]));

        $response->assertOk();
        $this->assertTrue($response->json('is_included_in_report'));
        $this->assertTrue($saved->fresh()->is_included_in_report);

        // Toggle OFF
        $response2 = $this->actingAs($this->user)
            ->postJson(route('surveys.reports.inferential.toggle-report', [$this->survey, $saved->id]));

        $response2->assertOk();
        $this->assertFalse($response2->json('is_included_in_report'));
        $this->assertFalse($saved->fresh()->is_included_in_report);
    }
}


