<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Survey;
use App\Models\Question;
use App\Models\Response;
use App\Services\Import\SurveyImportBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected SurveyImportBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->builder = new SurveyImportBuilder();
    }

    /** @test */
    public function it_can_infer_question_types_correctly()
    {
        // 3-7 numeric value labels -> rating (Likert scale)
        $var1 = [
            'value_labels' => [
                '1' => 'Strongly Disagree',
                '2' => 'Disagree',
                '3' => 'Neutral',
                '4' => 'Agree',
                '5' => 'Strongly Agree',
            ]
        ];
        $this->assertEquals('rating', $this->builder->inferType($var1));

        // Non-numeric or >7 value labels -> select (dropdown)
        $var2 = [
            'value_labels' => array_combine(range(1, 12), array_map(fn($n) => "Option $n", range(1, 12)))
        ];
        $this->assertEquals('select', $this->builder->inferType($var2));

        // No value labels -> text
        $var3 = [
            'value_labels' => []
        ];
        $this->assertEquals('text', $this->builder->inferType($var3));

        // Coordinates -> decimal
        $varLat = ['name' => 'latitude', 'label' => 'Latitude'];
        $this->assertEquals('decimal', $this->builder->inferType($varLat, ['0.0712', '0.0715']));

        // Wards with 3 choices -> radio
        $varWard = ['name' => 'ward', 'label' => 'Ward'];
        $this->assertEquals('radio', $this->builder->inferType($varWard, ['Igoji East', 'Koji East', 'Mwimbi', 'Igoji East']));
    }

    /** @test */
    public function it_imports_survey_structure_and_responses_correctly()
    {
        $this->actingAs($this->user);

        $mapping = [
            [
                'var_index' => 0,
                'name' => 'VAR00001',
                'label' => 'What is your gender?',
                'type' => 'radio', // converts to select_one
                'options' => [
                    ['label' => 'Male', 'value' => '1'],
                    ['label' => 'Female', 'value' => '2'],
                ],
                'value_labels' => [
                    '1' => 'Male',
                    '2' => 'Female',
                ],
                'include' => true,
            ],
            [
                'var_index' => 1,
                'name' => 'VAR00002',
                'label' => 'Comments',
                'type' => 'text',
                'options' => [],
                'value_labels' => [],
                'include' => true,
            ],
            [
                'var_index' => 2,
                'name' => 'VAR00003',
                'label' => 'Excluded Column',
                'type' => 'text',
                'options' => [],
                'value_labels' => [],
                'include' => false,
            ]
        ];

        $rows = [
            ['1', 'Very nice survey.', 'some secret value'],
            ['2', 'Needs improvement.', 'other secret value'],
        ];

        $survey = $this->builder->build(
            title: 'Test Import Survey',
            importSource: 'spss',
            mapping: $mapping,
            rows: $rows
        );

        // Assert Survey Created
        $this->assertDatabaseHas('surveys', [
            'id' => $survey->id,
            'title' => 'Test Import Survey',
            'import_source' => 'spss',
        ]);

        // Assert Questions Created (Only 2 included)
        $this->assertEquals(2, $survey->questions()->count());
        $this->assertDatabaseHas('questions', [
            'survey_id' => $survey->id,
            'text' => 'What is your gender?',
            'type' => 'select_one',
        ]);
        $this->assertDatabaseHas('questions', [
            'survey_id' => $survey->id,
            'text' => 'Comments',
            'type' => 'text',
        ]);
        $this->assertDatabaseMissing('questions', [
            'survey_id' => $survey->id,
            'text' => 'Excluded Column',
        ]);

        // Assert Responses Created
        $this->assertEquals(2, $survey->responses()->count());

        $firstResponse = $survey->responses()->first();
        $this->assertEquals(1, $firstResponse->answers()->count());

        $jsonAnswer = $firstResponse->answers()->first();
        $this->assertNull($jsonAnswer->question_id);

        $decoded = json_decode($jsonAnswer->value, true);
        $this->assertIsArray($decoded);
        $this->assertEquals('Male', $decoded[0]['userData']);
        $this->assertEquals('Very nice survey.', $decoded[1]['userData']);
    }

    /** @test */
    public function it_can_preview_uploaded_csv_file()
    {
        $this->actingAs($this->user);

        $csvContent = "Gender,Age Group,Satisfaction\nMale,18-24,5\nFemale,25-34,4\n";
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('survey_data.csv', $csvContent);

        $response = $this->postJson(route('surveys.import.preview'), [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'variables',
            'row_count',
            'source',
            'preview_rows',
        ]);
        $response->assertJson([
            'row_count' => 2,
            'source' => 'csv',
        ]);
    }

    /** @test */
    public function it_converts_excel_serial_dates_properly()
    {
        $this->actingAs($this->user);

        // 46296 is 2026-10-01 in Excel
        $csvContent = "Plot 1 season,Ward,Income\n46296,Igoji East,50000\n46054,Koji East,45000\n";
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('survey_dates.csv', $csvContent);

        $response = $this->postJson(route('surveys.import.preview'), [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $variables = $response->json('variables');

        $seasonVar = collect($variables)->firstWhere('name', 'plot_1_season');
        $this->assertNotNull($seasonVar);
        $this->assertEquals('date', $seasonVar['inferred_type']);

        // Check preview row dates are converted to YYYY-MM-DD
        $previewRows = $response->json('preview_rows');
        $this->assertEquals('2026-10-01', $previewRows[0][0]);
    }
}
