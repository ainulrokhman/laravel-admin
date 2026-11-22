<?php

namespace App\DataTables;

use App\Contracts\PermissionServiceInterface;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;
use App\Support\TableActions;

class PermissionDataTable
{
    protected PermissionServiceInterface $permissionService;

    public function __construct(PermissionServiceInterface $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Build the DataTable response.
     *
     * @return JsonResponse
     */
    public function make()
    {
        $permissions = Permission::select(['id', 'name', 'guard_name'])->with('roles');

        return DataTables::of($permissions)
            ->addColumn('permission_name', $this->permissionNameColumn())
            ->addColumn('roles_using_this', $this->rolesUsingThisColumn())
            ->addColumn('actions', $this->actionsColumn())
            ->rawColumns(['permission_name', 'roles_using_this', 'actions'])
            ->make(true);
    }

    /**
     * Build the `permission_name` column renderer.
     */
    private function permissionNameColumn(): \Closure
    {
        return function ($permission) {
            return '<div class="d-flex align-items-center">
                    <div class="bg-info bg-opacity-10 text-info rounded-3 p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-key fs-5"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-body d-block">'.e($permission->name).'</span>
                        <span class="text-muted small">Guard: '.e($permission->guard_name).'</span>
                    </div>
                </div>';
        };
    }

    /**
     * Build the `roles_using_this` column renderer.
     */
    private function rolesUsingThisColumn(): \Closure
    {
        return function ($permission) {
            $html = '<div class="d-flex flex-wrap gap-1" style="max-width: 250px;">';
            foreach ($permission->roles as $role) {
                $html .= '<span class="badge bg-light text-secondary border rounded-pill py-1 px-2 text-xs">'.e($role->name).'</span>';
            }
            if ($permission->roles->isEmpty()) {
                $html .= '<span class="text-muted small italic">Not assigned</span>';
            }
            $html .= '</div>';

            return $html;
        };
    }

    /**
     * Build the `actions` column renderer.
     */
    private function actionsColumn(): \Closure
    {
        return function ($permission) {
            $isProtected = $this->permissionService->isPermissionProtected($permission);
            $html = TableActions::openGroup();
            if ($isProtected) {
                $html .= TableActions::locked('Core permission is protected');
            } else {
                if (auth()->user()->can('permission-edit')) {
                    $html .= TableActions::link(route('admin.permissions.edit', $permission->id), 'bi-pencil-square', 'primary', 'Edit');
                }
                if (auth()->user()->can('permission-delete')) {
                    $html .= TableActions::deleteForm(route('admin.permissions.destroy', $permission->id), 'Are you sure you want to delete this permission? This action will remove it from all assigned roles.');
                }
            }
            $html .= TableActions::closeGroup();

            return $html;
        };
    }
}
