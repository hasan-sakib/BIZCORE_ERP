<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use League\OAuth2\Client\Provider\Google;
use Illuminate\View\View;

class OAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        $provider = $this->makeProvider();
        $authUrl  = $provider->getAuthorizationUrl(['scope' => ['email', 'profile']]);

        session(['oauth_state' => $provider->getState()]);

        return redirect()->away($authUrl);
    }

    public function callback(Request $request): RedirectResponse
    {
        $state = (string) $request->query('state', '');
        $code  = (string) $request->query('code', '');
        $error = (string) $request->query('error', '');

        if ($error !== '') {
            return redirect('/login')->with('error', 'Google sign-in was cancelled or failed. Please try again.');
        }

        $storedState = session()->pull('oauth_state', '');
        if ($state === '' || $storedState === '' || !hash_equals($storedState, $state)) {
            return redirect('/login')->with('error', 'Invalid OAuth state. Please try again.');
        }

        try {
            $provider    = $this->makeProvider();
            $accessToken = $provider->getAccessToken('authorization_code', ['code' => $code]);
            $googleUser  = $provider->getResourceOwner($accessToken);
        } catch (\Throwable) {
            return redirect('/login')->with('error', 'Failed to authenticate with Google. Please try again.');
        }

        $googleId = (string) $googleUser->getId();
        $email    = (string) ($googleUser->getEmail() ?? '');
        $name     = (string) ($googleUser->getName() ?? '');
        $avatar   = (string) ($googleUser->getAvatar() ?? '');

        // Find existing user by OAuth ID, then fall back to email
        $user = User::where('oauth_provider', 'google')
            ->where('oauth_provider_id', $googleId)
            ->first();

        if ($user === null && $email !== '') {
            $user = User::where('email', $email)->first();
            if ($user !== null) {
                $user->update(['oauth_provider' => 'google', 'oauth_provider_id' => $googleId]);
            }
        }

        if ($user !== null) {
            if (!$user->isActive()) {
                return redirect('/login')->with('error', 'Your account is inactive or locked. Please contact an administrator.');
            }

            // Sync Google avatar only if user has no local upload
            $hasLocalAvatar = $user->avatar !== null
                && !str_starts_with($user->avatar, 'http://')
                && !str_starts_with($user->avatar, 'https://');

            if ($avatar !== '' && !$hasLocalAvatar && $avatar !== $user->avatar) {
                $user->update(['avatar' => $avatar]);
                Cache::forget("auth_user_{$user->id}");
            }

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        }

        // Store pending OAuth data for profile completion
        session(['oauth_pending' => [
            'provider'    => 'google',
            'provider_id' => $googleId,
            'name'        => $name,
            'email'       => $email,
            'avatar'      => $avatar,
        ]]);

        return redirect('/auth/complete-profile');
    }

    public function showComplete(): View|RedirectResponse
    {
        $pending = session('oauth_pending');

        if (empty($pending)) {
            return redirect('/login');
        }

        if (Auth::check()) {
            return redirect('/dashboard');
        }

        return view('auth.oauth-complete', [
            'oauthData' => $pending,
            'roles'     => Role::where('slug', '!=', 'super_admin')->orderBy('name')->get(),
            'branches'  => Branch::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function complete(Request $request): RedirectResponse
    {
        $pending = session('oauth_pending');

        if (empty($pending)) {
            return redirect('/login');
        }

        $data = $request->validate([
            'role_id'   => ['required', 'integer', 'exists:roles,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        abort_if(
            Role::where('id', $data['role_id'])->where('slug', 'super_admin')->exists(),
            403, 'Cannot register as super admin.'
        );

        User::create([
            'branch_id'        => $data['branch_id'],
            'role_id'          => $data['role_id'],
            'name'             => $pending['name'],
            'email'            => $pending['email'],
            'password'         => bcrypt(bin2hex(random_bytes(16))),
            'avatar'           => $pending['avatar'] ?: null,
            'status'           => UserStatus::Inactive,
            'oauth_provider'   => $pending['provider'],
            'oauth_provider_id' => $pending['provider_id'],
        ]);

        session()->forget('oauth_pending');

        return redirect('/login')->with(
            'success',
            'Your account has been created and is pending admin approval. You\'ll be notified when it\'s activated.'
        );
    }

    private function makeProvider(): Google
    {
        return new Google([
            'clientId'     => config('services.google.client_id'),
            'clientSecret' => config('services.google.client_secret'),
            'redirectUri'  => config('services.google.redirect'),
        ]);
    }
}
