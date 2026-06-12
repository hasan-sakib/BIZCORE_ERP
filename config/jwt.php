<?php

declare(strict_types=1);

/**
 * BizCore ERP - JWT (JSON Web Token) Configuration
 *
 * Used for stateless API authentication (mobile apps, SPA, third-party integrations).
 * Tokens are signed with HMAC-SHA256 (HS256) by default; RS256 is supported
 * when asymmetric keys are provided via environment variables.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Signing Secret (HS256)
    |--------------------------------------------------------------------------
    |
    | Must be at least 64 characters for HS256. Generate with:
    |   php -r "echo bin2hex(random_bytes(32));"
    |
    | If ALGO is RS256/RS512, this value is ignored in favour of the
    | private/public key pair below.
    |
    */

    'secret' => $_ENV['JWT_SECRET'] ?? '',

    /*
    |--------------------------------------------------------------------------
    | Signing Algorithm
    |--------------------------------------------------------------------------
    |
    | Supported: HS256, HS384, HS512, RS256, RS384, RS512, ES256, ES384, ES512
    | Recommended: HS256 (symmetric), RS256 (asymmetric / multi-service)
    |
    */

    'algo' => $_ENV['JWT_ALGO'] ?? 'HS256',

    /*
    |--------------------------------------------------------------------------
    | Asymmetric Keys (RS256 / ES256)
    |--------------------------------------------------------------------------
    |
    | Required only when algo is RS* or ES*.
    | Paths should point to PEM-encoded key files.
    |
    */

    'keys' => [
        'public'     => $_ENV['JWT_PUBLIC_KEY']  ?? null,   // path or PEM string
        'private'    => $_ENV['JWT_PRIVATE_KEY'] ?? null,
        'passphrase' => $_ENV['JWT_PASSPHRASE']  ?? null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Lifetimes
    |--------------------------------------------------------------------------
    |
    | All values in MINUTES.
    | refresh_ttl should be much larger than ttl.
    |
    */

    'ttl'         => (int) ($_ENV['JWT_TTL'] ?? 60),            // access token: 1 hour
    'refresh_ttl' => (int) ($_ENV['JWT_REFRESH_TTL'] ?? 20160), // refresh token: 14 days

    /*
    |--------------------------------------------------------------------------
    | Leeway / Clock Skew Tolerance
    |--------------------------------------------------------------------------
    |
    | Number of seconds of tolerance for clock differences between servers.
    | Keep small to maintain security (recommended: 0–30 seconds).
    |
    */

    'leeway' => (int) ($_ENV['JWT_LEEWAY'] ?? 0),

    /*
    |--------------------------------------------------------------------------
    | Required Claims
    |--------------------------------------------------------------------------
    |
    | These claims must be present in every JWT or validation fails.
    |
    */

    'required_claims' => [
        'iss',  // issuer
        'iat',  // issued at
        'exp',  // expiry
        'nbf',  // not before
        'sub',  // subject (user ID)
        'jti',  // JWT ID (unique token identifier)
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Claims Added to Every Token
    |--------------------------------------------------------------------------
    |
    | These claims are merged into every issued token payload.
    | Dynamic values (user role, branch, etc.) are added by the auth service.
    |
    */

    'persistent_claims' => [
        // 'user_id', 'role', 'branch_id'  — added dynamically in AuthService
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Issuer (iss claim)
    |--------------------------------------------------------------------------
    */

    'issuer' => $_ENV['APP_URL'] ?? 'http://localhost:8080',

    /*
    |--------------------------------------------------------------------------
    | Blacklisting
    |--------------------------------------------------------------------------
    |
    | When enabled, invalidated tokens are stored in Redis until they expire.
    | This allows immediate logout / token revocation at the cost of a Redis
    | lookup on every authenticated request.
    |
    */

    'blacklist' => [
        'enabled'       => filter_var($_ENV['JWT_BLACKLIST'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'storage'       => 'redis',                 // only redis is supported
        'prefix'        => 'bizcore:jwt:blacklist:',
        'grace_period'  => 0,                       // seconds; 0 = immediate revocation
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Refresh Policy
    |--------------------------------------------------------------------------
    */

    'refresh' => [
        'allow_from_ttl'    => true,    // allow refresh even after access token has expired
        'single_use'        => true,    // refresh token is invalidated after use (rotation)
        'rotate_jti'        => true,    // generate new JTI on each refresh
        'max_refresh_count' => 0,       // 0 = unlimited within refresh_ttl
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Request Configuration
    |--------------------------------------------------------------------------
    */

    'request' => [
        // Header name carrying the bearer token
        'header'    => 'Authorization',
        'prefix'    => 'Bearer',

        // Cookie name (alternative to header — for SPA with httpOnly cookies)
        'cookie'    => 'bizcore_access_token',
        'use_cookie'=> filter_var($_ENV['JWT_USE_COOKIE'] ?? false, FILTER_VALIDATE_BOOLEAN),

        // Query parameter (only enabled in non-production for debugging)
        'query_key' => 'token',
        'allow_query_param' => filter_var($_ENV['JWT_ALLOW_QUERY_PARAM'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Payload Encryption
    |--------------------------------------------------------------------------
    |
    | When enabled, the JWT payload is AES-256-CBC encrypted before signing.
    | This hides claim values from clients but adds CPU overhead.
    |
    */

    'encrypt_payload' => filter_var($_ENV['JWT_ENCRYPT_PAYLOAD'] ?? false, FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Multi-Branch Token Claims
    |--------------------------------------------------------------------------
    |
    | Additional custom claims added to the JWT for ERP context.
    | These are validated on every authenticated API request.
    |
    */

    'erp_claims' => [
        'user_id'       => 'uid',
        'branch_id'     => 'bid',
        'role'          => 'rol',
        'permissions'   => 'pms',
        'session_id'    => 'sid',
        'device_id'     => 'did',
    ],

    /*
    |--------------------------------------------------------------------------
    | Service-to-Service Tokens (Internal API)
    |--------------------------------------------------------------------------
    |
    | Long-lived tokens for background workers and inter-service calls.
    | These bypass user-scoped claims but must present a valid service key.
    |
    */

    'service_tokens' => [
        'enabled'    => false,
        'ttl'        => 525600,     // 365 days in minutes
        'secret'     => $_ENV['JWT_SERVICE_SECRET'] ?? '',
        'allowed_ips'=> [],
    ],

];
