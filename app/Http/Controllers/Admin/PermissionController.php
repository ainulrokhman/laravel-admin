<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use App\Contracts\PermissionServiceInterface;
use App\Http\Requests\Permission\StorePermissionRequest;
use App\Http\Requests\Permission\UpdatePermissionRequest;
use App\DataTables\PermissionDataTable;
use App\Http\Controllers\Admin\Concerns\HandlesServiceExceptions;
use Inertia\Inertia;

class PermissionController extends Controller
{
    use HandlesServiceExceptions;

    protected PermissionServiceInterface $permissionService;

    /**
     * PermissionController constructor.
     */
    public function __construct(PermissionServiceInterface $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PermissionDataTable $permissionDataTable)
    {
        Gate::authorize('permission-list');

        // Inertia visits are XHR too (the client sends X-Requested-With),
        // so the table feed must only answer plain AJAX requests; otherwise
        // SPA navigation to this page would receive table JSON.
        if ($request->ajax() && ! $request->header('X-Inertia')) {
            return $permissionDataTable->make();
        }

        return Inertia::render('Permissions/Index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePermissionRequest $request)
    {
        Gate::authorize('permission-create');

        $permission = $this->permissionService->createPermission($request->validated());

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission "' . $permission->name . '" created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permission $permission)
    {
        Gate::authorize('permission-edit');

        if ($this->permissionService->isPermissionProtected($permission)) {
            return redirect()->route('admin.permissions.index')
                ->with('error', 'The core system permission "' . $permission->name . '" is protected and cannot be edited.');
        }

        return Inertia::render('Permissions/Edit', [
            'permission' => $permission->only(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        Gate::authorize('permission-edit');

        if ($errorRedirect = $this->runServiceAction(fn () => $this->permissionService->updatePermission($permission, $request->validated()), 'admin.permissions.index')) {
            return $errorRedirect;
        }

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        Gate::authorize('permission-delete');

        if ($errorRedirect = $this->runServiceAction(fn () => $this->permissionService->deletePermission($permission), 'admin.permissions.index')) {
            return $errorRedirect;
        }

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }
}
