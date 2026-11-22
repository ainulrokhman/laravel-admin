<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin(): Response|\Illuminate\Http\RedirectResponse
    {
        if (Auth::check()) {
            return redirect('/admin');
        }

        return Inertia::render('Auth/Login', [
            // Seeded demo accounts shown in the "Quick Testing Accounts"
            // panel; clicking one fills and submits the login form.
            'demoAccounts' => [
                ['label' => 'Super Admin', 'note' => 'Full Access', 'color' => 'primary', 'email' => 'superadmin@example.com', 'password' => 'password'],
                ['label' => 'Admin', 'note' => 'Users & Roles', 'color' => 'success', 'email' => 'admin@example.com', 'password' => 'password'],
                ['label' => 'Regular User', 'note' => 'Dashboard Only', 'color' => 'info', 'email' => 'user@example.com', 'password' => 'password'],
            ],
        ]);
    }

    /**
     * Handle the login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->intended('/admin')
                ->with('success', 'Welcome back, ' . Auth::user()->name . '!');
        }

        throw ValidationException::withMessages([
            'email' => __('The provided credentials do not match our records.'),
        ]);
    }

    /**
     * Handle the logout request.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Logged out successfully.');
    }
}
