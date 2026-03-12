<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Organization;
use App\Models\Independent;
use App\Models\Survey;
use App\Models\Question;
use App\Models\Response;
use App\Models\Answer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_has_required_columns(): void
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->assertNotNull($user->id);
        $this->assertEquals('Test', $user->name);
        $this->assertEquals('test@test.com', $user->email);
        $this->assertEquals('admin', $user->role);
        $this->assertEquals('active', $user->status);
    }

    public function test_organization_belongs_to_user(): void
    {
        $user = User::create([
            'name' => 'Org User',
            'email' => 'org@test.com',
            'password' => bcrypt('password123'),
            'role' => 'organization',
            'status' => 'active',
        ]);

        $org = Organization::create([
            'user_id' => $user->id,
            'name' => 'Test Org',
        ]);

        $this->assertEquals($user->id, $org->user->id);
        $this->assertEquals($org->id, $user->organization->id);
    }

    public function test_independent_belongs_to_user(): void
    {
        $user = User::create([
            'name' => 'Researcher',
            'email' => 'researcher@test.com',
            'password' => bcrypt('password123'),
            'role' => 'independent',
            'status' => 'active',
        ]);

        $ind = Independent::create([
            'user_id' => $user->id,
            'name' => 'Researcher',
            'institution' => 'MIT',
            'research_area' => 'AI',
        ]);

        $this->assertEquals($user->id, $ind->user->id);
        $this->assertEquals($ind->id, $user->independent->id);
    }

    public function test_survey_has_questions(): void
    {
        $user = User::create([
            'name' => 'Org', 'email' => 'o@t.com',
            'password' => bcrypt('p'), 'role' => 'organization', 'status' => 'active',
        ]);
        $org = Organization::create(['user_id' => $user->id, 'name' => 'O']);
        $survey = Survey::create([
            'organization_id' => $org->id,
            'title' => 'Test Survey',
            'category' => 'Academic',
            'type' => 'public',
            'status' => 'draft',
        ]);

        $q1 = Question::create([
            'survey_id' => $survey->id,
            'text' => 'Question 1?',
            'type' => 'text',
            'required' => true,
            'position' => 1,
        ]);

        $q2 = Question::create([
            'survey_id' => $survey->id,
            'text' => 'Question 2?',
            'type' => 'multiple_choice',
            'options' => json_encode(['Yes', 'No']),
            'required' => false,
            'position' => 2,
        ]);

        $this->assertEquals(2, $survey->questions()->count());
        $this->assertEquals('Question 1?', $survey->questions->first()->text);
    }

    public function test_response_and_answers_relationship(): void
    {
        $orgUser = User::create([
            'name' => 'Org', 'email' => 'o@t.com',
            'password' => bcrypt('p'), 'role' => 'organization', 'status' => 'active',
        ]);
        $org = Organization::create(['user_id' => $orgUser->id, 'name' => 'O']);
        $survey = Survey::create([
            'organization_id' => $org->id, 'title' => 'S',
            'category' => 'Academic', 'type' => 'public', 'status' => 'active',
        ]);
        $question = Question::create([
            'survey_id' => $survey->id, 'text' => 'Q?',
            'type' => 'text', 'required' => true, 'position' => 1,
        ]);

        $respondent = User::create([
            'name' => 'Resp', 'email' => 'r@t.com',
            'password' => bcrypt('p'), 'role' => 'respondent', 'status' => 'active',
        ]);

        $response = Response::create([
            'survey_id' => $survey->id,
            'respondent_id' => $respondent->id,
            'submitted_at' => now(),
        ]);

        $answer = Answer::create([
            'response_id' => $response->id,
            'question_id' => $question->id,
            'value' => 'Test answer',
        ]);

        $this->assertEquals(1, $survey->responses->count());
        $this->assertEquals($respondent->id, $response->respondent->id);
        $this->assertEquals(1, $response->answers->count());
        $this->assertEquals('Test answer', $response->answers->first()->value);
    }

    public function test_seeder_creates_admin_user(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', [
            'email' => 'admin@kmsurvey.com',
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function test_user_email_must_be_unique(): void
    {
        User::create([
            'name' => 'First', 'email' => 'dup@t.com',
            'password' => bcrypt('p'), 'role' => 'admin', 'status' => 'active',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::create([
            'name' => 'Second', 'email' => 'dup@t.com',
            'password' => bcrypt('p'), 'role' => 'respondent', 'status' => 'active',
        ]);
    }
}
