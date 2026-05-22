<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('user-list');

        $search = $request->get('search');
        
        if ($request->ajax()) {
            $query = User::with('roles')->select('users.*');
            return datatables()->of($query)
                ->addColumn('user_details', function ($user) {
                    $initials = collect(explode(' ', $user->name))->map(fn($n) => substr($n, 0, 1))->take(2)->join('');
                    $colors = ['bg-primary', 'bg-success', 'bg-info', 'bg-warning', 'bg-danger', 'bg-secondary'];
                    $bgColor = $colors[$user->id % count($colors)];
                    $avatar = '<div class="' . $bgColor . ' text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3 shadow-sm" style="width: 44px; height: 44px; font-size: 0.95rem;">' . strtoupper($initials) . '</div>';
                    return '<div class="d-flex align-items-center">' . $avatar . '<div><span class="fw-bold text-body d-block">' . e($user->name) . '</span><span class="text-muted small">' . e($user->email) . '</span></div></div>';
                })
                ->addColumn('assigned_roles', function ($user) {
                    $html = '<div class="d-flex flex-wrap gap-1">';
                    foreach ($user->roles as $role) {
                        $badgeClass = 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-10';
                        if ($role->name === 'SuperAdmin') {
                            $badgeClass = 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-20';
                        } elseif ($role->name === 'Admin') {
                            $badgeClass = 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20';
                        } elseif ($role->name === 'User') {
                            $badgeClass = 'bg-success bg-opacity-10 text-success border border-success border-opacity-20';
                        }
                        $html .= '<span class="badge ' . $badgeClass . ' rounded-pill py-1.5 px-2.5 small fw-semibold">' . e($role->name) . '</span>';
                    }
                    if ($user->roles->isEmpty()) {
                        $html .= '<span class="text-muted small italic">No roles assigned</span>';
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->addColumn('joined_date', function ($user) {
                    return '<span class="text-secondary small">' . $user->created_at->format('M d, Y') . '</span>';
                })
                ->addColumn('actions', function ($user) {
                    $html = '<div class="d-flex justify-content-end gap-2">';
                    if (auth()->user()->can('user-edit')) {
                        $html .= '<a href="' . route('admin.users.edit', $user->id) . '" class="btn btn-sm btn-light border py-1.5 px-2.5 rounded-2" title="Edit User"><i class="bi bi-pencil-square text-primary"></i> Edit</a>';
                    }
                    if (auth()->user()->can('user-delete')) {
                        if (auth()->id() === $user->id) {
                            $html .= '<button class="btn btn-sm btn-light border py-1.5 px-2.5 rounded-2" disabled title="You cannot delete yourself"><i class="bi bi-trash-fill text-muted"></i></button>';
                        } elseif ($user->email === 'superadmin@example.com') {
                            $html .= '<button class="btn btn-sm btn-light border py-1.5 px-2.5 rounded-2" disabled title="Cannot delete Super Admin account"><i class="bi bi-trash-fill text-muted"></i></button>';
                        } else {
                            $html .= '<form action="' . route('admin.users.destroy', $user->id) . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete user ' . e($user->name) . '?\')">';
                            $html .= csrf_field() . method_field('DELETE');
                            $html .= '<button type="submit" class="btn btn-sm btn-light border py-1.5 px-2.5 rounded-2" title="Delete User"><i class="bi bi-trash text-danger"></i></button>';
                            $html .= '</form>';
                        }
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->filterColumn('user_details', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")
                          ->orWhere('email', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('user_details', function ($query, $order) {
                    $query->orderBy('name', $order);
                })
                ->rawColumns(['user_details', 'assigned_roles', 'joined_date', 'actions'])
                ->make(true);
        }

        $users = collect(); // Initial empty collection for blade compile safety

        return view('admin.users.index', compact('users', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('user-create');

        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('user-create');

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,name']
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        } else {
            $user->assignRole('User'); // default role
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User "' . $user->name . '" created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        Gate::authorize('user-edit');

        $roles = Role::all();
        $userRoles = $user->roles->pluck('name')->toArray();
        return view('admin.users.edit', compact('user', 'roles', 'userRoles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        Gate::authorize('user-edit');

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,name']
        ]);

        // Prevent modifying the Super Admin seed email to avoid breaking the quick-login demo accounts
        if ($user->email === 'superadmin@example.com' && $request->email !== 'superadmin@example.com') {
            return redirect()->back()
                ->with('error', 'Cannot change the email of the core Super Admin account.');
        }

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        // Keep SuperAdmin role on the seed SuperAdmin account to prevent locking out
        if ($user->email === 'superadmin@example.com') {
            $roles = $request->roles ?? [];
            if (!in_array('SuperAdmin', $roles)) {
                $roles[] = 'SuperAdmin';
            }
            $user->syncRoles($roles);
        } else {
            $user->syncRoles($request->roles ?? []);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User "' . $user->name . '" updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        Gate::authorize('user-delete');

        // Don't let users delete themselves
        if (auth()->id() === $user->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        // Don't delete seed SuperAdmin account
        if ($user->email === 'superadmin@example.com') {
            return redirect()->route('admin.users.index')
                ->with('error', 'Cannot delete the system Super Admin account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
