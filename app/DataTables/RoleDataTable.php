<?php

namespace App\DataTables;

use App\Contracts\RoleServiceInterface;
use App\Support\SystemAccess;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class RoleDataTable
{
    protected RoleServiceInterface $roleService;

    public function __construct(RoleServiceInterface $roleService)
    {
        $this->roleService = $roleService;
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
        $roles = Role::select(['id', 'name', 'guard_name'])->withCount(['users', 'permissions'])->with('permissions');
        $currentUser = auth()->user();

        return DataTables::of($roles)
            ->addColumn('role_name', $this->roleNameColumn())
            ->editColumn('permissions_count', fn ($role) => $role->permissions_count)
            ->editColumn('users_count', fn ($role) => $role->users_count)
            ->addColumn('permissions_preview', $this->permissionsPreviewColumn())
            ->addColumn('actions', $this->actionsColumn($currentUser))
            ->make(true);
    }

    /**
     * Build the `role_name` column data: name plus guard.
     */
    private function roleNameColumn(): \Closure
    {
        return function ($role) {
            return [
                'name' => $role->name,
                'guard_name' => $role->guard_name,
            ];
        };
    }

    /**
     * Build the `permissions_preview` column data: the first three permission
     * names plus how many more exist, or the bypass flag for SuperAdmin
     * (whose permissions are implicit via Gate::before).
     */
    private function permissionsPreviewColumn(): \Closure
    {
        return function ($role) {
            if ($role->name === SystemAccess::SUPER_ADMIN_ROLE) {
                return ['bypass' => true, 'preview' => [], 'remaining' => 0];
            }

            $allPerms = $role->permissions;

            return [
                'bypass' => false,
                'preview' => $allPerms->take(3)->map(fn ($perm) => $perm->name)->values()->all(),
                'remaining' => max(0, $allPerms->count() - 3),
            ];
        };
    }

    /**
     * Build the `actions` column data.
     *
     * Mirrors the old server-rendered buttons: the SuperAdmin role is locked
     * outright; other roles expose an edit URL when the current user may
     * edit, and a delete URL unless the role is a protected system role.
     */
    private function actionsColumn(?\App\Models\User $currentUser): \Closure
    {
        return function ($role) use ($currentUser) {
            $actions = [
                'locked' => $role->name === SystemAccess::SUPER_ADMIN_ROLE,
                'edit_url' => null,
                'delete_url' => null,
                'delete_disabled_reason' => null,
            ];

            if ($actions['locked']) {
                return $actions;
            }

            if ($currentUser?->can('role-edit')) {
                $actions['edit_url'] = route('admin.roles.edit', $role->id);
            }

            if ($currentUser?->can('role-delete')) {
                if ($this->roleService->isSystemRole($role)) {
                    $actions['delete_disabled_reason'] = 'Cannot delete system role';
                } else {
                    $actions['delete_url'] = route('admin.roles.destroy', $role->id);
                }
            }

            return $actions;
        };
    }
}
