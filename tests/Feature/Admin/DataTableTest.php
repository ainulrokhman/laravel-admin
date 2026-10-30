<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DataTableTest extends TestCase
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

    /**
     * Call an index route the way Yajra DataTables does from the browser.
     */
    private function datatableJson(string $routeName, int $length = 10): TestResponse
    {
        return $this->actingAs($this->admin())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route($routeName, ['draw' => 1, 'start' => 0, 'length' => $length]));
    }

    /**
     * Test the users DataTable returns the seeded users as JSON.
     */
    public function test_users_datatable_returns_seeded_users(): void
    {
        $response = $this->datatableJson('admin.users.index');

        $response->assertOk();
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        $data = $response->json();
        $this->assertSame(3, $data['recordsTotal']);
        $this->assertArrayHasKey('user_details', $data['data'][0]);
        $this->assertArrayHasKey('assigned_roles', $data['data'][0]);
        $this->assertArrayHasKey('actions', $data['data'][0]);
        $this->assertStringContainsString('superadmin@example.com', json_encode($data['data']));
    }

    /**
     * Test the roles DataTable returns the seeded roles as JSON.
     */
    public function test_roles_datatable_returns_seeded_roles(): void
    {
        $response = $this->datatableJson('admin.roles.index');

        $response->assertOk();
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        $data = $response->json();
        $this->assertSame(3, $data['recordsTotal']);
        $this->assertArrayHasKey('role_name', $data['data'][0]);
        $this->assertArrayHasKey('permissions_preview', $data['data'][0]);
        $this->assertArrayHasKey('actions', $data['data'][0]);
        $this->assertStringContainsString('SuperAdmin', json_encode($data['data']));
    }

    /**
     * Test the permissions DataTable returns the seeded permissions as JSON.
     */
    public function test_permissions_datatable_returns_seeded_permissions(): void
    {
        // Ask for one full page: the default page size of 10 would cut
        // the list before reaching every seeded permission.
        $response = $this->datatableJson('admin.permissions.index', 25);

        $response->assertOk();
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        $data = $response->json();
        $this->assertSame(15, $data['recordsTotal']);
        $this->assertArrayHasKey('permission_name', $data['data'][0]);
        $this->assertArrayHasKey('roles_using_this', $data['data'][0]);
        $this->assertArrayHasKey('actions', $data['data'][0]);
        $this->assertStringContainsString('user-list', json_encode($data['data']));
    }

    /**
     * Test the activity logs DataTable responds with the expected JSON shape.
     */
    public function test_activity_logs_datatable_returns_expected_structure(): void
    {
        $response = $this->datatableJson('admin.activity-logs.index');

        $response->assertOk();
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        $data = $response->json();
        $this->assertIsArray($data['data']);

        // Seeding creates activity entries; when rows exist they must
        // carry the log columns the table renders.
        if ($data['recordsTotal'] > 0) {
            $this->assertArrayHasKey('actor', $data['data'][0]);
            $this->assertArrayHasKey('action', $data['data'][0]);
            $this->assertArrayHasKey('actions', $data['data'][0]);
        }
    }
}
