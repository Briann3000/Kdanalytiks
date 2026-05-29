<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyInviteCampaign;
use App\Models\SurveyInviteRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SurveyInviteCampaignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed default subscription tiers if middleware requires them
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

    public function test_creator_can_view_campaigns_index_and_create_form()
    {
        $user = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now()
        ]);
        $survey = Survey::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user);

        $response = $this->get(route('surveys.campaigns.index', $survey));
        $response->assertOk();
        $response->assertViewIs('surveys.campaigns.index');

        $response = $this->get(route('surveys.campaigns.create', $survey));
        $response->assertOk();
        $response->assertViewIs('surveys.campaigns.create');
    }

    public function test_cannot_view_campaigns_of_other_user_survey()
    {
        $owner = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now()
        ]);
        $otherUser = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now()
        ]);
        $survey = Survey::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($otherUser);

        $this->get(route('surveys.campaigns.index', $survey))->assertStatus(403);
    }

    public function test_create_campaign_with_manual_emails()
    {
        Queue::fake();

        $user = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now()
        ]);
        $survey = Survey::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user);

        $response = $this->post(route('surveys.campaigns.store', $survey), [
            'name' => 'Test Campaign 1',
            'emails' => "test1@example.com\ntest2@example.com; test3@example.com, test1@example.com",
            'auto_reminders' => '1',
            'reminder_interval_days' => '3',
        ]);

        $response->assertRedirect(route('surveys.campaigns.index', $survey));

        $this->assertDatabaseHas('survey_invite_campaigns', [
            'survey_id' => $survey->id,
            'name' => 'Test Campaign 1',
            'status' => 'sending',
            'auto_reminders' => true,
            'reminder_interval_days' => 3,
            'total_recipients' => 3,
        ]);

        $this->assertDatabaseHas('survey_invite_recipients', ['email' => 'test1@example.com']);
        $this->assertDatabaseHas('survey_invite_recipients', ['email' => 'test2@example.com']);
        $this->assertDatabaseHas('survey_invite_recipients', ['email' => 'test3@example.com']);

        Queue::assertPushed(\App\Jobs\SendInviteCampaignJob::class);
    }

    public function test_create_campaign_with_csv_upload()
    {
        Queue::fake();

        $user = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now()
        ]);
        $survey = Survey::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user);

        $csvContent = "name,email\nJohn Doe,john@example.com\nJane Smith,jane@example.com";
        $csvFile = UploadedFile::fake()->createWithContent('recipients.csv', $csvContent);

        $response = $this->post(route('surveys.campaigns.store', $survey), [
            'name' => 'CSV Campaign',
            'csv_file' => $csvFile,
        ]);

        $response->assertRedirect(route('surveys.campaigns.index', $survey));

        $this->assertDatabaseHas('survey_invite_campaigns', [
            'survey_id' => $survey->id,
            'name' => 'CSV Campaign',
            'status' => 'sending',
            'total_recipients' => 2,
        ]);

        $this->assertDatabaseHas('survey_invite_recipients', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);
        $this->assertDatabaseHas('survey_invite_recipients', [
            'email' => 'jane@example.com',
            'name' => 'Jane Smith',
        ]);

        Queue::assertPushed(\App\Jobs\SendInviteCampaignJob::class);
    }

    public function test_scheduled_campaign_does_not_send_immediately()
    {
        Queue::fake();

        $user = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now()
        ]);
        $survey = Survey::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user);

        $response = $this->post(route('surveys.campaigns.store', $survey), [
            'name' => 'Scheduled Campaign',
            'emails' => 'test@example.com',
            'scheduled_at' => now()->addHours(2)->toDateTimeString(),
        ]);

        $response->assertRedirect(route('surveys.campaigns.index', $survey));

        $this->assertDatabaseHas('survey_invite_campaigns', [
            'name' => 'Scheduled Campaign',
            'status' => 'scheduled',
        ]);

        Queue::assertNotPushed(\App\Jobs\SendInviteCampaignJob::class);
    }

    public function test_invite_token_allows_viewing_private_survey()
    {
        $user = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now()
        ]);
        // Set type to invitation (non-public)
        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'type' => \App\Enums\SurveyType::Invitation,
            'public_access' => 'none',
            'status' => \App\Enums\SurveyStatus::Active,
        ]);

        $campaign = SurveyInviteCampaign::create([
            'survey_id' => $survey->id,
            'name' => 'Campaign',
            'status' => 'sending',
            'total_recipients' => 1,
            'created_by' => $user->id,
        ]);

        $recipient = SurveyInviteRecipient::create([
            'campaign_id' => $campaign->id,
            'email' => 'invited@example.com',
            'token' => 'testtoken123',
            'status' => 'sent',
        ]);

        // Non-invited user guest should get 403
        $this->get(route('surveys.show', $survey))->assertStatus(403);

        // Accessing with correct invite token should render successfully
        $this->get(route('surveys.show', [$survey, 'invite_token' => 'testtoken123']))
            ->assertOk()
            ->assertViewIs('surveys.show_public');
    }

    public function test_submitting_with_invite_token_tracks_response()
    {
        $user = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now()
        ]);
        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'type' => \App\Enums\SurveyType::Invitation,
            'public_access' => 'none',
            'status' => \App\Enums\SurveyStatus::Active,
            'json_schema' => json_encode([['name' => 'q1', 'type' => 'text']]),
        ]);

        $campaign = SurveyInviteCampaign::create([
            'survey_id' => $survey->id,
            'name' => 'Campaign',
            'status' => 'sending',
            'total_recipients' => 1,
            'created_by' => $user->id,
        ]);

        $recipient = SurveyInviteRecipient::create([
            'campaign_id' => $campaign->id,
            'email' => 'invited@example.com',
            'token' => 'testtoken123',
            'status' => 'sent',
        ]);

        $this->actingAs($user);

        // Submit the survey with the token
        $response = $this->post(route('surveys.submit', [$survey, 'invite_token' => 'testtoken123']), [
            'terms_and_conditions' => '1',
            'is_json_submission' => '1',
            'json_data' => json_encode([['name' => 'q1', 'value' => 'Hello Answer']]),
        ]);

        $response->assertOk();

        // Verify recipient status is updated to responded
        $recipient->refresh();
        $this->assertEquals('responded', $recipient->status);
        $this->assertNotNull($recipient->responded_at);

        // Verify campaign totals updated
        $campaign->refresh();
        $this->assertEquals(1, $campaign->total_responded);
    }

    public function test_cancel_campaign()
    {
        $user = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now()
        ]);
        $survey = Survey::factory()->create(['created_by' => $user->id]);

        $campaign = SurveyInviteCampaign::create([
            'survey_id' => $survey->id,
            'name' => 'Scheduled Campaign',
            'status' => 'scheduled',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('surveys.campaigns.cancel', [$survey, $campaign]));
        $response->assertRedirect();

        $campaign->refresh();
        $this->assertEquals('cancelled', $campaign->status);
    }

    public function test_manual_reminders_dispatch_reminder_jobs()
    {
        Queue::fake();

        $user = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now()
        ]);
        $survey = Survey::factory()->create(['created_by' => $user->id]);

        $campaign = SurveyInviteCampaign::create([
            'survey_id' => $survey->id,
            'name' => 'Campaign',
            'status' => 'completed',
            'created_by' => $user->id,
        ]);

        $recipient1 = SurveyInviteRecipient::create([
            'campaign_id' => $campaign->id,
            'email' => 'remind@example.com',
            'token' => 'tok1',
            'status' => 'sent',
        ]);

        $recipient2 = SurveyInviteRecipient::create([
            'campaign_id' => $campaign->id,
            'email' => 'no-remind@example.com',
            'token' => 'tok2',
            'status' => 'responded',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('surveys.campaigns.remind', [$survey, $campaign]));
        $response->assertRedirect();

        Queue::assertPushed(\App\Jobs\SendInviteReminderJob::class);
    }

    public function test_scheduled_campaign_process_artisan_command()
    {
        Mail::fake();

        $user = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now()
        ]);
        $survey = Survey::factory()->create(['created_by' => $user->id]);

        $campaign = SurveyInviteCampaign::create([
            'survey_id' => $survey->id,
            'name' => 'Scheduled Campaign',
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinute(),
            'created_by' => $user->id,
        ]);

        $recipient = SurveyInviteRecipient::create([
            'campaign_id' => $campaign->id,
            'email' => 'test@example.com',
            'token' => 'tok123',
            'status' => 'pending',
        ]);

        $this->artisan('campaigns:process')
            ->expectsOutput("Dispatching campaign: {$campaign->name} (ID: {$campaign->id})")
            ->assertExitCode(0);

        $recipient->refresh();
        $this->assertEquals('sent', $recipient->status);

        $campaign->refresh();
        $this->assertEquals('completed', $campaign->status);

        Mail::assertSent(\App\Mail\SurveyInvitation::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    }

    public function test_auto_reminders_artisan_command()
    {
        Mail::fake();

        $user = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now()
        ]);
        $survey = Survey::factory()->create(['created_by' => $user->id]);

        $campaign = SurveyInviteCampaign::create([
            'survey_id' => $survey->id,
            'name' => 'Campaign with Reminders',
            'status' => 'completed',
            'auto_reminders' => true,
            'reminder_interval_days' => 3,
            'created_by' => $user->id,
        ]);

        // Needs reminder (sent 4 days ago, never reminded)
        $recipient1 = SurveyInviteRecipient::create([
            'campaign_id' => $campaign->id,
            'email' => 'remindme@example.com',
            'token' => 'tok1',
            'status' => 'sent',
            'sent_at' => now()->subDays(4),
            'last_reminder_at' => null,
        ]);

        // Doesn't need reminder (sent 1 day ago)
        $recipient2 = SurveyInviteRecipient::create([
            'campaign_id' => $campaign->id,
            'email' => 'dontremind@example.com',
            'token' => 'tok2',
            'status' => 'sent',
            'sent_at' => now()->subDays(1),
            'last_reminder_at' => null,
        ]);

        // Doesn't need reminder (already responded)
        $recipient3 = SurveyInviteRecipient::create([
            'campaign_id' => $campaign->id,
            'email' => 'responded@example.com',
            'token' => 'tok3',
            'status' => 'responded',
            'sent_at' => now()->subDays(4),
        ]);

        $this->artisan('campaigns:send-reminders')
            ->expectsOutput("Dispatched 1 reminder jobs.")
            ->assertExitCode(0);

        $recipient1->refresh();
        $this->assertEquals(1, $recipient1->reminder_count);
        $this->assertNotNull($recipient1->last_reminder_at);

        Mail::assertSent(\App\Mail\SurveyInviteReminder::class, function ($mail) {
            return $mail->hasTo('remindme@example.com');
        });
    }

    public function test_invite_token_transitions_status_to_opened_on_view()
    {
        $user = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now()
        ]);
        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'type' => \App\Enums\SurveyType::Invitation,
            'public_access' => 'none',
            'status' => \App\Enums\SurveyStatus::Active,
        ]);

        $campaign = SurveyInviteCampaign::create([
            'survey_id' => $survey->id,
            'name' => 'Campaign',
            'status' => 'sending',
            'total_recipients' => 1,
            'created_by' => $user->id,
        ]);

        $recipient = SurveyInviteRecipient::create([
            'campaign_id' => $campaign->id,
            'email' => 'invited@example.com',
            'token' => 'open-token-123',
            'status' => 'sent',
        ]);

        // Accessing the survey page with the token
        $response = $this->get(route('surveys.show', [$survey, 'invite_token' => 'open-token-123']));
        $response->assertOk();

        // Verify recipient status transitions to opened
        $recipient->refresh();
        $this->assertEquals('opened', $recipient->status);
        $this->assertNotNull($recipient->opened_at);

        // Verify campaign total_opened is incremented
        $campaign->refresh();
        $this->assertEquals(1, $campaign->total_opened);
    }
}
