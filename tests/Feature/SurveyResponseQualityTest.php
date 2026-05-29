<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Response;
use App\Models\Survey;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyResponseQualityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed default subscription tiers
        \App\Models\SubscriptionTier::create([
            'name' => 'Free',
            'slug' => 'free',
            'max_surveys' => 5,
            'ai_limit_per_month' => 10,
        ]);
        \App\Models\SubscriptionTier::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'max_surveys' => -1,
            'ai_limit_per_month' => -1,
        ]);
    }

    public function test_response_without_fraud_is_scored_clean_and_gets_reward()
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $respondent = User::factory()->create(['email_verified_at' => now()]);

        $survey = Survey::factory()->create([
            'created_by' => $owner->id,
            'is_paid' => true,
            'reward_per_response' => 10.00,
            'reward_budget' => 100.00,
            'json_schema' => json_encode([
                ['name' => 'q1', 'type' => 'text', 'label' => 'Elaborate feedback'],
                ['name' => 'q2', 'type' => 'select_one', 'label' => 'Rating', 'values' => ['1', '2', '3']],
                ['name' => 'q3', 'type' => 'select_one', 'label' => 'Rating 2', 'values' => ['1', '2', '3']],
            ]),
        ]);

        $wallet = Wallet::create(['user_id' => $respondent->id, 'balance' => 5.00]);

        $this->actingAs($respondent);

        $response = $this->post(route('surveys.submit', $survey), [
            'terms_and_conditions' => '1',
            'is_json_submission' => '1',
            'completion_time_seconds' => 120, // Clean: long time
            'json_data' => json_encode([
                ['name' => 'q1', 'userData' => 'This is very detailed feedback on the survey.'], // Clean: detailed text
                ['name' => 'q2', 'userData' => '1'],
                ['name' => 'q3', 'userData' => '2'], // Clean: not straight-lining (1 and 2 are different)
            ]),
        ]);

        $response->assertJson(['success' => true]);

        // Retrieve response
        $dbResponse = Response::latest()->first();
        $this->assertNotNull($dbResponse);
        $this->assertFalse($dbResponse->is_flagged);
        $this->assertGreaterThanOrEqual(70, $dbResponse->quality_score);

        // Wallet should be credited
        $wallet->refresh();
        $this->assertEquals(15.00, $wallet->balance);

        // Transaction should be completed
        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $wallet->id,
            'amount' => 10.00,
            'status' => 'completed',
        ]);
    }

    public function test_speedy_response_is_flagged_and_withholds_reward()
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $respondent = User::factory()->create(['email_verified_at' => now()]);

        $survey = Survey::factory()->create([
            'created_by' => $owner->id,
            'is_paid' => true,
            'reward_per_response' => 10.00,
            'reward_budget' => 100.00,
            'json_schema' => json_encode([
                ['name' => 'q1', 'type' => 'text', 'label' => 'Feedback'],
            ]),
        ]);

        $wallet = Wallet::create(['user_id' => $respondent->id, 'balance' => 5.00]);

        $this->actingAs($respondent);

        $response = $this->post(route('surveys.submit', $survey), [
            'terms_and_conditions' => '1',
            'is_json_submission' => '1',
            'completion_time_seconds' => 1, // Flagged: completed in 1 second!
            'json_data' => json_encode([
                ['name' => 'q1', 'userData' => 'Quick text'],
            ]),
        ]);

        $response->assertJson(['success' => true]);

        $dbResponse = Response::latest()->first();
        $this->assertNotNull($dbResponse);
        $this->assertTrue($dbResponse->is_flagged);

        // Wallet balance remains unchanged
        $wallet->refresh();
        $this->assertEquals(5.00, $wallet->balance);

        // Transaction should be pending
        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $wallet->id,
            'amount' => 10.00,
            'status' => 'pending',
        ]);
    }

    public function test_gibberish_response_is_flagged_and_withholds_reward()
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $respondent = User::factory()->create(['email_verified_at' => now()]);

        $survey = Survey::factory()->create([
            'created_by' => $owner->id,
            'is_paid' => true,
            'reward_per_response' => 10.00,
            'reward_budget' => 100.00,
            'json_schema' => json_encode([
                ['name' => 'q1', 'type' => 'text', 'label' => 'Elaborate feedback'],
            ]),
        ]);

        $wallet = Wallet::create(['user_id' => $respondent->id, 'balance' => 5.00]);

        $this->actingAs($respondent);

        $response = $this->post(route('surveys.submit', $survey), [
            'terms_and_conditions' => '1',
            'is_json_submission' => '1',
            'completion_time_seconds' => 60,
            'json_data' => json_encode([
                ['name' => 'q1', 'userData' => 'aaaaaaa'], // Flagged: repeated characters gibberish
            ]),
        ]);

        $response->assertJson(['success' => true]);

        $dbResponse = Response::latest()->first();
        $this->assertNotNull($dbResponse);
        $this->assertTrue($dbResponse->is_flagged);

        // Transaction pending
        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $wallet->id,
            'status' => 'pending',
        ]);
    }

    public function test_straight_lining_response_is_flagged()
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $respondent = User::factory()->create(['email_verified_at' => now()]);

        $survey = Survey::factory()->create([
            'created_by' => $owner->id,
            'json_schema' => json_encode([
                ['name' => 'q1', 'type' => 'select_one', 'label' => 'Q1'],
                ['name' => 'q2', 'type' => 'select_one', 'label' => 'Q2'],
                ['name' => 'q3', 'type' => 'select_one', 'label' => 'Q3'],
            ]),
        ]);

        $this->actingAs($respondent);

        $response = $this->post(route('surveys.submit', $survey), [
            'terms_and_conditions' => '1',
            'is_json_submission' => '1',
            'completion_time_seconds' => 60,
            'json_data' => json_encode([
                ['name' => 'q1', 'userData' => 'option-1'],
                ['name' => 'q2', 'userData' => 'option-1'],
                ['name' => 'q3', 'userData' => 'option-1'], // Flagged: straight-lining same option-1
            ]),
        ]);

        $response->assertJson(['success' => true]);

        $dbResponse = Response::latest()->first();
        $this->assertTrue($dbResponse->is_flagged);
    }

    public function test_admin_approves_flagged_response_releases_reward()
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $respondent = User::factory()->create(['email_verified_at' => now()]);

        $survey = Survey::factory()->create([
            'created_by' => $owner->id,
            'is_paid' => true,
        ]);

        $wallet = Wallet::create(['user_id' => $respondent->id, 'balance' => 0.00]);

        $response = Response::create([
            'survey_id' => $survey->id,
            'respondent_id' => $respondent->id,
            'is_flagged' => true,
            'quality_score' => 20,
        ]);

        $transaction = Transaction::create([
            'wallet_id' => $wallet->id,
            'amount' => 10.00,
            'type' => 'credit',
            'status' => 'pending',
            'reference' => 'TEST-REF',
            'description' => "Reward pending quality review for Survey ID: {$survey->id}"
        ]);

        $this->actingAs($owner);

        // Approve response quality override
        $webResponse = $this->post(route('surveys.responses.quality-override', [$survey, $response]), [
            'action' => 'approve'
        ]);

        $webResponse->assertRedirect();

        $response->refresh();
        $this->assertFalse($response->is_flagged);

        // Wallet should be credited
        $wallet->refresh();
        $this->assertEquals(10.00, $wallet->balance);

        // Transaction should be completed
        $transaction->refresh();
        $this->assertEquals('completed', $transaction->status);
    }

    public function test_admin_rejects_flagged_response_deletes_record()
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $respondent = User::factory()->create(['email_verified_at' => now()]);

        $survey = Survey::factory()->create([
            'created_by' => $owner->id,
            'is_paid' => true,
        ]);

        $wallet = Wallet::create(['user_id' => $respondent->id, 'balance' => 0.00]);

        $response = Response::create([
            'survey_id' => $survey->id,
            'respondent_id' => $respondent->id,
            'is_flagged' => true,
            'quality_score' => 20,
        ]);

        $answer = Answer::create([
            'response_id' => $response->id,
            'value' => '[]',
        ]);

        $transaction = Transaction::create([
            'wallet_id' => $wallet->id,
            'amount' => 10.00,
            'type' => 'credit',
            'status' => 'pending',
            'reference' => 'TEST-REF-2',
            'description' => "Reward pending quality review for Survey ID: {$survey->id}"
        ]);

        $this->actingAs($owner);

        $webResponse = $this->post(route('surveys.responses.quality-override', [$survey, $response]), [
            'action' => 'reject'
        ]);

        $webResponse->assertRedirect();

        // Assert response and answers deleted
        $this->assertDatabaseMissing('responses', ['id' => $response->id]);
        $this->assertDatabaseMissing('answers', ['id' => $answer->id]);

        // Transaction marked failed
        $transaction->refresh();
        $this->assertEquals('failed', $transaction->status);
    }

    public function test_response_with_multiselect_answers_does_not_fail()
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $respondent = User::factory()->create(['email_verified_at' => now()]);

        $survey = Survey::factory()->create([
            'created_by' => $owner->id,
            'json_schema' => json_encode([
                ['name' => 'q1', 'type' => 'checkbox', 'label' => 'Choose multiple', 'values' => ['A', 'B', 'C']],
            ]),
        ]);

        $this->actingAs($respondent);

        $response = $this->post(route('surveys.submit', $survey), [
            'terms_and_conditions' => '1',
            'is_json_submission' => '1',
            'completion_time_seconds' => 60,
            'json_data' => json_encode([
                ['name' => 'q1', 'userData' => ['A', 'B']], // Multi-select array input
            ]),
        ]);

        $response->assertJson(['success' => true]);

        $dbResponse = Response::latest()->first();
        $this->assertNotNull($dbResponse);
        $this->assertFalse($dbResponse->is_flagged);
    }
}
