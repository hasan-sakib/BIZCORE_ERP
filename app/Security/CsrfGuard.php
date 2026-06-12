<?php

declare(strict_types=1);

namespace App\Security;

use App\Core\Session;

class CsrfGuard
{
    public function __construct(
        private readonly Session $session,
        private readonly int     $lifetime = 3600,
    ) {}

    public function token(): string
    {
        return $this->session->csrfToken();
    }

    public function validate(string $token): bool
    {
        return $this->session->validateCsrf($token);
    }

    public function field(): string
    {
        return '<input type="hidden" name="_token" value="' . $this->token() . '">';
    }
}
