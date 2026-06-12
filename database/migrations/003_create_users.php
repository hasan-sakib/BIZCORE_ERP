<?php

declare(strict_types=1);

class CreateUsers
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
                `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `branch_id`             INT UNSIGNED NOT NULL,
                `role_id`               INT UNSIGNED NOT NULL,
                `name`                  VARCHAR(150) NOT NULL,
                `email`                 VARCHAR(191) NOT NULL,
                `password`              VARCHAR(255) NOT NULL,
                `phone`                 VARCHAR(30),
                `avatar`                VARCHAR(255),
                `status`                ENUM('active','inactive','locked') NOT NULL DEFAULT 'active',
                `email_verified_at`     TIMESTAMP NULL,
                `remember_token`        VARCHAR(100),
                `last_login_at`         TIMESTAMP NULL,
                `failed_login_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `locked_until`          TIMESTAMP NULL,
                `must_change_password`  TINYINT(1) NOT NULL DEFAULT 0,
                `two_factor_secret`     VARCHAR(255),
                `preferences`           JSON,
                `created_by`            INT UNSIGNED,
                `updated_by`            INT UNSIGNED,
                `deleted_at`            TIMESTAMP NULL,
                `created_at`            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_users_email` (`email`),
                INDEX `idx_users_branch_id` (`branch_id`),
                INDEX `idx_users_role_id` (`role_id`),
                INDEX `idx_users_status` (`status`),
                CONSTRAINT `fk_users_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
                CONSTRAINT `fk_users_role`   FOREIGN KEY (`role_id`)   REFERENCES `roles` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `users`");
    }
}
