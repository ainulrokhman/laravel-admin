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

class PermissionController extends Controller
{
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

        if ($request->ajax()) {
            return $permissionDataTable->make();
        }

        $permissions = collect();
        return view('admin.permissions.index', compact('permissions'));
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

        return view('admin.permissions.edit', compact('permission'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        Gate::authorize('permission-edit');

        try {
            $this->permissionService->updatePermission($permission, $request->validated());
        } catch (\LogicException $e) {
            return redirect()->route('admin.permissions.index')
                ->with('error', $e->getMessage());
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

        try {
            $this->permissionService->deletePermission($permission);
        } catch (\LogicException $e) {
            return redirect()->route('admin.permissions.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }
}
