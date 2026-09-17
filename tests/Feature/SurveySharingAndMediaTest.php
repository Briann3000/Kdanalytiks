<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Response as SurveyResponse;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SurveySharingAndMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_renders_without_error(): void
    {
        $user = User::factory()->create();
        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'title' => 'Sample Test Survey',
        ]);

        $res = $this->actingAs($user)->get(route('surveys.settings', $survey));
        $res->assertStatus(200);
        $res->assertSee('Sharing & Collaborators');
        $res->assertSee('Read-Only Data & Charts');
        $res->assertSee('For Respondents');
    }

    public function test_collaborator_can_be_added_with_granular_permissions(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $owner = User::factory()->create();
        $collaboratorUser = User::factory()->create(['email' => 'collab@example.com']);
        $survey = Survey::factory()->create(['created_by' => $owner->id]);

        $res = $this->actingAs($owner)->post(route('surveys.collaborators.add', $survey), [
            'email' => 'collab@example.com',
            'view_form' => 1,
            'view_submissions' => 1,
            'edit_submissions' => 1,
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('success');

        $this->assertDatabaseHas('survey_permissions', [
            'survey_id' => $survey->id,
            'user_id' => $collaboratorUser->id,
            'status' => 'accepted',
        ]);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\CollaboratorInvitationMail::class);
    }

    public function test_unregistered_user_receives_invitation_email_and_links_on_registration(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $owner = User::factory()->create();
        $survey = Survey::factory()->create(['created_by' => $owner->id]);

        $inviteEmail = 'newcollaborator@example.com';

        // Owner invites non-registered email
        $res = $this->actingAs($owner)->post(route('surveys.collaborators.add', $survey), [
            'email' => $inviteEmail,
            'view_form' => 1,
            'view_submissions' => 1,
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('success');

        $this->assertDatabaseHas('survey_permissions', [
            'survey_id' => $survey->id,
            'invite_email' => $inviteEmail,
            'status' => 'pending',
            'user_id' => null,
        ]);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\CollaboratorInvitationMail::class);

        // Log out owner before registering as new guest user
        auth()->logout();

        // Now new user registers with that email
        $regRes = $this->post(route('register', ['role' => 'independent']), [
            'name' => 'New Collaborator',
            'email' => $inviteEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $regRes->assertRedirect(route('verification.notice'));

        // Permission should now be accepted and linked to user_id
        $newUser = User::where('email', $inviteEmail)->first();
        $this->assertNotNull($newUser);

        $this->assertDatabaseHas('survey_permissions', [
            'survey_id' => $survey->id,
            'invite_email' => $inviteEmail,
            'status' => 'accepted',
            'user_id' => $newUser->id,
        ]);
    }

    public function test_owner_can_transfer_survey_ownership(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $owner = User::factory()->create();
        $newOwner = User::factory()->create(['email' => 'futureowner@example.com']);
        $survey = Survey::factory()->create(['created_by' => $owner->id]);

        $res = $this->actingAs($owner)->post(route('surveys.transfer_ownership', $survey), [
            'new_owner_email' => 'futureowner@example.com',
        ]);

        $res->assertRedirect(route('surveys.summary', $survey));
        $survey->refresh();

        $this->assertEquals($newOwner->id, $survey->created_by);
        $this->assertNull($survey->pending_owner_email);

        // Old owner should retain full collaborator permissions
        $this->assertDatabaseHas('survey_permissions', [
            'survey_id' => $survey->id,
            'user_id' => $owner->id,
            'status' => 'accepted',
        ]);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SurveyOwnershipTransferredMail::class);
    }

    public function test_owner_can_invite_unregistered_user_to_transfer_ownership_and_claims_on_registration(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $owner = User::factory()->create();
        $survey = Survey::factory()->create(['created_by' => $owner->id]);

        $pendingEmail = 'unregisteredowner@example.com';

        $res = $this->actingAs($owner)->post(route('surveys.transfer_ownership', $survey), [
            'new_owner_email' => $pendingEmail,
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('success');

        $survey->refresh();
        $this->assertEquals($owner->id, $survey->created_by);
        $this->assertEquals($pendingEmail, $survey->pending_owner_email);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SurveyOwnershipTransferredMail::class, function ($mail) use ($pendingEmail) {
            return $mail->isPending && $mail->inviteEmail === $pendingEmail;
        });

        // Settings page shows pending banner
        $settingsRes = $this->actingAs($owner)->get(route('surveys.settings', $survey));
        $settingsRes->assertStatus(200);
        $settingsRes->assertSee('Pending Ownership Transfer');
        $settingsRes->assertSee($pendingEmail);

        // Log out owner before registering as new user
        auth()->logout();

        // New user registers
        $regRes = $this->post(route('register', ['role' => 'independent']), [
            'name' => 'Brand New Owner',
            'email' => $pendingEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $regRes->assertRedirect(route('verification.notice'));

        $newRegisteredUser = User::where('email', $pendingEmail)->first();
        $this->assertNotNull($newRegisteredUser);

        $survey->refresh();
        $this->assertEquals($newRegisteredUser->id, $survey->created_by);
        $this->assertNull($survey->pending_owner_email);

        // Original owner receives full collaborator permissions
        $this->assertDatabaseHas('survey_permissions', [
            'survey_id' => $survey->id,
            'user_id' => $owner->id,
            'status' => 'accepted',
        ]);
    }

    public function test_owner_can_cancel_pending_ownership_transfer(): void
    {
        $owner = User::factory()->create();
        $survey = Survey::factory()->create([
            'created_by' => $owner->id,
            'pending_owner_email' => 'canceltarget@example.com',
        ]);

        $res = $this->actingAs($owner)->post(route('surveys.transfer_ownership.cancel', $survey));
        $res->assertRedirect();
        $res->assertSessionHas('success');

        $survey->refresh();
        $this->assertNull($survey->pending_owner_email);
    }

    public function test_owner_can_resend_pending_ownership_transfer_invite(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $owner = User::factory()->create();
        $survey = Survey::factory()->create([
            'created_by' => $owner->id,
            'pending_owner_email' => 'resendtarget@example.com',
        ]);

        $res = $this->actingAs($owner)->post(route('surveys.transfer_ownership.resend', $survey));
        $res->assertRedirect();
        $res->assertSessionHas('success');

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SurveyOwnershipTransferredMail::class, function ($mail) {
            return $mail->isPending && $mail->inviteEmail === 'resendtarget@example.com';
        });
    }

    public function test_cannot_transfer_survey_ownership_to_self(): void
    {
        $owner = User::factory()->create(['email' => 'selfowner@example.com']);
        $survey = Survey::factory()->create(['created_by' => $owner->id]);

        $res = $this->actingAs($owner)->post(route('surveys.transfer_ownership', $survey), [
            'new_owner_email' => 'selfowner@example.com',
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('error');

        $survey->refresh();
        $this->assertEquals($owner->id, $survey->created_by);
        $this->assertNull($survey->pending_owner_email);
    }

    public function test_toggle_shared_data_activates_and_deactivates(): void
    {
        $user = User::factory()->create();
        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'public_data_enabled' => false,
        ]);

        // Enable
        $res = $this->actingAs($user)->post(route('surveys.toggle-shared-data', $survey));
        $res->assertRedirect();
        $survey->refresh();
        $this->assertTrue($survey->public_data_enabled);
        $this->assertNotEmpty($survey->share_data_token);

        // Disable
        $res = $this->actingAs($user)->post(route('surveys.toggle-shared-data', $survey), ['disable' => 1]);
        $res->assertRedirect();
        $survey->refresh();
        $this->assertFalse($survey->public_data_enabled);
    }

    public function test_unauthenticated_stakeholder_can_view_shared_data_page(): void
    {
        $user = User::factory()->create();
        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'title' => 'Community Research Survey',
            'share_data_token' => 'sample_stakeholder_token_123',
            'public_data_enabled' => true,
            'json_schema' => json_encode([
                [
                    'name' => 'field_audio_1',
                    'label' => 'Interview Question 1',
                    'type' => 'audio',
                ],
                [
                    'name' => 'field_checkbox_1',
                    'label' => 'Multiple Selection',
                    'type' => 'checkbox-group',
                    'values' => [
                        ['label' => 'Option Alpha', 'value' => 'alpha'],
                        ['label' => 'Option Beta', 'value' => 'beta'],
                    ]
                ],
                [
                    'name' => 'field_raw_array',
                    'label' => 'Raw Array Field',
                    'type' => 'text',
                ],
            ]),
        ]);

        $responseRecord = SurveyResponse::factory()->create([
            'survey_id' => $survey->id,
            'respondent_id' => null,
            'guest_name' => 'Jane Doe',
            'ai_metadata' => [
                'transcriptions' => [
                    'uploads/res_test_audio.ogg' => 'This is a sample transcribed interview response.',
                ],
            ],
        ]);

        $answer = Answer::create([
            'response_id' => $responseRecord->id,
            'question_id' => null,
            'value' => json_encode([
                [
                    'name' => 'field_audio_1',
                    'userData' => 'uploads/res_test_audio.ogg',
                ],
                [
                    'name' => 'field_checkbox_1',
                    'userData' => ['alpha', 'beta'],
                ],
                [
                    'name' => 'field_raw_array',
                    'userData' => ['raw_val_1', 'raw_val_2'],
                ],
            ]),
        ]);

        // Guest access without login
        $res = $this->get(route('surveys.shared_data', 'sample_stakeholder_token_123'));
        $res->assertStatus(200);
        $res->assertSee('Community Research Survey');
        $res->assertSee('Public Sharing (Read-Only)');
        $res->assertSee('Jane Doe');
        $res->assertSee('This is a sample transcribed interview response.');
    }

    public function test_serve_media_handles_preview_and_download(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/res_audio_test.ogg', 'fake-audio-content');

        $user = User::factory()->create();
        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'share_data_token' => 'valid_token_abc',
            'public_data_enabled' => true,
        ]);

        $responseRecord = SurveyResponse::factory()->create([
            'survey_id' => $survey->id,
        ]);

        // Stream / inline view
        $res = $this->get(route('surveys.responses.media', [
            'survey' => $survey,
            'response' => $responseRecord,
            'path' => 'uploads/res_audio_test.ogg',
            'token' => 'valid_token_abc',
        ]));
        $res->assertStatus(200);

        // Download attachment
        $downloadRes = $this->get(route('surveys.responses.media', [
            'survey' => $survey,
            'response' => $responseRecord,
            'path' => 'uploads/res_audio_test.ogg',
            'download' => 1,
            'token' => 'valid_token_abc',
        ]));
        $downloadRes->assertStatus(200);
        $downloadRes->assertHeader('content-disposition');
    }

    public function test_survey_responses_export_maps_transcriptions(): void
    {
        $user = User::factory()->create();
        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'json_schema' => json_encode([
                [
                    'name' => 'field_audio_1',
                    'label' => 'Interview Question 1',
                    'type' => 'audio',
                ],
            ]),
        ]);

        $responseRecord = SurveyResponse::factory()->create([
            'survey_id' => $survey->id,
            'respondent_id' => null,
            'guest_name' => 'John Field Respondent',
            'ai_metadata' => [
                'transcriptions' => [
                    'uploads/res_interview_1.ogg' => 'The policy implementation improved our local operations significantly.',
                ],
            ],
        ]);

        Answer::create([
            'response_id' => $responseRecord->id,
            'question_id' => null,
            'value' => json_encode([
                [
                    'name' => 'field_audio_1',
                    'userData' => 'uploads/res_interview_1.ogg',
                ],
            ]),
        ]);

        $export = new \App\Exports\SurveyResponsesExport($survey, collect([$responseRecord]));
        $mapped = $export->map($responseRecord);

        // The mapped answer column should contain the transcribed dialogue
        $this->assertContains('The policy implementation improved our local operations significantly.', $mapped);
    }

    public function test_export_single_docx_runs_successfully(): void
    {
        $user = User::factory()->create();
        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'json_schema' => json_encode([
                [
                    'name' => 'field_audio_1',
                    'label' => 'Interview Question 1',
                    'type' => 'audio',
                ],
            ]),
        ]);

        $responseRecord = SurveyResponse::factory()->create([
            'survey_id' => $survey->id,
            'respondent_id' => null,
            'guest_name' => 'John Field Respondent',
            'ai_metadata' => [
                'transcriptions' => [
                    'uploads/res_interview_1.ogg' => 'Transcribed answer in Word docx.',
                ],
            ],
        ]);

        Answer::create([
            'response_id' => $responseRecord->id,
            'question_id' => null,
            'value' => json_encode([
                [
                    'name' => 'field_audio_1',
                    'userData' => 'uploads/res_interview_1.ogg',
                ],
            ]),
        ]);

        $res = $this->actingAs($user)->get(route('surveys.responses.export_docx', [$survey, $responseRecord]));
        $res->assertStatus(200);
        $res->assertHeader('content-disposition');
    }

    public function test_export_auto_transcribes_pending_media(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/untranscribed_audio.ogg', 'dummy-audio-bytes');

        $user = User::factory()->create();
        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'json_schema' => json_encode([
                [
                    'name' => 'field_audio_1',
                    'label' => 'Interview Question 1',
                    'type' => 'audio',
                ],
            ]),
        ]);

        $responseRecord = SurveyResponse::factory()->create([
            'survey_id' => $survey->id,
            'ai_metadata' => [],
        ]);

        Answer::create([
            'response_id' => $responseRecord->id,
            'question_id' => null,
            'value' => json_encode([
                [
                    'name' => 'field_audio_1',
                    'userData' => 'uploads/untranscribed_audio.ogg',
                ],
            ]),
        ]);

        // Mock AiService
        $this->mock(\App\Services\AiService::class, function ($mock) {
            $mock->shouldReceive('transcribeMedia')
                ->once()
                ->andReturn('Automatically transcribed text from export trigger.');
        });

        // Instantiate export which runs ensureTranscriptionsForResponses
        $export = new \App\Exports\SurveyResponsesExport($survey, collect([$responseRecord]));
        $responseRecord->refresh();

        $this->assertEquals(
            'Automatically transcribed text from export trigger.',
            $responseRecord->ai_metadata['transcriptions']['uploads/untranscribed_audio.ogg'] ?? null
        );

        $mapped = $export->map($responseRecord);
        $this->assertContains('Automatically transcribed text from export trigger.', $mapped);
    }

    public function test_owner_can_view_data_tab_with_complex_schema(): void
    {
        $user = User::factory()->create();
        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'title' => 'Data Tab Complexity Test',
            'json_schema' => json_encode([
                [
                    'name' => 'field_matrix_1',
                    'label' => 'Matrix Question',
                    'type' => 'likert_matrix_grid',
                    'rows' => [
                        ['label' => 'Row 1', 'value' => 'r1'],
                        ['label' => 'Row 2', 'value' => 'r2'],
                    ],
                    'columns' => [
                        ['label' => 'Col 1', 'value' => 'c1'],
                        ['label' => 'Col 2', 'value' => 'c2'],
                    ],
                ],
                [
                    'name' => 'field_checkbox_1',
                    'label' => 'Select Multiple',
                    'type' => 'checkbox-group',
                    'values' => [
                        ['label' => 'Option Alpha', 'value' => 'alpha'],
                        ['label' => 'Option Beta', 'value' => 'beta'],
                    ],
                ],
            ]),
        ]);

        $responseRecord = SurveyResponse::factory()->create([
            'survey_id' => $survey->id,
        ]);

        Answer::create([
            'response_id' => $responseRecord->id,
            'question_id' => null,
            'value' => json_encode([
                [
                    'name' => 'field_matrix_1',
                    'userData' => json_encode(['r1' => 'c1', 'r2' => 'c2']),
                ],
                [
                    'name' => 'field_checkbox_1',
                    'userData' => ['alpha', 'beta'],
                ],
            ]),
        ]);

        $res = $this->actingAs($user)->get(route('surveys.data', $survey));
        $res->assertStatus(200);
        $res->assertSee('Data Tab Complexity Test');
        $res->assertSee('Row 1: Col 1');
        $res->assertSee('Option Alpha, Option Beta');
    }

    public function test_export_package_generates_valid_kdsurvey_zip(): void
    {
        $user = User::factory()->create();
        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'title' => 'Export Package Survey',
            'json_schema' => json_encode([
                [
                    'name' => 'full_name',
                    'label' => 'Full Name',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'rating_score',
                    'label' => 'Satisfaction Score',
                    'type' => 'rating',
                    'values' => [
                        ['label' => 'Low', 'value' => '1'],
                        ['label' => 'High', 'value' => '5'],
                    ],
                ],
            ]),
        ]);

        $responseRecord = SurveyResponse::factory()->create([
            'survey_id' => $survey->id,
            'guest_name' => 'Respondent One',
        ]);

        Answer::create([
            'response_id' => $responseRecord->id,
            'value' => json_encode([
                ['name' => 'full_name', 'userData' => 'Alice Walker'],
                ['name' => 'rating_score', 'userData' => '5'],
            ]),
        ]);

        $res = $this->actingAs($user)->get(route('surveys.export_package', $survey));
        $res->assertStatus(200);
        $this->assertTrue(str_contains($res->headers->get('content-disposition'), 'export-package-survey_export.kdsurvey'));
    }

    public function test_export_spss_sav_generates_file_download(): void
    {
        $user = User::factory()->create();
        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'title' => 'SPSS Dataset Test',
            'json_schema' => json_encode([
                [
                    'name' => 'gender',
                    'label' => 'Gender',
                    'type' => 'radio-group',
                    'values' => [
                        ['label' => 'Female', 'value' => 'F'],
                        ['label' => 'Male', 'value' => 'M'],
                    ],
                ],
                [
                    'name' => 'age',
                    'label' => 'Age in Years',
                    'type' => 'number',
                ],
            ]),
        ]);

        $responseRecord = SurveyResponse::factory()->create([
            'survey_id' => $survey->id,
        ]);

        Answer::create([
            'response_id' => $responseRecord->id,
            'value' => json_encode([
                ['name' => 'gender', 'userData' => 'Female'],
                ['name' => 'age', 'userData' => '28'],
            ]),
        ]);

        $res = $this->actingAs($user)->get(route('surveys.export_spss_sav', $survey));
        $res->assertStatus(200);
        $this->assertTrue(str_contains($res->headers->get('content-disposition'), 'spss-dataset-test_data.sav'));
    }

    public function test_export_pdf_summary_generates_pdf_download(): void
    {
        $user = User::factory()->create();
        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'title' => 'PDF Summary Survey',
            'export_org_name' => 'Test Organization',
            'remove_kd_branding' => false,
            'json_schema' => json_encode([
                [
                    'name' => 'satisfaction',
                    'label' => 'Overall Satisfaction',
                    'type' => 'select',
                    'values' => [
                        ['label' => 'Good', 'value' => 'good'],
                        ['label' => 'Bad', 'value' => 'bad'],
                    ],
                ],
            ]),
        ]);

        $responseRecord = SurveyResponse::factory()->create([
            'survey_id' => $survey->id,
        ]);

        Answer::create([
            'response_id' => $responseRecord->id,
            'value' => json_encode([
                ['name' => 'satisfaction', 'userData' => 'good'],
            ]),
        ]);

        $res = $this->actingAs($user)->get(route('surveys.export_pdf_summary', $survey));
        $res->assertStatus(200);
        $this->assertTrue(str_contains($res->headers->get('content-type'), 'application/pdf'));
    }

    public function test_import_kdsurvey_package_creates_survey_and_responses(): void
    {
        $user = User::factory()->create();

        // Create a temporary zip package
        $tmpZipPath = tempnam(sys_get_temp_dir(), 'test_pkg') . '.kdsurvey';
        $zip = new \ZipArchive();
        $zip->open($tmpZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('survey.json', json_encode([
            'title' => 'Imported Package Test Survey',
            'description' => 'Test survey package import',
            'json_schema' => [
                ['name' => 'city', 'label' => 'City', 'type' => 'text'],
            ],
            'export_org_name' => 'Import Org',
        ]));
        $zip->addFromString('questions.json', json_encode([]));
        $zip->addFromString('responses.json', json_encode([
            [
                'guest_name' => 'Imported Person',
                'data' => ['city' => 'Nairobi'],
            ],
        ]));
        $zip->close();

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $tmpZipPath,
            'test.kdsurvey',
            'application/octet-stream',
            null,
            true
        );

        $res = $this->actingAs($user)->post(route('surveys.import.package'), [
            'file' => $uploadedFile,
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('surveys', [
            'title' => 'Imported Package Test Survey',
            'created_by' => $user->id,
        ]);

        $importedSurvey = Survey::where('title', 'Imported Package Test Survey')->first();
        $this->assertNotNull($importedSurvey);
        $this->assertDatabaseHas('responses', [
            'survey_id' => $importedSurvey->id,
            'guest_name' => 'Imported Person',
        ]);

        @unlink($tmpZipPath);
    }

    public function test_submitting_survey_redirects_to_thank_you_page(): void
    {
        $creator = User::factory()->create();
        $survey = Survey::factory()->create([
            'created_by' => $creator->id,
            'title' => 'Customer Experience 2026',
            'is_paid' => false,
            'json_schema' => json_encode([
                ['name' => 'feedback', 'type' => 'text', 'label' => 'Your Feedback']
            ])
        ]);

        // 1. Traditional POST submission
        $res = $this->post(route('surveys.submit', $survey), [
            'guest_name' => 'Alice Respondent',
            'terms_and_conditions' => '1',
            'feedback' => 'Great platform!'
        ]);

        $res->assertRedirect(route('surveys.thank_you', $survey));

        // 2. JSON submission
        $jsonRes = $this->post(route('surveys.submit', $survey), [
            'guest_name' => 'Bob Respondent',
            'terms_and_conditions' => '1',
            'is_json_submission' => '1',
            'json_data' => json_encode([
                ['name' => 'feedback', 'userData' => 'Very intuitive and fast.']
            ])
        ]);

        $jsonRes->assertJson([
            'success' => true,
            'redirect_url' => route('surveys.thank_you', $survey),
        ]);
    }

    public function test_thank_you_page_renders_feature_showcase_and_cta(): void
    {
        $creator = User::factory()->create();
        $survey = Survey::factory()->create([
            'created_by' => $creator->id,
            'title' => 'Academic Research Project',
        ]);

        // Guest visitor
        $res = $this->get(route('surveys.thank_you', $survey));
        $res->assertStatus(200);
        $res->assertSee('Thank you for your feedback!');
        $res->assertSee('Academic Research Project');
        $res->assertSee('Discover KDAnalytiks');
        $res->assertSee('Advanced Survey Builder');
        $res->assertSee('Socius AI Statistics');
        $res->assertSee('Plagiarism &amp; AI Check', false);
        $res->assertSee('Research Proposal Studio');
        $res->assertSee('Get Started Free');
        $res->assertSee(route('register', ['role' => 'independent']));
        $res->assertSee(route('login'));

        // Authenticated user
        $user = User::factory()->create();
        $authRes = $this->actingAs($user)->get(route('surveys.thank_you', $survey));
        $authRes->assertStatus(200);
        $authRes->assertSee('Go to Dashboard');
        $authRes->assertSee('Explore More Surveys');
    }
}


