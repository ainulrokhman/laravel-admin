<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use App\Contracts\UserServiceInterface;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\DataTables\UserDataTable;
use App\Http\Controllers\Admin\Concerns\HandlesServiceExceptions;
use App\Support\SystemAccess;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use HandlesServiceExceptions;

    protected UserServiceInterface $userService;

    /**
     * UserController constructor.
     */
    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, UserDataTable $userDataTable)
    {
        Gate::authorize('user-list');

        if ($request->ajax()) {
            return $userDataTable->make();
        }

        return Inertia::render('Users/Index', [
            'search' => $request->get('search'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('user-create');

        return Inertia::render('Users/Create', [
            'roles' => Role::all(['id', 'name']),
            // The plain User role starts ticked, as in the former form.
            'defaultRole' => SystemAccess::USER_ROLE,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        Gate::authorize('user-create');

        $user = $this->userService->createUser($request->validated());

        return redirect()->route('admin.users.index')
            ->with('success', 'User "' . $user->name . '" created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): Response
    {
        Gate::authorize('user-edit');

        return Inertia::render('Users/Edit', [
            'user' => $user->only(['id', 'name', 'email']),
            'roles' => Role::all(['id', 'name']),
            'userRoles' => $user->roles->pluck('name')->toArray(),
            // The Super Admin seed account locks its email and its role.
            'protectedAccount' => $user->email === SystemAccess::SUPER_ADMIN_EMAIL,
            'protectedRole' => SystemAccess::SUPER_ADMIN_ROLE,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        Gate::authorize('user-edit');

        if ($errorRedirect = $this->runServiceAction(fn () => $this->userService->updateUser($user, $request->validated()), 'admin.users.index')) {
            return $errorRedirect;
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

        if ($errorRedirect = $this->runServiceAction(fn () => $this->userService->deleteUser($user), 'admin.users.index')) {
            return $errorRedirect;
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
