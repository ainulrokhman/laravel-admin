<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;

// Welcome Page
Route::get('/', function () {
    return view('welcome');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::any('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Admin Panel Group
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard Route
    Route::get('/', function () {
        // We will load counts dynamically
        $usersCount = \App\Models\User::count();
        $rolesCount = \Spatie\Permission\Models\Role::count();
        $permissionsCount = \Spatie\Permission\Models\Permission::count();
        
        return view('admin.dashboard', compact('usersCount', 'rolesCount', 'permissionsCount'));
    })->name('dashboard')->middleware('permission:view-dashboard');

    // RBAC CRUD Resource Routes
    Route::resource('users', UserController::class)->middleware('permission:user-list');
    Route::resource('roles', RoleController::class)->middleware('permission:role-list');
    Route::resource('permissions', PermissionController::class)->middleware('permission:permission-list');
});

