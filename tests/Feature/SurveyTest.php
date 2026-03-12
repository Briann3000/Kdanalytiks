<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Organization;
use App\Models\Survey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyTest extends TestCase
{
    use RefreshDatabase;

    private User $orgUser;
    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgUser = User::create([
            'name' => 'Org User',
            'email' => 'org@test.com',
            'password' => bcrypt('password123'),
            'role' => 'organization',
            'status' => 'active',
        ]);

        $this->organization = Organization::create([
            'user_id' => $this->orgUser->id,
            'name' => 'Test Organization',
        ]);
    }

    public function test_organization_can_view_create_survey_page(): void
    {
        $response = $this->actingAs($this->orgUser)->get('/organization/surveys/create');
        $response->assertStatus(200);
        $response->assertSee('Create New Survey');
    }

    public function test_organization_can_create_survey(): void
    {
        $response = $this->actingAs($this->orgUser)->post('/organization/surveys', [
            'title' => 'Customer Satisfaction Survey',
            'description' => 'A test survey',
            'category' => 'Marketing',
            'type' => 'public',
        ]);

        $response->assertRedirect(route('organization.surveys'));
        $this->assertDatabaseHas('surveys', [
            'title' => 'Customer Satisfaction Survey',
            'organization_id' => $this->organization->id,
            'category' => 'Marketing',
            'type' => 'public',
            'status' => 'draft',
        ]);
    }

    public function test_survey_requires_title(): void
    {
        $response = $this->actingAs($this->orgUser)->post('/organization/surveys', [
            'title' => '',
            'category' => 'Marketing',
            'type' => 'public',
        ]);

        $response->assertSessionHasErrors(['title']);
    }

    public function test_survey_requires_valid_category(): void
    {
        $response = $this->actingAs($this->orgUser)->post('/organization/surveys', [
            'title' => 'Test Survey',
            'category' => 'InvalidCategory',
            'type' => 'public',
        ]);

        $response->assertSessionHasErrors(['category']);
    }

    public function test_survey_requires_valid_type(): void
    {
        $response = $this->actingAs($this->orgUser)->post('/organization/surveys', [
            'title' => 'Test Survey',
            'category' => 'Marketing',
            'type' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['type']);
    }

    public function test_organization_can_edit_own_survey(): void
    {
        $survey = Survey::create([
            'organization_id' => $this->organization->id,
            'title' => 'Original Title',
            'category' => 'Academic',
            'type' => 'public',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->orgUser)->put("/organization/surveys/{$survey->id}", [
            'title' => 'Updated Title',
            'category' => 'Product',
            'type' => 'invitation',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('organization.surveys'));
        $this->assertDatabaseHas('surveys', [
            'id' => $survey->id,
            'title' => 'Updated Title',
            'category' => 'Product',
            'type' => 'invitation',
            'status' => 'active',
        ]);
    }

    public function test_organization_can_delete_own_survey(): void
    {
        $survey = Survey::create([
            'organization_id' => $this->organization->id,
            'title' => 'Survey to Delete',
            'category' => 'Academic',
            'type' => 'public',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->orgUser)->delete("/organization/surveys/{$survey->id}");

        $response->assertRedirect(route('organization.surveys'));
        $this->assertDatabaseMissing('surveys', ['id' => $survey->id]);
    }

    public function test_organization_cannot_edit_other_organizations_survey(): void
    {
        $otherUser = User::create([
            'name' => 'Other Org',
            'email' => 'other@test.com',
            'password' => bcrypt('password123'),
            'role' => 'organization',
            'status' => 'active',
        ]);
        $otherOrg = Organization::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Org',
        ]);
        $otherSurvey = Survey::create([
            'organization_id' => $otherOrg->id,
            'title' => 'Other Survey',
            'category' => 'Academic',
            'type' => 'public',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->orgUser)->get("/organization/surveys/{$otherSurvey->id}/edit");
        $response->assertStatus(403);
    }

    public function test_surveys_list_shows_on_organization_dashboard(): void
    {
        Survey::create([
            'organization_id' => $this->organization->id,
            'title' => 'Survey Alpha',
            'category' => 'Marketing',
            'type' => 'public',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->orgUser)->get('/organization/surveys');
        $response->assertStatus(200);
        $response->assertSee('Survey Alpha');
    }
}
