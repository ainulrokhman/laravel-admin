<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed default roles and permissions
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Test guest is redirected to login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test user with view-dashboard permission can access the dashboard.
     */
    public function test_user_with_permission_can_access_dashboard(): void
    {
        // Get the regular user seeded by RolesAndPermissionsSeeder (who has view-dashboard permission)
        $user = User::where('email', 'user@example.com')->first();

        $response = $this->actingAs($user)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('usersCount')
            ->has('rolesCount')
            ->has('permissionsCount')
            ->has('recentUsers')
            ->has('auth.user')
            // The regular user holds exactly the view-dashboard permission,
            // and the shared list must carry its name for menu gating.
            ->has('auth.permissions', 1)
            ->where('auth.permissions.0', 'view-dashboard')
        );
    }

    /**
     * Test the shared permissions for a SuperAdmin contain every permission,
     * mirroring the Gate::before bypass (the role itself syncs none).
     */
    public function test_super_admin_shared_permissions_contain_all_permissions(): void
    {
        $superAdmin = User::where('email', 'superadmin@example.com')->first();

        $response = $this->actingAs($superAdmin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('auth.permissions', \Spatie\Permission\Models\Permission::count())
        );
    }

    /**
     * Test user without view-dashboard permission cannot access dashboard.
     */
    public function test_user_without_permission_cannot_access_dashboard(): void
    {
        // Create a user without any role/permission
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }
}
