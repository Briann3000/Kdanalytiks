<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role, string $status = 'active'): User
    {
        return User::create([
            'name' => ucfirst($role) . ' User',
            'email' => "{$role}@test.com",
            'password' => bcrypt('password123'),
            'role' => $role,
            'status' => $status,
        ]);
    }

    // --- Admin Access ---

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = $this->createUser('admin');
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertStatus(200);
    }

    public function test_organization_cannot_access_admin_dashboard(): void
    {
        $orgUser = $this->createUser('organization');
        $response = $this->actingAs($orgUser)->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_redirected_from_admin_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect();
    }

    // --- Organization Access ---

    public function test_organization_can_access_organization_dashboard(): void
    {
        $orgUser = $this->createUser('organization');
        $response = $this->actingAs($orgUser)->get('/organization/dashboard');
        $response->assertStatus(200);
    }

    public function test_admin_cannot_access_organization_dashboard(): void
    {
        $admin = $this->createUser('admin');
        $response = $this->actingAs($admin)->get('/organization/dashboard');
        $response->assertStatus(403);
    }

    // --- Independent Access ---

    public function test_independent_can_access_independent_dashboard(): void
    {
        $independent = $this->createUser('independent');
        $response = $this->actingAs($independent)->get('/independent/dashboard');
        $response->assertStatus(200);
    }

    public function test_respondent_cannot_access_independent_dashboard(): void
    {
        $respondent = $this->createUser('respondent');
        $response = $this->actingAs($respondent)->get('/independent/dashboard');
        $response->assertStatus(403);
    }

    // --- Respondent Access ---

    public function test_respondent_can_access_respondent_dashboard(): void
    {
        $respondent = $this->createUser('respondent');
        $response = $this->actingAs($respondent)->get('/respondent/dashboard');
        $response->assertStatus(200);
    }

    public function test_admin_cannot_access_respondent_dashboard(): void
    {
        $admin = $this->createUser('admin');
        $response = $this->actingAs($admin)->get('/respondent/dashboard');
        $response->assertStatus(403);
    }

    // --- Admin Management Routes ---

    public function test_admin_can_access_users_page(): void
    {
        $admin = $this->createUser('admin');
        $response = $this->actingAs($admin)->get('/admin/users');
        $response->assertStatus(200);
    }

    public function test_admin_can_access_surveys_page(): void
    {
        $admin = $this->createUser('admin');
        $response = $this->actingAs($admin)->get('/admin/surveys');
        $response->assertStatus(200);
    }

    public function test_admin_can_access_reports_page(): void
    {
        $admin = $this->createUser('admin');
        $response = $this->actingAs($admin)->get('/admin/reports');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_admin_users(): void
    {
        $orgUser = $this->createUser('organization');
        $response = $this->actingAs($orgUser)->get('/admin/users');
        $response->assertStatus(403);
    }
}
