<?php

namespace App\DataTables;

use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class PermissionDataTable
{
    protected array $protectedPermissions = [
        'view-dashboard',
        'user-list', 'user-create', 'user-edit', 'user-delete',
        'role-list', 'role-create', 'role-edit', 'role-delete',
        'permission-list', 'permission-create', 'permission-edit', 'permission-delete',
        'manage-settings'
    ];

    /**
     * Build the DataTable response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function make()
    {
        $permissions = Permission::with('roles');

        return DataTables::of($permissions)
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
            ->addColumn('actions', function ($permission) {
                $isProtected = in_array($permission->name, $this->protectedPermissions);
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
}
