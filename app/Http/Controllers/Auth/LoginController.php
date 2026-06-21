<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Exceptions\AccountLockedException;
use App\Exceptions\AuthException;
use App\Exceptions\InvalidCredentialsException;
use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended('/dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'min:8'],
        ]);

        try {
            $user = $this->authService->attemptLogin(
                email:    $credentials['email'],
                password: $credentials['password'],
                ip:       $request->ip(),
                ua:       $request->userAgent() ?? '',
            );
        } catch (AccountLockedException $e) {
            return back()->withErrors(['email' => $e->getMessage()])->withInput($request->only('email'));
        } catch (InvalidCredentialsException|AuthException $e) {
            return back()->withErrors(['email' => $e->getMessage()])->withInput($request->only('email'));
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
