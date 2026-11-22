<?php

namespace App\DataTables;

use App\Models\User;
use App\Support\SystemAccess;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class UserDataTable
{
    /**
     * Avatar background colors, cycled by user id. The Vue table turns the
     * plain color name into the matching `bg-*` class.
     */
    private const AVATAR_COLORS = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];

    /**
     * Build the DataTable response.
     *
     * The endpoint speaks the standard Yajra protocol (draw, start, length,
     * search, order), but the added columns carry structured data instead of
     * pre-rendered HTML so the Vue table renders the cells itself.
     *
     * @return JsonResponse
     */
    public function make()
    {
        $query = User::with('roles')->select(['id', 'name', 'email', 'created_at']);
        $currentUser = auth()->user();

        return DataTables::of($query)
            ->addColumn('user_details', $this->userDetailsColumn())
            ->addColumn('assigned_roles', $this->assignedRolesColumn())
            ->addColumn('joined_date', $this->joinedDateColumn())
            ->addColumn('actions', $this->actionsColumn($currentUser))
            ->filterColumn('user_details', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            })
            ->orderColumn('user_details', function ($query, $order) {
                $query->orderBy('name', $order);
            })
            ->orderColumn('joined_date', function ($query, $order) {
                $query->orderBy('created_at', $order);
            })
            ->make(true);
    }

    /**
     * Build the `user_details` column data: identity plus the avatar
     * initials and color the client renders.
     */
    private function userDetailsColumn(): \Closure
    {
        return function ($user) {
            $initials = collect(explode(' ', $user->name))->map(fn ($n) => substr($n, 0, 1))->take(2)->join('');

            return [
                'name' => $user->name,
                'email' => $user->email,
                'initials' => strtoupper($initials),
                'color' => self::AVATAR_COLORS[$user->id % count(self::AVATAR_COLORS)],
            ];
        };
    }

    /**
     * Build the `assigned_roles` column data: the user's role names.
     */
    private function assignedRolesColumn(): \Closure
    {
        return function ($user) {
            return $user->roles->map(fn ($role) => ['name' => $role->name])->values()->all();
        };
    }

    /**
     * Build the `joined_date` column data: the formatted creation date.
     */
    private function joinedDateColumn(): \Closure
    {
        return function ($user) {
            return $user->created_at->format('M d, Y');
        };
    }

    /**
     * Build the `actions` column data.
     *
     * Mirrors the old server-rendered buttons: an edit URL when the current
     * user may edit, a delete URL when deletion is allowed, or the reason
     * deletion is blocked (own account or the protected Super Admin account)
     * so the client can render the disabled button with its tooltip.
     */
    private function actionsColumn(?User $currentUser): \Closure
    {
        return function ($user) use ($currentUser) {
            $actions = [
                'edit_url' => null,
                'delete_url' => null,
                'delete_disabled_reason' => null,
            ];

            if ($currentUser?->can('user-edit')) {
                $actions['edit_url'] = route('admin.users.edit', $user->id);
            }

            if ($currentUser?->can('user-delete')) {
                if ($currentUser->id === $user->id) {
                    $actions['delete_disabled_reason'] = 'You cannot delete yourself';
                } elseif ($user->email === SystemAccess::SUPER_ADMIN_EMAIL) {
                    $actions['delete_disabled_reason'] = 'Cannot delete Super Admin account';
                } else {
                    $actions['delete_url'] = route('admin.users.destroy', $user->id);
                }
            }

            return $actions;
        };
    }
}
