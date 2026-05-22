<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('permission-list');

        if ($request->ajax()) {
            $permissions = Permission::with('roles');
            
            $protectedPermissions = [
                'view-dashboard', 
                'user-list', 'user-create', 'user-edit', 'user-delete',
                'role-list', 'role-create', 'role-edit', 'role-delete',
                'permission-list', 'permission-create', 'permission-edit', 'permission-delete',
                'manage-settings'
            ];

            return datatables()->of($permissions)
                ->addColumn('permission_name', function ($permission) {
                    return '<div class="d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 text-info rounded-3 p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-key fs-5"></i>
                        </div>
                        <div>
                            <span class="fw-bold text-body d-block">' . e($permission->name) . '</span>
                            <span class="text-muted small">Guard: ' . e($permission->guard_name) . '</span>
                        </div>
                    </div>';
                })
                ->addColumn('roles_using_this', function ($permission) {
                    $html = '<div class="d-flex flex-wrap gap-1" style="max-width: 250px;">';
                    foreach ($permission->roles as $role) {
                        $html .= '<span class="badge bg-light text-secondary border rounded-pill py-1 px-2 text-xs">' . e($role->name) . '</span>';
                    }
                    if ($permission->roles->isEmpty()) {
                        $html .= '<span class="text-muted small italic">Not assigned</span>';
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->addColumn('actions', function ($permission) use ($protectedPermissions) {
                    $isProtected = in_array($permission->name, $protectedPermissions);
                    $html = '<div class="d-flex justify-content-end gap-2">';
                    if ($isProtected) {
                        $html .= '<span class="badge bg-light text-secondary border d-flex align-items-center py-2 px-3 rounded-2 text-xs"><i class="bi bi-lock-fill text-warning me-1"></i> Core</span>';
                    } else {
                        if (auth()->user()->can('permission-edit')) {
                            $html .= '<a href="' . route('admin.permissions.edit', $permission->id) . '" class="btn btn-sm btn-light border py-1.5 px-2.5 rounded-2" title="Edit Permission"><i class="bi bi-pencil-square text-primary"></i></a>';
                        }
                        if (auth()->user()->can('permission-delete')) {
                            $html .= '<form action="' . route('admin.permissions.destroy', $permission->id) . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this permission? This action will remove it from all assigned roles.\')">';
                            $html .= csrf_field() . method_field('DELETE');
                            $html .= '<button type="submit" class="btn btn-sm btn-light border py-1.5 px-2.5 rounded-2" title="Delete Permission"><i class="bi bi-trash text-danger"></i></button>';
                            $html .= '</form>';
                        }
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->rawColumns(['permission_name', 'roles_using_this', 'actions'])
                ->make(true);
        }

        $permissions = collect();
        return view('admin.permissions.index', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('permission-create');

        $request->validate([
            'name' => ['required', 'string', 'unique:permissions,name', 'max:255', 'regex:/^[a-zA-Z0-9\-]+$/'],
        ], [
            'name.regex' => 'The permission name must only contain letters, numbers, and dashes (e.g. manage-users).'
        ]);

        $permission = Permission::create([
            'name' => strtolower($request->name),
            'guard_name' => 'web'
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission "' . $permission->name . '" created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permission $permission)
    {
        Gate::authorize('permission-edit');

        // Protected system permissions cannot be modified
        $protectedPermissions = [
            'view-dashboard', 
            'user-list', 'user-create', 'user-edit', 'user-delete',
            'role-list', 'role-create', 'role-edit', 'role-delete',
            'permission-list', 'permission-create', 'permission-edit', 'permission-delete',
            'manage-settings'
        ];
        if (in_array($permission->name, $protectedPermissions)) {
            return redirect()->route('admin.permissions.index')
                ->with('error', 'The core system permission "' . $permission->name . '" is protected and cannot be edited.');
        }

        return view('admin.permissions.edit', compact('permission'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Permission $permission)
    {
        Gate::authorize('permission-edit');

        $protectedPermissions = [
            'view-dashboard', 
            'user-list', 'user-create', 'user-edit', 'user-delete',
            'role-list', 'role-create', 'role-edit', 'role-delete',
            'permission-list', 'permission-create', 'permission-edit', 'permission-delete',
            'manage-settings'
        ];
        if (in_array($permission->name, $protectedPermissions)) {
            return redirect()->route('admin.permissions.index')
                ->with('error', 'The core system permission "' . $permission->name . '" is protected and cannot be modified.');
        }

        $request->validate([
            'name' => ['required', 'string', 'unique:permissions,name,' . $permission->id, 'max:255', 'regex:/^[a-zA-Z0-9\-]+$/'],
        ], [
            'name.regex' => 'The permission name must only contain letters, numbers, and dashes (e.g. manage-users).'
        ]);

        $oldName = $permission->name;
        $permission->update([
            'name' => strtolower($request->name)
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission updated from "' . $oldName . '" to "' . $permission->name . '" successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        Gate::authorize('permission-delete');

        // Protected system permissions cannot be deleted
        $protectedPermissions = [
            'view-dashboard', 
            'user-list', 'user-create', 'user-edit', 'user-delete',
            'role-list', 'role-create', 'role-edit', 'role-delete',
            'permission-list', 'permission-create', 'permission-edit', 'permission-delete',
            'manage-settings'
        ];
        if (in_array($permission->name, $protectedPermissions)) {
            return redirect()->route('admin.permissions.index')
                ->with('error', 'Cannot delete core system permission "' . $permission->name . '".');
        }

        $permission->delete();

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }
}
