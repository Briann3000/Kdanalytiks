<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgentChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_agent_chat_returns_successful_navigation()
    {
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'action' => 'navigate',
                                'page_key' => 'org-login',
                                'message' => 'Taking you to organization login...'
                            ])
                        ]
                    ]
                ]
            ], 200)
        ]);

        $response = $this->postJson(route('api.agent.chat'), [
            'messages' => [['role' => 'user', 'content' => 'how do i login as an organization?']]
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'action' => 'navigate',
                'page_key' => 'org-login',
                'url' => route('organization.login')
            ]);
    }

    public function test_agent_chat_returns_successful_ai_navigation()
    {
        /** @var User $user */
        $user = User::factory()->create([
            'role' => UserRole::Organization->value,
            'status' => UserStatus::Active->value
        ]);

        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'action' => 'navigate',
                                'page_key' => 'org-responses',
                                'message' => 'Navigating to responses...'
                            ])
                        ]
                    ]
                ]
            ], 200)
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('api.agent.chat'), [
                'messages' => [['role' => 'user', 'content' => 'show my responses']]
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'action' => 'navigate',
                'page_key' => 'org-responses',
                'url' => route('organization.responses.index')
            ]);
    }

    public function test_agent_chat_handles_api_errors_gracefully()
    {
        /** @var User $user */
        $user = User::factory()->create();

        Http::fake([
            'api.groq.com/*' => Http::response(['error' => 'API Down'], 502)
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('api.agent.chat'), [
                'messages' => [['role' => 'user', 'content' => 'hello']]
            ]);

        $response->assertStatus(502)
            ->assertJsonPath('action', 'chat')
            ->assertJsonPath('message', 'AI Service is temporarily unavailable. Please try again later.');
    }
}
