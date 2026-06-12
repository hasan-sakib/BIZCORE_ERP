<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

/**
 * AuthController
 *
 * Thin alias for LoginController so that routes can reference
 * App\Controllers\Auth\AuthController for login / logout actions.
 */
final class AuthController extends LoginController {}
