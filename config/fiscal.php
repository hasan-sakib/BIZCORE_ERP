<?php

return [
    /*
     * Month the fiscal year starts (1 = January, 7 = July for BD standard).
     */
    'year_start_month' => (int) env('FISCAL_YEAR_START_MONTH', 7),

    'currency' => [
        'code'   => env('DEFAULT_CURRENCY', 'BDT'),
        'symbol' => env('CURRENCY_SYMBOL', '৳'),
    ],
];
