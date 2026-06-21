<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function show(): View
    {
        return view('profile.show', ['user' => auth()->user()]);
    }

    public function edit(): View
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,' . auth()->id()],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        auth()->user()->update($data);

        Cache::forget('auth_user_' . auth()->id());

        return redirect('/profile')->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        try {
            $this->authService->changePassword(
                userId:          auth()->id(),
                currentPassword: $request->input('current_password'),
                newPassword:     $request->input('new_password'),
            );
        } catch (\App\Exceptions\InvalidCredentialsException $e) {
            return back()->withErrors(['current_password' => $e->getMessage()]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect('/profile')->with('success', 'Password changed successfully.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = auth()->user();

        // Delete old avatar if it was a local upload
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars/' . $user->id, 'public');

        $user->update(['avatar' => $path]);

        Cache::forget('auth_user_' . $user->id);

        return redirect('/profile')->with('success', 'Avatar updated successfully.');
    }
}
