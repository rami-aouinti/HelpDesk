<?php

namespace App\\Http\\Controllers;

use Illuminate\\Contracts\\View\\View;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Hash;
use Illuminate\\Support\\Facades\\Storage;
use Illuminate\\Support\\Str;
use Illuminate\\Validation\\Rule;
use Illuminate\\Validation\\Rules\\Password;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $emailChanged = $user->email !== $data['email'];

        $user->name = $data['name'];
        $user->email = $data['email'];

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();

            return redirect()
                ->route('verification.notice')
                ->with('status', 'Your email address changed. Please verify your new address.');
        }

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Your profile details have been updated.');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $newPath = $request->file('profile_photo')->store('profile-photos', 'public');

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->forceFill(['profile_photo_path' => $newPath])->save();

        return back()->with('success', 'Your profile photo has been updated.');
    }

    public function destroyPhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->forceFill(['profile_photo_path' => null])->save();
        }

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Your profile photo has been removed.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Your password has been changed successfully.');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email_notifications_enabled' => ['required', 'boolean'],
        ]);

        $request->user()->forceFill([
            'email_notifications_enabled' => $request->boolean('email_notifications_enabled'),
        ])->save();

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Your notification preferences have been saved.');
    }
}
