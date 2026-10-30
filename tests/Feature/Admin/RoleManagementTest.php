<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
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

    private function regularUser(): User
    {
        return User::where('email', 'user@example.com')->firstOrFail();
    }

    /**
     * Test guest is redirected to login from the role list.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.roles.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test a user without the role-list permission cannot access the role list.
     */
    public function test_user_without_permission_cannot_access_role_list(): void
    {
        $response = $this->actingAs($this->regularUser())
            ->get(route('admin.roles.index'));

        $response->assertForbidden();
    }

    /**
     * Test an admin can view the role list.
     */
    public function test_admin_can_view_role_list(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.roles.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.roles.index');
    }

    /**
     * Test an admin can create a role with permissions.
     */
    public function test_admin_can_create_role_with_permissions(): void
    {
        $permission = Permission::where('name', 'user-list')->firstOrFail();

        $response = $this->actingAs($this->admin())
            ->post(route('admin.roles.store'), [
                'name' => 'Manager',
                'permissions' => [$permission->id],
            ]);

        $response->assertRedirect(route('admin.roles.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('roles', ['name' => 'Manager']);
        $this->assertTrue(Role::where('name', 'Manager')->first()->hasPermissionTo('user-list'));
    }

    /**
     * Test a system role cannot be renamed.
     */
    public function test_system_role_cannot_be_renamed(): void
    {
        $adminRole = Role::where('name', 'Admin')->firstOrFail();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.roles.edit', $adminRole))
            ->put(route('admin.roles.update', $adminRole), [
                'name' => 'RenamedAdmin',
                'permissions' => [],
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['id' => $adminRole->id, 'name' => 'Admin']);
        $this->assertDatabaseMissing('roles', ['name' => 'RenamedAdmin']);
    }

    /**
     * Test a system role cannot be deleted.
     */
    public function test_system_role_cannot_be_deleted(): void
    {
        $userRole = Role::where('name', 'User')->firstOrFail();

        $response = $this->actingAs($this->admin())
            ->delete(route('admin.roles.destroy', $userRole));

        $response->assertRedirect(route('admin.roles.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['id' => $userRole->id]);
    }

    /**
     * Test a non-system role can be deleted.
     */
    public function test_non_system_role_can_be_deleted(): void
    {
        $role = Role::create(['name' => 'Temporary', 'guard_name' => 'web']);

        $response = $this->actingAs($this->admin())
            ->delete(route('admin.roles.destroy', $role));

        $response->assertRedirect(route('admin.roles.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }
}
