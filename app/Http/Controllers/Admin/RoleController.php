<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('role-list');

        if ($request->ajax()) {
            $roles = Role::withCount(['users', 'permissions'])->with('permissions');
            return datatables()->of($roles)
                ->addColumn('role_name', function ($role) {
                    return '<div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-shield-lock fs-5"></i>
                        </div>
                        <div>
                            <span class="fw-bold text-body d-block">' . e($role->name) . '</span>
                            <span class="text-muted small">Guard: ' . e($role->guard_name) . '</span>
                        </div>
                    </div>';
                })
                ->editColumn('permissions_count', function ($role) {
                    return '<span class="badge bg-info bg-opacity-10 text-info fw-medium py-2 px-3 fs-7 border border-info border-opacity-20 rounded-pill">' . $role->permissions_count . ' Permissions</span>';
                })
                ->editColumn('users_count', function ($role) {
                    return '<div class="text-center"><span class="badge bg-secondary bg-opacity-10 text-secondary fw-bold py-2 px-3 fs-7 border border-secondary border-opacity-20 rounded-pill">' . $role->users_count . ' Users</span></div>';
                })
                ->addColumn('permissions_preview', function ($role) {
                    if ($role->name === 'SuperAdmin') {
                        return '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10 rounded-pill py-1 px-2 text-xs">all-permissions (Bypass)</span>';
                    }
                    $allPerms = $role->permissions;
                    $previewPerms = $allPerms->take(3);
                    $remainingCount = $allPerms->count() - 3;
                    
                    $html = '<div class="d-flex flex-wrap gap-1" style="max-width: 320px;">';
                    foreach ($previewPerms as $perm) {
                        $html .= '<span class="badge bg-light text-secondary border rounded-pill py-1 px-2 text-xs">' . e($perm->name) . '</span>';
                    }
                    if ($remainingCount > 0) {
                        $html .= '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10 rounded-pill py-1 px-2 text-xs fw-bold">+' . $remainingCount . ' more</span>';
                    }
                    if ($previewPerms->isEmpty()) {
                        $html .= '<span class="text-muted small italic">None</span>';
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->addColumn('actions', function ($role) {
                    $html = '<div class="d-flex justify-content-end gap-2">';
                    if ($role->name === 'SuperAdmin') {
                        $html .= '<button class="btn btn-sm btn-light border" disabled title="SuperAdmin permissions are locked"><i class="bi bi-lock-fill text-muted me-1"></i> Locked</button>';
                    } else {
                        if (auth()->user()->can('role-edit')) {
                            $html .= '<a href="' . route('admin.roles.edit', $role->id) . '" class="btn btn-sm btn-light border py-1.5 px-2.5 rounded-2" title="Edit Role"><i class="bi bi-pencil-square text-primary"></i> Edit</a>';
                        }
                        if (auth()->user()->can('role-delete')) {
                            if (in_array($role->name, ['Admin', 'User'])) {
                                $html .= '<button class="btn btn-sm btn-light border py-1.5 px-2.5 rounded-2" disabled title="Cannot delete system role"><i class="bi bi-trash-fill text-muted"></i></button>';
                            } else {
                                $html .= '<form action="' . route('admin.roles.destroy', $role->id) . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this role? Any assigned users will lose these permissions.\')">';
                                $html .= csrf_field() . method_field('DELETE');
                                $html .= '<button type="submit" class="btn btn-sm btn-light border py-1.5 px-2.5 rounded-2" title="Delete Role"><i class="bi bi-trash text-danger"></i></button>';
                                $html .= '</form>';
                            }
                        }
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->rawColumns(['role_name', 'permissions_count', 'users_count', 'permissions_preview', 'actions'])
                ->make(true);
        }

        $roles = collect();
        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('role-create');

        $permissions = Permission::all();
        
        // Group permissions by category for cleaner UI rendering
        $groupedPermissions = $this->groupPermissions($permissions);

        return view('admin.roles.create', compact('groupedPermissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('role-create');

        $request->validate([
            'name' => ['required', 'string', 'unique:roles,name', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id']
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web'
        ]);

        if ($request->has('permissions')) {
            $permissions = Permission::whereIn('id', $request->permissions)->get();
            $role->syncPermissions($permissions);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role "' . $role->name . '" created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        Gate::authorize('role-edit');

        // Prevent editing SuperAdmin to avoid lockouts (can only view or edit other roles)
        if ($role->name === 'SuperAdmin') {
            return redirect()->route('admin.roles.index')
                ->with('error', 'The SuperAdmin role permissions are locked and cannot be modified.');
        }

        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        
        $groupedPermissions = $this->groupPermissions($permissions);

        return view('admin.roles.edit', compact('role', 'groupedPermissions', 'rolePermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        Gate::authorize('role-edit');

        if ($role->name === 'SuperAdmin') {
            return redirect()->route('admin.roles.index')
                ->with('error', 'The SuperAdmin role is protected and cannot be modified.');
        }

        $request->validate([
            'name' => ['required', 'string', 'unique:roles,name,' . $role->id, 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id']
        ]);

        // Don't allow changing the name of Admin and User system roles (to prevent breakage)
        $isSystemRole = in_array($role->name, ['Admin', 'User']);
        if ($isSystemRole && $request->name !== $role->name) {
            return redirect()->back()
                ->with('error', 'Cannot change the name of the system role "' . $role->name . '".');
        }

        $role->update([
            'name' => $request->name
        ]);

        $permissions = Permission::whereIn('id', $request->permissions ?? [])->get();
        $role->syncPermissions($permissions);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role "' . $role->name . '" updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        Gate::authorize('role-delete');

        // System roles cannot be deleted
        if (in_array($role->name, ['SuperAdmin', 'Admin', 'User'])) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Cannot delete system role "' . $role->name . '".');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    /**
     * Helper to group permissions by suffix/prefix for logical categorization.
     */
    private function groupPermissions($permissions)
    {
        $grouped = [];
        foreach ($permissions as $permission) {
            $parts = explode('-', $permission->name);
            if (count($parts) > 1) {
                $prefix = $parts[0];
                if ($prefix === 'view' || $prefix === 'manage') {
                    $category = ucfirst($parts[1]); // e.g. Dashboard, Settings
                } else {
                    $category = ucfirst($prefix); // e.g. User, Role, Permission
                }
            } else {
                $category = 'General';
            }
            $grouped[$category][] = $permission;
        }
        return $grouped;
    }
}
