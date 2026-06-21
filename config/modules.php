<?php

return [
    /*
     * Which ERP modules are active. Set to false to hide from menu
     * and block the 'module:<name>' middleware.
     */
    'enabled' => [
        'hr'         => env('MODULE_HR',         true),
        'payroll'    => env('MODULE_PAYROLL',     true),
        'inventory'  => env('MODULE_INVENTORY',  true),
        'sales'      => env('MODULE_SALES',      true),
        'purchasing' => env('MODULE_PURCHASING', true),
        'crm'        => env('MODULE_CRM',        true),
        'accounting' => env('MODULE_ACCOUNTING', true),
        'expenses'   => env('MODULE_EXPENSES',   true),
        'reports'    => env('MODULE_REPORTS',    true),
    ],
];
