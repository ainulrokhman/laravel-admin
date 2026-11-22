<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\ProfileServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    protected ProfileServiceInterface $profileService;

    /**
     * ProfileController constructor.
     */
    public function __construct(ProfileServiceInterface $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Show the profile edit form.
     */
    public function edit(): Response
    {
        /** @var User $user */
        $user = auth()->user();

        return Inertia::render('Profile/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar ? asset('storage/'.$user->avatar) : null,
            ],
            // The protected seed account's email is locked, matching the
            // validation rules that omit the email field for it.
            'emailLocked' => $this->profileService->isProtectedSeedAccount($user),
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request)
    {
        /** @var User $user */
        $user = auth()->user();

        $this->profileService->updateProfile($user, $request->validated());

        return redirect()->route('admin.profile.edit')
            ->with('success', 'Profile updated successfully.');
    }
}
