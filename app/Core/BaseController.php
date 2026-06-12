<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\ForbiddenException;
use App\Foundation\Application;
use App\Http\Request;
use App\Http\Response;

abstract class BaseController
{
    protected function view(string $template, array $data = []): string
    {
        $app      = Application::getInstance();
        $viewPath = $app->resourcePath("views/{$template}.php");

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View [{$template}] not found at {$viewPath}");
        }

        $auth        = $app->get(Auth::class);
        $session     = $app->get(Session::class);
        $currentUser = $auth->user();

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;
        return (string) ob_get_clean();
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }

    protected function back(): Response
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return $this->redirect($referer);
    }

    protected function authorize(string $permission): void
    {
        $app  = Application::getInstance();
        $auth = $app->get(Auth::class);
        $user = $auth->user();

        if ($user === null) {
            throw new ForbiddenException("Authentication required.");
        }

        $perms = $app->get(Permissions::class);
        if ($perms->cannot($user, $permission)) {
            throw new ForbiddenException("You do not have permission to [{$permission}].");
        }
    }

    protected function currentUser(): ?\App\Entities\User
    {
        return Application::getInstance()->get(Auth::class)->user();
    }

    protected function success(string $message): void
    {
        Application::getInstance()->get(Session::class)->flash('success', $message);
    }

    protected function error(string $message): void
    {
        Application::getInstance()->get(Session::class)->flash('error', $message);
    }

    protected function withErrors(array $errors): void
    {
        Application::getInstance()->get(Session::class)->flash('errors', $errors);
    }

    protected function withInput(Request $request): void
    {
        Application::getInstance()->get(Session::class)->flashInput($request->all());
    }

    protected function render(string $template, array $data = []): Response
    {
        return Response::make($this->view($template, $data));
    }
}
