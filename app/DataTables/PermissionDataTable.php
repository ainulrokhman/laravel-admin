<?php

namespace App\DataTables;

use App\Contracts\PermissionServiceInterface;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

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
     * Same Yajra protocol as before, but the added columns carry structured
     * data instead of pre-rendered HTML so the Vue table renders the cells.
     *
     * @return JsonResponse
     */
    public function make()
    {
        $permissions = Permission::select(['id', 'name', 'guard_name'])->with('roles');
        $currentUser = auth()->user();

        return DataTables::of($permissions)
            ->addColumn('permission_name', $this->permissionNameColumn())
            ->addColumn('roles_using_this', $this->rolesUsingThisColumn())
            ->addColumn('actions', $this->actionsColumn($currentUser))
            ->make(true);
    }

    /**
     * Build the `permission_name` column data: name plus guard.
     */
    private function permissionNameColumn(): \Closure
    {
        return function ($permission) {
            return [
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
            ];
        };
    }

    /**
     * Build the `roles_using_this` column data: names of the roles that
     * hold this permission.
     */
    private function rolesUsingThisColumn(): \Closure
    {
        return function ($permission) {
            return $permission->roles->map(fn ($role) => $role->name)->values()->all();
        };
    }

    /**
     * Build the `actions` column data.
     *
     * Mirrors the old server-rendered buttons: core system permissions are
     * flagged as protected and expose no actions; custom permissions expose
     * edit/delete URLs according to the current user's permissions.
     */
    private function actionsColumn(?\App\Models\User $currentUser): \Closure
    {
        return function ($permission) use ($currentUser) {
            $actions = [
                'protected' => $this->permissionService->isPermissionProtected($permission),
                'edit_url' => null,
                'delete_url' => null,
            ];

            if ($actions['protected']) {
                return $actions;
            }

            if ($currentUser?->can('permission-edit')) {
                $actions['edit_url'] = route('admin.permissions.edit', $permission->id);
            }

            if ($currentUser?->can('permission-delete')) {
                $actions['delete_url'] = route('admin.permissions.destroy', $permission->id);
            }

            return $actions;
        };
    }
}
