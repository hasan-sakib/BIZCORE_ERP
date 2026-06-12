<?php

declare(strict_types=1);

return [
    'google' => [
        'client_id'     => $_ENV['GOOGLE_CLIENT_ID']     ?? '',
        'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
        'redirect_uri'  => rtrim($_ENV['APP_URL'] ?? '', '/') . '/auth/google/callback',
    ],
];
