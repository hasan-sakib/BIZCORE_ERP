<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Services\UserService;

final class ProfileController extends BaseController
{
    public function __construct(private readonly UserService $userService) {}

    public function show(): Response
    {
        $user = $this->currentUser();
        if ($user === null) {
            return $this->redirect('/login');
        }

        return $this->render('profile/show', ['user' => $user]);
    }

    public function edit(): Response
    {
        $user = $this->currentUser();
        if ($user === null) {
            return $this->redirect('/login');
        }

        return $this->render('profile/edit', [
            'user'   => $user,
            'errors' => \App\Foundation\Application::getInstance()
                ->get(\App\Core\Session::class)->getFlash('errors', []),
            'old'    => \App\Foundation\Application::getInstance()
                ->get(\App\Core\Session::class)->getFlash('old', []),
        ]);
    }

    public function update(Request $request): Response
    {
        $user = $this->currentUser();
        if ($user === null) {
            return $this->redirect('/login');
        }

        $data = $request->only(['name', 'email', 'phone']);

        $errors = [];
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required.';
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email address is required.';
        }

        if ($errors) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/profile/edit');
        }

        try {
            $this->userService->update($user->id, $data);
            $this->success('Profile updated successfully.');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }

        return $this->redirect('/profile');
    }

    public function updatePassword(Request $request): Response
    {
        $user = $this->currentUser();
        if ($user === null) {
            return $this->redirect('/login');
        }

        $current  = (string) $request->input('current_password', '');
        $new      = (string) $request->input('new_password', '');
        $confirm  = (string) $request->input('confirm_password', '');

        $errors = [];
        if (empty($current)) {
            $errors['current_password'] = 'Current password is required.';
        }
        if (strlen($new) < 8) {
            $errors['new_password'] = 'New password must be at least 8 characters.';
        }
        if ($new !== $confirm) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if ($errors) {
            $this->withErrors($errors);
            return $this->redirect('/profile/edit');
        }

        try {
            $repo = \App\Foundation\Application::getInstance()
                ->get(\App\Repositories\UserRepository::class);

            $hash = $repo->getPasswordHash($user->id);
            if ($hash === null || !password_verify($current, $hash)) {
                $this->error('Current password is incorrect.');
                return $this->redirect('/profile/edit');
            }

            $repo->update($user->id, ['password' => password_hash($new, PASSWORD_BCRYPT, ['cost' => 12])]);

            $this->success('Password changed successfully.');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }

        return $this->redirect('/profile');
    }

    public function updateAvatar(): Response
    {
        $user = $this->currentUser();
        if ($user === null) {
            return $this->redirect('/login');
        }

        $file = $_FILES['avatar'] ?? null;
        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $this->error('No valid file uploaded.');
            return $this->redirect('/profile/edit');
        }

        try {
            $this->userService->uploadAvatar($user->id, $file);
            $this->success('Avatar updated successfully.');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }

        return $this->redirect('/profile');
    }
}
