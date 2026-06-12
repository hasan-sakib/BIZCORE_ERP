<?php

declare(strict_types=1);

/**
 * BizCore ERP - VAT & Tax Configuration (Bangladesh)
 *
 * Compliant with the Value Added Tax and Supplementary Duty Act, 2012
 * (amended) and NBR (National Board of Revenue) VAT Online System (VOS).
 *
 * All Mushak (Musak) form numbers referenced follow NBR SRO 195-AIN/2019/51
 * and subsequent amendments.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | VAT Global Switch
    |--------------------------------------------------------------------------
    */

    'enabled'       => filter_var($_ENV['VAT_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'auto_calculate'=> true,    // automatically apply VAT on sales/purchase entries

    /*
    |--------------------------------------------------------------------------
    | Business Registration
    |--------------------------------------------------------------------------
    */

    'registration' => [
        'bin'           => $_ENV['VAT_REGISTRATION_NUMBER'] ?? '',   // Business Identification Number (9-digit BIN)
        'registered_name'       => $_ENV['VAT_REGISTERED_NAME'] ?? '',
        'registered_address'    => $_ENV['VAT_REGISTERED_ADDRESS'] ?? '',
        'registration_date'     => $_ENV['VAT_REGISTRATION_DATE'] ?? '',
        'vat_circle'            => $_ENV['VAT_CIRCLE'] ?? '',
        'vat_division'          => $_ENV['VAT_DIVISION'] ?? '',
        'vat_commissionerate'   => $_ENV['VAT_COMMISSIONERATE'] ?? '',
        'tin'                   => $_ENV['TIN_NUMBER'] ?? '',       // Taxpayer Identification Number
    ],

    /*
    |--------------------------------------------------------------------------
    | VAT Rates
    |--------------------------------------------------------------------------
    |
    | Standard VAT rate in Bangladesh is 15% (as per VAT Act 2012).
    | Reduced rates apply to specific goods and services.
    | Rate values are percentages (e.g., 15 = 15%).
    |
    */

    'default_rate' => (float) ($_ENV['DEFAULT_VAT_RATE'] ?? 15.0),

    'rates' => [

        // Standard Rate
        'standard' => [
            'code'        => 'S15',
            'rate'        => 15.0,
            'description' => 'Standard VAT Rate',
            'applies_to'  => ['goods', 'services'],
        ],

        // Reduced Rates
        'r5' => [
            'code'        => 'R5',
            'rate'        => 5.0,
            'description' => 'Reduced Rate 5% (e.g., basic food processing)',
            'applies_to'  => ['goods'],
        ],

        'r7_5' => [
            'code'        => 'R7.5',
            'rate'        => 7.5,
            'description' => 'Reduced Rate 7.5% (e.g., certain services)',
            'applies_to'  => ['services'],
        ],

        'r10' => [
            'code'        => 'R10',
            'rate'        => 10.0,
            'description' => 'Reduced Rate 10% (e.g., certain contractors)',
            'applies_to'  => ['services'],
        ],

        // Zero-rated
        'zero' => [
            'code'        => 'Z0',
            'rate'        => 0.0,
            'description' => 'Zero-rated (exports, special economic zones)',
            'applies_to'  => ['goods', 'services'],
        ],

        // Exempt
        'exempt' => [
            'code'        => 'EX',
            'rate'        => 0.0,
            'description' => 'VAT Exempt (per Schedule 1 & 2 of VAT Act 2012)',
            'applies_to'  => ['goods', 'services'],
            'is_exempt'   => true,
        ],

        // Truncated Value (TV) — for retail with packaged goods
        'tv' => [
            'code'        => 'TV',
            'rate'        => 15.0,
            'description' => 'Truncated Value (MRP-based VAT)',
            'applies_to'  => ['goods'],
            'uses_mrp'    => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Supplementary Duty (SD) Rates
    |--------------------------------------------------------------------------
    |
    | Supplementary Duty applies on top of VAT for luxury/demerit goods.
    |
    */

    'supplementary_duty' => [
        'enabled' => true,
        'rates'   => [
            'sd_10'  => ['rate' => 10.0,  'description' => 'SD 10%'],
            'sd_20'  => ['rate' => 20.0,  'description' => 'SD 20%'],
            'sd_45'  => ['rate' => 45.0,  'description' => 'SD 45%'],
            'sd_60'  => ['rate' => 60.0,  'description' => 'SD 60%'],
            'sd_100' => ['rate' => 100.0, 'description' => 'SD 100%'],
            'sd_250' => ['rate' => 250.0, 'description' => 'SD 250%'],
            'sd_350' => ['rate' => 350.0, 'description' => 'SD 350%'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax Period
    |--------------------------------------------------------------------------
    |
    | VAT return period in Bangladesh: monthly (calendar month).
    | Return deadline: 15th of the following month.
    |
    */

    'tax_period' => [
        'type'              => 'monthly',       // Supported: monthly, quarterly, yearly
        'start_day'         => 1,               // First day of each period
        'return_due_day'    => 15,              // Submission deadline (15th of next month)
        'payment_due_day'   => 15,              // Payment deadline
        'grace_period_days' => 0,               // Late submission tolerance
    ],

    /*
    |--------------------------------------------------------------------------
    | Mushak (Musak) Forms — NBR Official Forms
    |--------------------------------------------------------------------------
    |
    | Standard VAT forms required under Bangladesh VAT Act 2012.
    |
    */

    'mushak_forms' => [

        // Mushak-6.1 — VAT Challan / Tax Invoice
        '6_1' => [
            'name'        => 'Mushak-6.1',
            'title'       => 'Tax Invoice (VAT Challan)',
            'description' => 'Issued by VAT registered supplier for taxable supplies',
            'required_fields' => [
                'bin', 'invoice_number', 'invoice_date', 'buyer_name',
                'buyer_bin', 'buyer_address', 'line_items', 'vat_amount', 'total',
            ],
            'template'    => 'vat/mushak-6-1',
            'auto_generate' => true,
        ],

        // Mushak-6.2 — Debit/Credit Note
        '6_2' => [
            'name'        => 'Mushak-6.2',
            'title'       => 'Debit/Credit Note',
            'description' => 'Issued for price adjustments after original invoice',
            'template'    => 'vat/mushak-6-2',
            'auto_generate' => true,
        ],

        // Mushak-6.3 — Purchase Register (Input Tax)
        '6_3' => [
            'name'        => 'Mushak-6.3',
            'title'       => 'Purchase/Input Register',
            'description' => 'Monthly register of all purchases with input tax credit',
            'template'    => 'vat/mushak-6-3',
            'auto_generate' => true,
        ],

        // Mushak-6.4 — Sales Register (Output Tax)
        '6_4' => [
            'name'        => 'Mushak-6.4',
            'title'       => 'Sales/Output Register',
            'description' => 'Monthly register of all sales with output tax',
            'template'    => 'vat/mushak-6-4',
            'auto_generate' => true,
        ],

        // Mushak-6.5 — VAT Return
        '6_5' => [
            'name'        => 'Mushak-6.5',
            'title'       => 'VAT Return',
            'description' => 'Monthly VAT return submitted to NBR',
            'template'    => 'vat/mushak-6-5',
            'auto_generate' => true,
        ],

        // Mushak-6.6 — Transfer Challan (Stock Transfer)
        '6_6' => [
            'name'        => 'Mushak-6.6',
            'title'       => 'Transfer Challan',
            'description' => 'For stock transfer between branches/warehouses',
            'template'    => 'vat/mushak-6-6',
            'auto_generate' => true,
        ],

        // Mushak-6.7 — Destruction Certificate
        '6_7' => [
            'name'        => 'Mushak-6.7',
            'title'       => 'Destruction Certificate',
            'description' => 'For goods destroyed / written off',
            'template'    => 'vat/mushak-6-7',
            'auto_generate' => false,
        ],

        // Mushak-6.10 — VAT Challan for Services (Reverse Charge)
        '6_10' => [
            'name'        => 'Mushak-6.10',
            'title'       => 'VAT Challan (Reverse Charge / Services)',
            'description' => 'For imported services subject to reverse charge mechanism',
            'template'    => 'vat/mushak-6-10',
            'auto_generate' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Report Formats
    |--------------------------------------------------------------------------
    */

    'reports' => [

        'output_formats' => ['pdf', 'xlsx', 'csv'],
        'default_format' => 'pdf',

        'available' => [

            'vat_return_summary' => [
                'title'       => 'VAT Return Summary (Mushak-6.5)',
                'period'      => 'monthly',
                'format'      => ['pdf', 'xlsx'],
                'mushak_form' => '6_5',
            ],

            'purchase_register' => [
                'title'       => 'Purchase / Input Tax Register (Mushak-6.3)',
                'period'      => 'monthly',
                'format'      => ['pdf', 'xlsx', 'csv'],
                'mushak_form' => '6_3',
            ],

            'sales_register' => [
                'title'       => 'Sales / Output Tax Register (Mushak-6.4)',
                'period'      => 'monthly',
                'format'      => ['pdf', 'xlsx', 'csv'],
                'mushak_form' => '6_4',
            ],

            'input_tax_credit' => [
                'title'       => 'Input Tax Credit (ITC) Reconciliation',
                'period'      => 'monthly',
                'format'      => ['pdf', 'xlsx'],
                'mushak_form' => null,
            ],

            'vat_payable_summary' => [
                'title'       => 'VAT Payable Summary',
                'period'      => 'monthly',
                'format'      => ['pdf', 'xlsx'],
                'mushak_form' => null,
            ],

            'annual_vat_summary' => [
                'title'       => 'Annual VAT Summary',
                'period'      => 'yearly',
                'format'      => ['pdf', 'xlsx'],
                'mushak_form' => null,
            ],

            'zero_rated_sales' => [
                'title'       => 'Zero-Rated Sales Register',
                'period'      => 'monthly',
                'format'      => ['pdf', 'xlsx'],
                'mushak_form' => null,
            ],

            'exempt_sales' => [
                'title'       => 'Exempt Sales Register',
                'period'      => 'monthly',
                'format'      => ['pdf', 'xlsx'],
                'mushak_form' => null,
            ],

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Input Tax Credit (ITC) Rules
    |--------------------------------------------------------------------------
    */

    'itc' => [
        'enabled'               => true,
        'partial_itc_allowed'   => true,    // for mixed-use (taxable + exempt)
        'itc_claim_window_months' => 6,     // ITC can be claimed within 6 months
        'carry_forward'         => true,    // excess ITC carried to next period
        'refund_allowed'        => true,    // exporters can claim cash refund
        'auto_match_invoices'   => true,    // match purchase invoice to supplier's sales register
    ],

    /*
    |--------------------------------------------------------------------------
    | Withholding VAT (Deducted at Source)
    |--------------------------------------------------------------------------
    |
    | Certain buyers (govt agencies, listed companies) must withhold VAT
    | at source before making payment to the supplier.
    |
    */

    'withholding' => [
        'enabled'             => true,
        'default_rate'        => 15.0,      // percentage to withhold
        'applicable_entities' => ['government', 'listed_company', 'ngo'],
        'certificate_form'    => 'Mushak-6.6',
    ],

    /*
    |--------------------------------------------------------------------------
    | E-Filing / VOS Integration (NBR VAT Online System)
    |--------------------------------------------------------------------------
    */

    'efiling' => [
        'enabled'       => filter_var($_ENV['VAT_EFILING_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'base_url'      => $_ENV['VOS_BASE_URL'] ?? 'https://vat.gov.bd',
        'api_url'       => $_ENV['VOS_API_URL'] ?? 'https://api.vat.gov.bd/v1',
        'api_key'       => $_ENV['VOS_API_KEY'] ?? '',
        'api_secret'    => $_ENV['VOS_API_SECRET'] ?? '',
        'timeout'       => 30,
        'auto_submit'   => false,   // require manual review before submission
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Numbering
    |--------------------------------------------------------------------------
    */

    'invoice_numbering' => [
        'format'         => '{BRANCH}-{YEAR}{MONTH}-{SEQ:6}',
        'sequence_reset' => 'monthly',     // reset sequence: never | monthly | yearly
        'prefix'         => '',
        'suffix'         => '',
        'min_digits'     => 6,
        'start_from'     => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rounding Rules
    |--------------------------------------------------------------------------
    */

    'rounding' => [
        'precision'     => 2,               // decimal places for VAT amounts
        'method'        => 'half_up',       // half_up | half_even (banker's rounding)
        'per_line'      => false,           // round per line item or total only
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Trail
    |--------------------------------------------------------------------------
    */

    'audit' => [
        'enabled'           => true,
        'log_all_changes'   => true,
        'immutable_invoices'=> true,    // posted invoices cannot be edited, only credited
        'retain_years'      => 7,       // NBR requires 7 years of records
    ],

];
