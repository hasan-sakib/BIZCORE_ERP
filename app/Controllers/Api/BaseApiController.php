<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Entities\User;
use App\Http\Request;

abstract class BaseApiController
{
    protected ?User $apiUser = null;

    protected function success(mixed $data, string $message = 'Success', int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function paginated(array $result, string $message = 'Success'): void
    {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success'    => true,
            'message'    => $message,
            'data'       => $result['data'],
            'pagination' => $result['pagination'] ?? null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function error(string $message, int $code = 400, array $errors = []): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        $response = ['success' => false, 'message' => $message];
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function currentUser(Request $request): ?User
    {
        return $request->getAuthUser();
    }

    protected function getBranchId(Request $request): int
    {
        $user = $this->currentUser($request);
        return $user ? $user->branchId : 0;
    }

    protected function getPaginationParams(Request $request): array
    {
        return [
            'page'     => max(1, (int)$request->query('page', 1)),
            'per_page' => min(100, max(10, (int)$request->query('per_page', 20))),
        ];
    }
}
