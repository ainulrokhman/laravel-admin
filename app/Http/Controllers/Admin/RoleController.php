<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Contracts\RoleServiceInterface;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\DataTables\RoleDataTable;
use App\Support\SystemAccess;
use App\Http\Controllers\Admin\Concerns\HandlesServiceExceptions;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    use HandlesServiceExceptions;

    protected RoleServiceInterface $roleService;

    /**
     * RoleController constructor.
     */
    public function __construct(RoleServiceInterface $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, RoleDataTable $roleDataTable)
    {
        Gate::authorize('role-list');

        if ($request->ajax()) {
            return $roleDataTable->make();
        }

        return Inertia::render('Roles/Index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('role-create');

        return Inertia::render('Roles/Create', [
            'groupedPermissions' => $this->serializeGroupedPermissions(),
        ]);
    }

    /**
     * Group all permissions by category, shaped for the Vue role form:
     * category name mapped to a list of id/name pairs.
     */
    private function serializeGroupedPermissions(): array
    {
        $grouped = $this->roleService->groupPermissions(Permission::all());

        return collect($grouped)
            ->map(fn ($permissions) => collect($permissions)
                ->map(fn (Permission $permission) => ['id' => $permission->id, 'name' => $permission->name])
                ->values()
                ->all())
            ->all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        Gate::authorize('role-create');

        $role = $this->roleService->createRole($request->validated());

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role "' . $role->name . '" created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        Gate::authorize('role-edit');

        // Prevent editing SuperAdmin to avoid lockouts
        if ($role->name === SystemAccess::SUPER_ADMIN_ROLE) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'The SuperAdmin role permissions are locked and cannot be modified.');
        }

        return Inertia::render('Roles/Edit', [
            'role' => $role->only(['id', 'name']),
            'groupedPermissions' => $this->serializeGroupedPermissions(),
            'rolePermissions' => $role->permissions->pluck('id')->toArray(),
            // Built-in Admin/User roles keep their name to avoid breakage.
            'nameLocked' => in_array($role->name, [SystemAccess::ADMIN_ROLE, SystemAccess::USER_ROLE], true),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        Gate::authorize('role-edit');

        if ($errorRedirect = $this->runServiceAction(fn () => $this->roleService->updateRole($role, $request->validated()), 'admin.roles.index')) {
            return $errorRedirect;
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role "' . $role->name . '" updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        Gate::authorize('role-delete');

        if ($errorRedirect = $this->runServiceAction(fn () => $this->roleService->deleteRole($role), 'admin.roles.index')) {
            return $errorRedirect;
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
