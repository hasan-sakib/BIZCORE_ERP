<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Cache;
use App\Entities\UserStatus;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\UserRepository;

class OAuthController
{
    public function __construct(
        private readonly array $servicesConfig,
        private readonly UserRepository $userRepository,
        private readonly Cache $cache,
    ) {}

    public function redirect(): Response
    {
        $session  = session();
        $provider = $this->makeProvider();
        $options  = ['scope' => ['email', 'profile']];

        $authUrl = $provider->getAuthorizationUrl($options);
        $session->set('oauth_state', $provider->getState());

        return Response::redirect($authUrl);
    }

    public function callback(Request $request): Response
    {
        $session = session();
        $state   = $request->input('state', '');
        $code    = $request->input('code', '');
        $error   = $request->input('error', '');

        if ($error !== '') {
            $session->flash('error', 'Google sign-in was cancelled or failed. Please try again.');
            return Response::redirect('/login');
        }

        $storedState = $session->get('oauth_state', '');
        if ($state === '' || $storedState === '' || !hash_equals($storedState, $state)) {
            $session->flash('error', 'Invalid OAuth state. Please try again.');
            return Response::redirect('/login');
        }

        $session->forget('oauth_state');

        try {
            $provider    = $this->makeProvider();
            $accessToken = $provider->getAccessToken('authorization_code', ['code' => $code]);
            $googleUser  = $provider->getResourceOwner($accessToken);
        } catch (\Throwable) {
            session()->flash('error', 'Failed to authenticate with Google. Please try again.');
            return Response::redirect('/login');
        }

        $googleId    = (string) $googleUser->getId();
        $email       = (string) ($googleUser->getEmail() ?? '');
        $name        = (string) ($googleUser->getName() ?? '');
        $avatar      = (string) ($googleUser->getAvatar() ?? '');

        $user = $this->userRepository->findByOAuthId('google', $googleId);

        if ($user === null && $email !== '') {
            $user = $this->userRepository->findByEmail($email);
            if ($user !== null) {
                $this->userRepository->linkOAuth($user->id, 'google', $googleId);
            }
        }

        if ($user !== null) {
            if (!$user->isActive()) {
                session()->flash('error', 'Your account is inactive or locked. Please contact an administrator.');
                return Response::redirect('/login');
            }

            // Sync Google profile picture, but only when the user has no avatar
            // or their avatar is already an external URL (i.e. a previous Google picture).
            // Local uploads (no http prefix) are never overwritten.
            $hasLocalAvatar = $user->avatar !== null
                && !str_starts_with($user->avatar, 'https://')
                && !str_starts_with($user->avatar, 'http://');

            if ($avatar !== '' && !$hasLocalAvatar && $avatar !== $user->avatar) {
                $this->userRepository->update($user->id, ['avatar' => $avatar]);
                $this->cache->forget("auth_user_{$user->id}");
            }

            $this->startSession($user->id);
            return Response::redirect('/dashboard');
        }

        $session->set('oauth_pending', [
            'provider'    => 'google',
            'provider_id' => $googleId,
            'name'        => $name,
            'email'       => $email,
            'avatar'      => $avatar,
        ]);

        return Response::redirect('/auth/complete-profile');
    }

    public function showComplete(): Response
    {
        $session = session();
        $pending = $session->get('oauth_pending');

        if (empty($pending)) {
            return Response::redirect('/login');
        }

        if ($session->userId() !== null) {
            return Response::redirect('/dashboard');
        }

        $pdo      = app(\PDO::class);
        $roles    = $pdo->query("SELECT id, name, slug FROM roles WHERE slug != 'super_admin' ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $branches = $pdo->query("SELECT id, name FROM branches WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);

        return Response::make($this->renderView('auth/oauth-complete', [
            'oauthData' => $pending,
            'roles'     => $roles,
            'branches'  => $branches,
            'errors'    => $session->getFlash('oauth_errors') ?? [],
        ]));
    }

    public function complete(Request $request): Response
    {
        $session = session();
        $pending = $session->get('oauth_pending');

        if (empty($pending)) {
            return Response::redirect('/login');
        }

        if (!$session->validateCsrf($request->csrfToken())) {
            $session->flash('error', 'Invalid security token. Please try again.');
            return Response::redirect('/auth/complete-profile');
        }
        $roleId   = (int) $request->input('role_id', 0);
        $branchId = (int) $request->input('branch_id', 0);
        $errors   = [];
        $pdo      = app(\PDO::class);

        if ($roleId === 0) {
            $errors['role_id'] = 'Please select a role.';
        } else {
            $role = $pdo->prepare("SELECT id FROM roles WHERE id = :id AND slug != 'super_admin' LIMIT 1");
            $role->execute([':id' => $roleId]);
            if (!$role->fetch()) {
                $errors['role_id'] = 'Selected role is invalid.';
            }
        }

        if ($branchId === 0) {
            $errors['branch_id'] = 'Please select a branch.';
        } else {
            $branch = $pdo->prepare('SELECT id FROM branches WHERE id = :id AND deleted_at IS NULL LIMIT 1');
            $branch->execute([':id' => $branchId]);
            if (!$branch->fetch()) {
                $errors['branch_id'] = 'Selected branch is invalid.';
            }
        }

        if ($errors !== []) {
            session()->flash('oauth_errors', $errors);
            return Response::redirect('/auth/complete-profile');
        }

        $this->userRepository->create([
            'branch_id'       => $branchId,
            'role_id'         => $roleId,
            'name'            => $pending['name'],
            'email'           => $pending['email'],
            'password'        => password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT, ['cost' => 12]),
            'avatar'          => $pending['avatar'] !== '' ? $pending['avatar'] : null,
            'status'          => UserStatus::Inactive->value,
            'oauth_provider'  => $pending['provider'],
            'oauth_provider_id' => $pending['provider_id'],
        ]);

        $session->forget('oauth_pending');

        $session->flash('success', 'Your account has been created and is pending admin approval. You\'ll be notified when it\'s activated.');
        return Response::redirect('/login');
    }

    private function makeProvider(): \League\OAuth2\Client\Provider\Google
    {
        $cfg = $this->servicesConfig['google'] ?? [];
        return new \League\OAuth2\Client\Provider\Google([
            'clientId'     => $cfg['client_id']     ?? '',
            'clientSecret' => $cfg['client_secret'] ?? '',
            'redirectUri'  => $cfg['redirect_uri']  ?? '',
        ]);
    }

    private function startSession(int $userId): void
    {
        $session = session();
        $session->regenerate(true);
        $session->setUserId($userId);
        $session->set('auth_at', time());
    }

    private function renderView(string $view, array $data = []): string
    {
        $viewPath = dirname(__DIR__, 3) . '/resources/views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View not found: {$viewPath}");
        }

        $session     = session();
        $currentUser = null;
        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;
        return (string) ob_get_clean();
    }
}
