<?php

declare(strict_types=1);

class CreateAuditAndSessions
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `login_history` (
                `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id`        INT UNSIGNED NOT NULL,
                `ip_address`     VARCHAR(45) NOT NULL,
                `user_agent`     VARCHAR(512),
                `status`         ENUM('success','failed') NOT NULL,
                `failure_reason` VARCHAR(255),
                `location`       VARCHAR(255),
                `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_login_history_user_id` (`user_id`),
                INDEX `idx_login_history_status` (`status`),
                CONSTRAINT `fk_login_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `password_history` (
                `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id`      INT UNSIGNED NOT NULL,
                `password_hash` VARCHAR(255) NOT NULL,
                `changed_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_password_history_user_id` (`user_id`),
                CONSTRAINT `fk_password_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `audit_logs` (
                `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id`     INT UNSIGNED,
                `action`      VARCHAR(100) NOT NULL,
                `entity_type` VARCHAR(100) NOT NULL,
                `entity_id`   INT UNSIGNED,
                `old_values`  JSON,
                `new_values`  JSON,
                `ip_address`  VARCHAR(45),
                `user_agent`  VARCHAR(512),
                `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_audit_logs_user_id` (`user_id`),
                INDEX `idx_audit_logs_entity` (`entity_type`, `entity_id`),
                INDEX `idx_audit_logs_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `notifications` (
                `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id`    INT UNSIGNED,
                `type`       VARCHAR(100) NOT NULL,
                `title`      VARCHAR(255) NOT NULL,
                `message`    TEXT NOT NULL,
                `data`       JSON,
                `read_at`    TIMESTAMP NULL,
                `expires_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_notifications_user_id` (`user_id`),
                INDEX `idx_notifications_read_at` (`read_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `user_sessions` (
                `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id`       BIGINT UNSIGNED NOT NULL,
                `session_token` VARCHAR(512) NOT NULL,
                `refresh_token` VARCHAR(512) NOT NULL,
                `ip_address`    VARCHAR(45) DEFAULT NULL,
                `user_agent`    TEXT,
                `expires_at`    DATETIME NOT NULL,
                `revoked_at`    DATETIME DEFAULT NULL,
                `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_user_sessions_user_id` (`user_id`),
                INDEX `idx_user_sessions_refresh_token` (`refresh_token`(255))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `settings` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `key`         VARCHAR(100) NOT NULL,
                `value`       TEXT,
                `group`       VARCHAR(50) NOT NULL DEFAULT 'general',
                `type`        ENUM('string','integer','boolean','json','encrypted') DEFAULT 'string',
                `description` TEXT,
                `is_system`   TINYINT(1) NOT NULL DEFAULT 0,
                `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_settings_key` (`key`),
                INDEX `idx_settings_group` (`group`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `settings`");
        $pdo->exec("DROP TABLE IF EXISTS `notifications`");
        $pdo->exec("DROP TABLE IF EXISTS `audit_logs`");
        $pdo->exec("DROP TABLE IF EXISTS `password_history`");
        $pdo->exec("DROP TABLE IF EXISTS `login_history`");
    }
}
