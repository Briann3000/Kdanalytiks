<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Organization;
use App\Models\Independent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // --- Registration Tests ---

    public function test_organization_registration_page_loads(): void
    {
        $response = $this->get('/organization/register');
        $response->assertStatus(200);
        $response->assertSee('Organization Register');
    }

    public function test_independent_registration_page_loads(): void
    {
        $response = $this->get('/independent/register');
        $response->assertStatus(200);
        $response->assertSee('Independent Register');
    }

    public function test_respondent_registration_page_loads(): void
    {
        $response = $this->get('/respondent/register');
        $response->assertStatus(200);
        $response->assertSee('Respondent Register');
    }

    public function test_organization_can_register(): void
    {
        $response = $this->post('/organization/register', [
            'name' => 'Test Org User',
            'email' => 'org@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'organization_name' => 'Test Org',
        ]);

        $response->assertRedirect(route('organization.login'));
        $this->assertDatabaseHas('users', [
            'email' => 'org@test.com',
            'role' => 'organization',
        ]);
        $this->assertDatabaseHas('organizations', [
            'name' => 'Test Org',
        ]);
    }

    public function test_independent_can_register(): void
    {
        $response = $this->post('/independent/register', [
            'name' => 'Test Researcher',
            'email' => 'researcher@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'institution' => 'Test University',
            'research_area' => 'AI',
        ]);

        $response->assertRedirect(route('independent.login'));
        $this->assertDatabaseHas('users', [
            'email' => 'researcher@test.com',
            'role' => 'independent',
        ]);
        $this->assertDatabaseHas('independents', [
            'name' => 'Test Researcher',
            'institution' => 'Test University',
        ]);
    }

    public function test_respondent_can_register(): void
    {
        $response = $this->post('/respondent/register', [
            'name' => 'Test Respondent',
            'email' => 'respondent@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('respondent.login'));
        $this->assertDatabaseHas('users', [
            'email' => 'respondent@test.com',
            'role' => 'respondent',
        ]);
    }

    public function test_registration_fails_with_invalid_data(): void
    {
        $response = $this->post('/respondent/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::create([
            'name' => 'Existing',
            'email' => 'existing@test.com',
            'password' => bcrypt('password123'),
            'role' => 'respondent',
            'status' => 'active',
        ]);

        $response = $this->post('/respondent/register', [
            'name' => 'New User',
            'email' => 'existing@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    // --- Login Tests ---

    public function test_login_page_loads_for_each_role(): void
    {
        foreach (['admin', 'organization', 'independent', 'respondent'] as $role) {
            $response = $this->get("/{$role}/login");
            $response->assertStatus(200);
            $response->assertSee(ucfirst($role) . ' Login');
        }
    }

    public function test_active_user_can_login(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_pending_user_cannot_login(): void
    {
        User::create([
            'name' => 'Pending User',
            'email' => 'pending@test.com',
            'password' => bcrypt('password123'),
            'role' => 'organization',
            'status' => 'pending',
        ]);

        $response = $this->post('/organization/login', [
            'email' => 'pending@test.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertGuest();
    }

    public function test_login_with_wrong_password_fails(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => bcrypt('correctpassword'),
            'role' => 'respondent',
            'status' => 'active',
        ]);

        $response = $this->post('/respondent/login', [
            'email' => 'user@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('credentials');
        $this->assertGuest();
    }

    public function test_logout_works(): void
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($user);
        $response = $this->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }
}
