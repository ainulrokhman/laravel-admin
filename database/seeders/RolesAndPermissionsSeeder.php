<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create Permissions
        $permissions = [
            'view-dashboard',
            
            'user-list',
            'user-create',
            'user-edit',
            'user-delete',
            
            'role-list',
            'role-create',
            'role-edit',
            'role-delete',
            
            'permission-list',
            'permission-create',
            'permission-edit',
            'permission-delete',
            
            'manage-settings',
            'activity-log-list',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 2. Create Roles and Assign Existing Permissions
        
        // User Role
        $userRole = Role::firstOrCreate(['name' => 'User']);
        $userRole->givePermissionTo('view-dashboard');

        // Admin Role
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->givePermissionTo([
            'view-dashboard',
            
            'user-list',
            'user-create',
            'user-edit',
            'user-delete',
            
            'role-list',
            'role-create',
            'role-edit',
            'role-delete',
            
            'permission-list',
            'activity-log-list',
        ]);

        // SuperAdmin Role
        $superAdminRole = Role::firstOrCreate(['name' => 'SuperAdmin']);
        // No explicit permissions assigned because it bypasses checks via Gate::before in AppServiceProvider

        // 3. Create Default Users for Testing
        
        // SuperAdmin User
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin User',
                'password' => Hash::make('password'),
            ]
        );
        $superAdmin->assignRole($superAdminRole);

        // Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole($adminRole);

        // Regular User
        $user = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Regular User',
                'password' => Hash::make('password'),
            ]
        );
        $user->assignRole($userRole);
    }
}
