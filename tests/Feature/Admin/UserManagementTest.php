<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed default roles, permissions, and demo users
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    private function superAdmin(): User
    {
        return User::where('email', 'superadmin@example.com')->firstOrFail();
    }

    private function regularUser(): User
    {
        return User::where('email', 'user@example.com')->firstOrFail();
    }

    /**
     * Test guest is redirected to login from the user list.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.users.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test a user without the user-list permission cannot access the user list.
     */
    public function test_user_without_permission_cannot_access_user_list(): void
    {
        $response = $this->actingAs($this->regularUser())
            ->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    /**
     * Test an admin can view the user list.
     */
    public function test_admin_can_view_user_list(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.index');
    }

    /**
     * Test an admin can create a user and assign a role.
     */
    public function test_admin_can_create_user_with_role(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => 'New Member',
                'email' => 'member@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'roles' => ['Admin'],
            ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['email' => 'member@example.com']);
        $this->assertTrue(User::where('email', 'member@example.com')->first()->hasRole('Admin'));
    }

    /**
     * Test a created user without roles receives the default User role.
     */
    public function test_created_user_without_role_gets_default_user_role(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => 'Plain Member',
                'email' => 'plain@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $this->assertTrue(User::where('email', 'plain@example.com')->first()->hasRole('User'));
    }

    /**
     * Test an admin can update a user.
     */
    public function test_admin_can_update_user(): void
    {
        $user = User::factory()->create(['name' => 'Before Update']);

        $response = $this->actingAs($this->admin())
            ->put(route('admin.users.update', $user), [
                'name' => 'After Update',
                'email' => $user->email,
                'roles' => ['User'],
            ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'After Update']);
    }

    /**
     * Test a user cannot delete their own account.
     */
    public function test_user_cannot_delete_own_account(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    /**
     * Test the Super Admin seed account email cannot be changed through user management.
     */
    public function test_superadmin_seed_email_cannot_be_changed(): void
    {
        $superAdmin = $this->superAdmin();

        $response = $this->actingAs($superAdmin)
            ->from(route('admin.users.edit', $superAdmin))
            ->put(route('admin.users.update', $superAdmin), [
                'name' => $superAdmin->name,
                'email' => 'changed@example.com',
                'roles' => ['SuperAdmin'],
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'email' => 'superadmin@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'changed@example.com']);
    }
}
