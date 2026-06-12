<?php

declare(strict_types=1);

/**
 * BizCore ERP - Mail Configuration
 *
 * Supports SMTP (production), Mailhog (local dev), sendmail, and a null
 * "log" driver that writes to the application log (useful for testing).
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | Supported: "smtp", "sendmail", "log", "array", "null"
    |
    */

    'default' => $_ENV['MAIL_DRIVER'] ?? 'smtp',

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    */

    'mailers' => [

        'smtp' => [
            'transport'  => 'smtp',
            'host'       => $_ENV['MAIL_HOST'] ?? 'mailhog',
            'port'       => (int) ($_ENV['MAIL_PORT'] ?? 1025),
            'encryption' => ($_ENV['MAIL_ENCRYPTION'] === 'null' || empty($_ENV['MAIL_ENCRYPTION']))
                                ? null
                                : $_ENV['MAIL_ENCRYPTION'],
            'username'   => ($_ENV['MAIL_USERNAME'] === 'null' || empty($_ENV['MAIL_USERNAME']))
                                ? null
                                : $_ENV['MAIL_USERNAME'],
            'password'   => ($_ENV['MAIL_PASSWORD'] === 'null' || empty($_ENV['MAIL_PASSWORD']))
                                ? null
                                : $_ENV['MAIL_PASSWORD'],
            'timeout'    => 30,
            'auth_mode'  => null,
            'verify_peer'=> filter_var($_ENV['MAIL_VERIFY_PEER'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'verify_peer_name' => filter_var($_ENV['MAIL_VERIFY_PEER_NAME'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path'      => $_ENV['MAIL_SENDMAIL_PATH'] ?? '/usr/sbin/sendmail -bs -i',
        ],

        'log' => [
            'transport' => 'log',
            'channel'   => 'mail',
        ],

        'array' => [
            'transport' => 'array',
        ],

        'null' => [
            'transport' => 'null',
        ],

        // Backup SMTP (transactional email service)
        'ses' => [
            'transport' => 'smtp',
            'host'      => $_ENV['SES_SMTP_HOST'] ?? 'email-smtp.ap-southeast-1.amazonaws.com',
            'port'      => (int) ($_ENV['SES_SMTP_PORT'] ?? 587),
            'encryption'=> 'tls',
            'username'  => $_ENV['SES_SMTP_USERNAME'] ?? null,
            'password'  => $_ENV['SES_SMTP_PASSWORD'] ?? null,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | From Address
    |--------------------------------------------------------------------------
    */

    'from' => [
        'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@bizcore.local',
        'name'    => $_ENV['MAIL_FROM_NAME'] ?? 'BizCore ERP',
    ],

    /*
    |--------------------------------------------------------------------------
    | Reply-To Addresses
    |--------------------------------------------------------------------------
    */

    'reply_to' => [
        'address' => $_ENV['MAIL_REPLY_TO_ADDRESS'] ?? null,
        'name'    => $_ENV['MAIL_REPLY_TO_NAME'] ?? null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Mail Templates
    |--------------------------------------------------------------------------
    */

    'templates_path' => dirname(__DIR__) . '/resources/views/emails',

    'templates' => [

        // Authentication
        'welcome'               => 'auth/welcome',
        'password_reset'        => 'auth/password-reset',
        'email_verification'    => 'auth/email-verification',
        'account_locked'        => 'auth/account-locked',
        'login_notification'    => 'auth/login-notification',
        'mfa_otp'               => 'auth/mfa-otp',

        // Sales
        'invoice'               => 'sales/invoice',
        'quotation'             => 'sales/quotation',
        'delivery_note'         => 'sales/delivery-note',
        'payment_receipt'       => 'sales/payment-receipt',
        'payment_reminder'      => 'sales/payment-reminder',
        'overdue_notice'        => 'sales/overdue-notice',

        // Purchasing
        'purchase_order'        => 'purchasing/purchase-order',
        'grn_notification'      => 'purchasing/grn-notification',

        // HR / Payroll
        'payslip'               => 'hr/payslip',
        'leave_approved'        => 'hr/leave-approved',
        'leave_rejected'        => 'hr/leave-rejected',
        'offer_letter'          => 'hr/offer-letter',

        // System
        'low_stock_alert'       => 'inventory/low-stock-alert',
        'daily_report'          => 'reports/daily-report',
        'system_alert'          => 'system/alert',
        'backup_complete'       => 'system/backup-complete',
        'vat_return_due'        => 'vat/return-due-reminder',

    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration for Mail
    |--------------------------------------------------------------------------
    |
    | Mails are queued to prevent blocking request/response cycles.
    |
    */

    'queue' => [
        'enabled'     => filter_var($_ENV['MAIL_QUEUE'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'connection'  => 'redis',
        'queue_name'  => 'emails',
        'delay'       => 0,
        'max_tries'   => 3,
        'retry_after' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Attachments & Limits
    |--------------------------------------------------------------------------
    */

    'attachments' => [
        'max_size_kb'        => 10240,      // 10 MB per attachment
        'max_total_size_kb'  => 25600,      // 25 MB total per email
        'allowed_types'      => ['pdf', 'png', 'jpg', 'jpeg', 'xlsx', 'docx', 'csv'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    */

    'notification_channels' => [
        'mail'     => true,
        'database' => true,
        'sms'      => filter_var($_ENV['SMS_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS (Bangladesh — for OTPs and alerts)
    |--------------------------------------------------------------------------
    */

    'sms' => [
        'driver'   => $_ENV['SMS_DRIVER'] ?? 'ssl_wireless',
        'api_url'  => $_ENV['SMS_API_URL'] ?? 'https://msgblaze.com/api/sms',
        'api_key'  => $_ENV['SMS_API_KEY'] ?? '',
        'sender'   => $_ENV['SMS_SENDER_ID'] ?? 'BizCore',
        'timeout'  => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */

    'log' => [
        'enabled'      => true,
        'log_sent'     => true,
        'log_failed'   => true,
        'retain_days'  => 90,
    ],

];
