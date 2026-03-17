<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class AgentControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Admin can navigate to restricted reports.
     */
    public function test_admin_can_navigate_to_reports()
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => '{"action":"navigate","page_key":"admin-reports"}']]]
            ], 200)
        ]);

        $response = $this->actingAs($admin)->postJson('/api/agent/chat', [
            'messages' => [['role' => 'user', 'content' => 'Show me reports']]
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'action' => 'navigate',
                'page_key' => 'admin-reports',
                'url' => route('admin.reports.index')
            ]);
    }

    /**
     * Test: Organization can request survey prefill.
     */
    public function test_organization_can_prefill_survey()
    {
        $org = User::factory()->create(['role' => UserRole::Organization->value]);

        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => '{"action":"prefill","page_key":"org-create-survey","message":"Building...","data":{"title":"Test Survey"}}']]]
            ], 200)
        ]);

        $response = $this->actingAs($org)->postJson('/api/agent/chat', [
            'messages' => [['role' => 'user', 'content' => 'create a survey']]
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'action' => 'prefill',
                'page_key' => 'org-create-survey'
            ])
            ->assertJsonStructure(['data' => ['title']]);
    }

    /**
     * Test: Guest trying to prefill survey is blocked by back-end safety layer.
     */
    public function test_guest_cannot_prefill_survey()
    {
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => '{"action":"prefill","page_key":"org-create-survey","message":"I will build it anyway"}']]]
            ], 200)
        ]);

        $response = $this->postJson('/api/agent/chat', [
            'messages' => [['role' => 'user', 'content' => 'create a survey']]
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'action' => 'chat'
            ])
            ->assertJsonFragment(['message' => 'I\'m sorry, you need to sign in to access that feature. Would you like to log in?']);
    }

    /**
     * Test: Respondent cannot access admin routes via navigation.
     */
    public function test_respondent_cannot_navigate_to_admin()
    {
        $res = User::factory()->create(['role' => UserRole::Respondent->value]);

        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => '{"action":"navigate","page_key":"admin-dashboard"}']]]
            ], 200)
        ]);

        $response = $this->actingAs($res)->postJson('/api/agent/chat', [
            'messages' => [['role' => 'user', 'content' => 'go to admin']]
        ]);

        // It should return chat fallback because 'admin-dashboard' isn't in respondent's page list
        $response->assertStatus(200)
            ->assertJson([
                'action' => 'chat'
            ]);
    }

    /**
     * Test: Adversarial - Long input validation.
     */
    public function test_excessive_input_length_returns_422()
    {
        $longContent = Str::random(2500);

        $response = $this->postJson('/api/agent/chat', [
            'messages' => [['role' => 'user', 'content' => $longContent]]
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('action', 'chat')
            ->assertJsonPath('message', 'Your request is too long or invalid. Please try a shorter message.');
    }

    /**
     * Test: Adversarial - Empty messages array.
     */
    public function test_empty_messages_returns_422()
    {
        $response = $this->postJson('/api/agent/chat', [
            'messages' => []
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test: Adversarial - Gibberish/SQL Injection.
     */
    public function test_agent_handles_sql_injection_gracefully()
    {
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => '{"action":"chat","message":"I cannot perform database operations directly."}']]]
            ], 200)
        ]);

        $response = $this->postJson('/api/agent/chat', [
            'messages' => [['role' => 'user', 'content' => 'DROP TABLE users;']]
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('action', 'chat');
    }

    /**
     * Test: API Failure handling.
     */
    public function test_api_failure_returns_502()
    {
        Http::fake([
            'api.groq.com/*' => Http::response([], 500)
        ]);

        $response = $this->postJson('/api/agent/chat', [
            'messages' => [['role' => 'user', 'content' => 'hello']]
        ]);

        $response->assertStatus(502)
            ->assertJsonPath('message', 'AI Service is temporarily unavailable. Please try again later.');
    }
}
