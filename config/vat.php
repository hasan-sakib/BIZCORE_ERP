<?php

return [
    'enabled'             => env('VAT_ENABLED', true),
    'default_rate'        => (float) env('DEFAULT_VAT_RATE', 15),
    'registration_number' => env('VAT_REGISTRATION_NUMBER', ''),
];
