<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the overview dashboard.
     */
    public function index(): Response
    {
        Gate::authorize('view-dashboard');

        // Fetch the 5 most recent registrations, shaped for the Vue page:
        // the human-readable timestamp is resolved here so the client only
        // renders strings.
        $recentUsers = User::orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'created_at_human' => $user->created_at->diffForHumans(),
            ]);

        return Inertia::render('Dashboard', [
            'usersCount' => User::count(),
            'rolesCount' => Role::count(),
            'permissionsCount' => Permission::count(),
            'recentUsers' => $recentUsers,
        ]);
    }
}
