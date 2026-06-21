<?php

return [
    'secret'             => env('JWT_SECRET'),
    'algo'               => env('JWT_ALGO', 'HS256'),
    'ttl'                => (int) env('JWT_TTL', 60),            // minutes
    'refresh_ttl'        => (int) env('JWT_REFRESH_TTL', 20160), // minutes (14 days)
    'blacklist'          => (bool) env('JWT_BLACKLIST', true),
    'blacklist_grace'    => (int) env('JWT_BLACKLIST_GRACE_PERIOD', 30), // seconds
    'redis_blacklist_key' => 'jwt_blacklist',
];
