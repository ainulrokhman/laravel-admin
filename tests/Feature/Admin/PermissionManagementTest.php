<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionManagementTest extends TestCase
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
     * Test guest is redirected to login from the permission list.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.permissions.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test a user without the permission-list permission cannot access the permission list.
     */
    public function test_user_without_permission_cannot_access_permission_list(): void
    {
        $response = $this->actingAs($this->regularUser())
            ->get(route('admin.permissions.index'));

        $response->assertForbidden();
    }

    /**
     * Test an admin can view the permission list.
     */
    public function test_admin_can_view_permission_list(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.permissions.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.permissions.index');
    }

    /**
     * Test an admin cannot create a permission because the Admin role
     * only holds permission-list, not permission-create.
     */
    public function test_admin_cannot_create_permission(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('admin.permissions.store'), [
                'name' => 'export-reports',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('permissions', ['name' => 'export-reports']);
    }

    /**
     * Test a Super Admin can create a permission and its name is stored lowercase.
     */
    public function test_superadmin_can_create_permission_and_name_is_lowercased(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->post(route('admin.permissions.store'), [
                'name' => 'Export-Reports',
            ]);

        $response->assertRedirect(route('admin.permissions.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('permissions', ['name' => 'export-reports']);
    }

    /**
     * Test a core system permission cannot be updated.
     */
    public function test_protected_permission_cannot_be_updated(): void
    {
        $permission = Permission::where('name', 'user-list')->firstOrFail();

        $response = $this->actingAs($this->superAdmin())
            ->put(route('admin.permissions.update', $permission), [
                'name' => 'user-list-renamed',
            ]);

        $response->assertRedirect(route('admin.permissions.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('permissions', ['id' => $permission->id, 'name' => 'user-list']);
    }

    /**
     * Test a core system permission cannot be deleted.
     */
    public function test_protected_permission_cannot_be_deleted(): void
    {
        $permission = Permission::where('name', 'user-list')->firstOrFail();

        $response = $this->actingAs($this->superAdmin())
            ->delete(route('admin.permissions.destroy', $permission));

        $response->assertRedirect(route('admin.permissions.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('permissions', ['id' => $permission->id]);
    }

    /**
     * Test a custom permission can be updated.
     */
    public function test_custom_permission_can_be_updated(): void
    {
        $permission = Permission::create(['name' => 'custom-thing', 'guard_name' => 'web']);

        $response = $this->actingAs($this->superAdmin())
            ->put(route('admin.permissions.update', $permission), [
                'name' => 'custom-renamed',
            ]);

        $response->assertRedirect(route('admin.permissions.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('permissions', ['id' => $permission->id, 'name' => 'custom-renamed']);
    }

    /**
     * Test a custom permission can be deleted.
     */
    public function test_custom_permission_can_be_deleted(): void
    {
        $permission = Permission::create(['name' => 'custom-thing', 'guard_name' => 'web']);

        $response = $this->actingAs($this->superAdmin())
            ->delete(route('admin.permissions.destroy', $permission));

        $response->assertRedirect(route('admin.permissions.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
    }
}
