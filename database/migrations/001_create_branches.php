<?php

declare(strict_types=1);

class CreateBranches
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `branches` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`       VARCHAR(150) NOT NULL,
                `code`       VARCHAR(20) NOT NULL,
                `address`    JSON,
                `phone`      VARCHAR(30),
                `email`      VARCHAR(150),
                `manager_id` INT UNSIGNED,
                `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
                `settings`   JSON,
                `deleted_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_branches_code` (`code`),
                INDEX `idx_branches_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `branches`");
    }
}
