<?php

return [
    'bkash' => [
        'app_key'      => env('BKASH_APP_KEY'),
        'app_secret'   => env('BKASH_APP_SECRET'),
        'username'     => env('BKASH_USERNAME'),
        'password'     => env('BKASH_PASSWORD'),
        'base_url'     => env('BKASH_BASE_URL', 'https://tokenized.sandbox.bka.sh/v1.2.0-beta'),
        'callback_url' => env('BKASH_CALLBACK_URL'),
    ],

    'nagad' => [
        'merchant_id'  => env('NAGAD_MERCHANT_ID'),
        'merchant_key' => env('NAGAD_MERCHANT_KEY'),
        'base_url'     => env('NAGAD_BASE_URL', 'http://sandbox.mynagad.com:10080/remote-payment-gateway-1.0'),
        'callback_url' => env('NAGAD_CALLBACK_URL'),
    ],
];
