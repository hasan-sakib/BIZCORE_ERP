<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Entities\UserStatus;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\UserRepository;

class RegisterController
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function showForm(): Response
    {
        if ($this->isAuthenticated()) {
            return Response::redirect('/dashboard');
        }

        $pdo      = app(\PDO::class);
        $roles    = $pdo->query("SELECT id, name, slug FROM roles WHERE slug != 'super_admin' ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $branches = $pdo->query("SELECT id, name FROM branches WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);

        return Response::make($this->renderView('auth/register', [
            'roles'    => $roles,
            'branches' => $branches,
            'errors'   => session()->getFlash('register_errors') ?? [],
        ]));
    }

    public function register(Request $request): Response
    {
        if (!session()->validateCsrf($request->csrfToken())) {
            session()->flash('error', 'Invalid security token. Please try again.');
            return Response::redirect('/register');
        }

        $name            = trim((string) $request->input('name', ''));
        $email           = trim((string) $request->input('email', ''));
        $password        = (string) $request->input('password', '');
        $confirmPassword = (string) $request->input('confirm_password', '');
        $roleId          = (int) $request->input('role_id', 0);
        $branchId        = (int) $request->input('branch_id', 0);

        session()->flashInput([
            'name'      => $name,
            'email'     => $email,
            'role_id'   => (string) $roleId,
            'branch_id' => (string) $branchId,
        ]);

        $errors = $this->validate($name, $email, $password, $confirmPassword, $roleId, $branchId);

        if ($errors !== []) {
            session()->flash('register_errors', $errors);
            return Response::redirect('/register');
        }

        $this->userRepository->create([
            'branch_id' => $branchId,
            'role_id'   => $roleId,
            'name'      => $name,
            'email'     => $email,
            'password'  => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'status'    => UserStatus::Inactive->value,
        ]);

        session()->flash('success', 'Your account has been created and is pending admin approval. You\'ll be notified when it\'s activated.');
        return Response::redirect('/login');
    }

    private function validate(
        string $name,
        string $email,
        string $password,
        string $confirmPassword,
        int $roleId,
        int $branchId,
    ): array {
        $errors = [];
        $pdo    = app(\PDO::class);

        if ($name === '') {
            $errors['name'] = 'Full name is required.';
        } elseif (mb_strlen($name) > 150) {
            $errors['name'] = 'Full name must not exceed 150 characters.';
        }

        if ($email === '') {
            $errors['email'] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        } else {
            $existing = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = :email AND deleted_at IS NULL LIMIT 1');
            $existing->execute([':email' => strtolower($email)]);
            if ($existing->fetch()) {
                $errors['email'] = 'This email address is already registered.';
            }
        }

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        } elseif (mb_strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        if ($confirmPassword === '') {
            $errors['confirm_password'] = 'Please confirm your password.';
        } elseif ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

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

        return $errors;
    }

    private function isAuthenticated(): bool
    {
        return session()->userId() !== null;
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
