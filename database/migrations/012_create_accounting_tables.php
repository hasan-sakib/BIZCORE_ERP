<?php

declare(strict_types=1);

class CreateAccountingTables
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `accounts` (
                `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `parent_id`      INT UNSIGNED,
                `code`           VARCHAR(20) NOT NULL,
                `name`           VARCHAR(150) NOT NULL,
                `type`           ENUM('asset','liability','equity','revenue','expense') NOT NULL,
                `subtype`        VARCHAR(50),
                `is_system`      TINYINT(1) NOT NULL DEFAULT 0,
                `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
                `normal_balance` ENUM('debit','credit') NOT NULL DEFAULT 'debit',
                `description`    TEXT,
                `balance`        DECIMAL(15,2) NOT NULL DEFAULT 0,
                `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_accounts_code` (`code`),
                INDEX `idx_accounts_type` (`type`),
                INDEX `idx_accounts_parent_id` (`parent_id`),
                CONSTRAINT `fk_accounts_parent` FOREIGN KEY (`parent_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `journal_entries` (
                `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `branch_id`       INT UNSIGNED NOT NULL,
                `entry_number`    VARCHAR(50) NOT NULL,
                `date`            DATE NOT NULL,
                `reference_type`  VARCHAR(100),
                `reference_id`    INT UNSIGNED,
                `description`     TEXT,
                `total_debit`     DECIMAL(15,2) NOT NULL DEFAULT 0,
                `total_credit`    DECIMAL(15,2) NOT NULL DEFAULT 0,
                `status`          ENUM('draft','posted','reversed') NOT NULL DEFAULT 'draft',
                `posted_by`       INT UNSIGNED,
                `posted_at`       TIMESTAMP NULL,
                `reversed_by`     INT UNSIGNED,
                `reversed_at`     TIMESTAMP NULL,
                `created_by`      INT UNSIGNED NOT NULL,
                `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_journal_entries_number` (`entry_number`),
                INDEX `idx_journal_entries_branch_id` (`branch_id`),
                INDEX `idx_journal_entries_date` (`date`),
                INDEX `idx_journal_entries_status` (`status`),
                INDEX `idx_journal_entries_reference` (`reference_type`, `reference_id`),
                CONSTRAINT `fk_journal_entries_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `journal_entry_lines` (
                `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `journal_entry_id` INT UNSIGNED NOT NULL,
                `account_id`       INT UNSIGNED NOT NULL,
                `debit`            DECIMAL(15,2) NOT NULL DEFAULT 0,
                `credit`           DECIMAL(15,2) NOT NULL DEFAULT 0,
                `description`      VARCHAR(255),
                `branch_id`        INT UNSIGNED NOT NULL,
                INDEX `idx_jel_journal_entry_id` (`journal_entry_id`),
                INDEX `idx_jel_account_id` (`account_id`),
                CONSTRAINT `fk_jel_journal_entry` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_jel_account`        FOREIGN KEY (`account_id`)       REFERENCES `accounts` (`id`),
                CONSTRAINT `fk_jel_branch`          FOREIGN KEY (`branch_id`)        REFERENCES `branches` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `tax_records` (
                `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `branch_id`       INT UNSIGNED NOT NULL,
                `period_type`     ENUM('monthly','quarterly','annual') NOT NULL DEFAULT 'monthly',
                `period_start`    DATE NOT NULL,
                `period_end`      DATE NOT NULL,
                `total_sales`     DECIMAL(15,2) NOT NULL DEFAULT 0,
                `total_purchases` DECIMAL(15,2) NOT NULL DEFAULT 0,
                `vat_collected`   DECIMAL(15,2) NOT NULL DEFAULT 0,
                `vat_paid`        DECIMAL(15,2) NOT NULL DEFAULT 0,
                `net_vat`         DECIMAL(15,2) NOT NULL DEFAULT 0,
                `status`          ENUM('draft','filed','paid') NOT NULL DEFAULT 'draft',
                `filed_at`        TIMESTAMP NULL,
                `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_tax_records_branch_id` (`branch_id`),
                CONSTRAINT `fk_tax_records_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `file_uploads` (
                `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id`       INT UNSIGNED NOT NULL,
                `original_name` VARCHAR(255) NOT NULL,
                `stored_name`   VARCHAR(255) NOT NULL,
                `path`          VARCHAR(500) NOT NULL,
                `mime_type`     VARCHAR(100),
                `size`          BIGINT UNSIGNED NOT NULL DEFAULT 0,
                `entity_type`   VARCHAR(100),
                `entity_id`     INT UNSIGNED,
                `disk`          ENUM('local','s3') NOT NULL DEFAULT 'local',
                `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_file_uploads_user_id` (`user_id`),
                INDEX `idx_file_uploads_entity` (`entity_type`, `entity_id`),
                CONSTRAINT `fk_file_uploads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `file_uploads`");
        $pdo->exec("DROP TABLE IF EXISTS `tax_records`");
        $pdo->exec("DROP TABLE IF EXISTS `journal_entry_lines`");
        $pdo->exec("DROP TABLE IF EXISTS `journal_entries`");
        $pdo->exec("DROP TABLE IF EXISTS `accounts`");
    }
}
