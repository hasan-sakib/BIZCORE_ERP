<?php

declare(strict_types=1);

class CreateRoles
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `roles` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`        VARCHAR(100) NOT NULL,
                `slug`        VARCHAR(100) NOT NULL,
                `description` TEXT,
                `permissions` JSON,
                `is_system`   TINYINT(1) NOT NULL DEFAULT 0,
                `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_roles_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `roles`");
    }
}
