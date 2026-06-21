<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function showForgotForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $key = 'pw_reset:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['email' => 'Too many requests. Please wait a few minutes.']);
        }
        RateLimiter::hit($key, 900);

        // Always succeeds visually — no email enumeration
        $this->authService->forgotPassword($request->input('email'));

        return back()->with(
            'status',
            'If an account with that email exists, a password reset link has been sent.'
        );
    }

    public function showResetForm(Request $request, string $token): View|RedirectResponse
    {
        if (empty($token)) {
            return redirect('/password/forgot');
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token'                 => ['required'],
            'email'                 => ['required', 'email'],
            'password'              => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $success = $this->authService->resetPassword(
            token:        $data['token'],
            email:        $data['email'],
            password:     $data['password'],
            confirmation: $data['password_confirmation'],
        );

        if (!$success) {
            return back()->withErrors(['email' => 'This password reset link is invalid or has expired.']);
        }

        return redirect('/login')->with('success', 'Your password has been reset. You may now sign in.');
    }
}
