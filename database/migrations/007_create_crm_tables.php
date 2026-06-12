<?php

declare(strict_types=1);

class CreateCrmTables
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `customers` (
                `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `branch_id`           INT UNSIGNED NOT NULL,
                `customer_code`       VARCHAR(30) NOT NULL,
                `name`                VARCHAR(150) NOT NULL,
                `email`               VARCHAR(191),
                `phone`               VARCHAR(30),
                `address`             JSON,
                `company_name`        VARCHAR(150),
                `contact_person`      VARCHAR(150),
                `credit_limit`        DECIMAL(15,2) NOT NULL DEFAULT 0,
                `outstanding_balance` DECIMAL(15,2) NOT NULL DEFAULT 0,
                `total_purchases`     DECIMAL(15,2) NOT NULL DEFAULT 0,
                `loyalty_points`      INT NOT NULL DEFAULT 0,
                `status`              ENUM('active','inactive') NOT NULL DEFAULT 'active',
                `notes`               TEXT,
                `created_by`          INT UNSIGNED,
                `deleted_at`          TIMESTAMP NULL,
                `created_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_customers_code` (`customer_code`),
                INDEX `idx_customers_branch_id` (`branch_id`),
                INDEX `idx_customers_status` (`status`),
                FULLTEXT INDEX `ft_customers_name` (`name`, `company_name`),
                CONSTRAINT `fk_customers_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `suppliers` (
                `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `branch_id`           INT UNSIGNED NOT NULL,
                `supplier_code`       VARCHAR(30) NOT NULL,
                `name`                VARCHAR(150) NOT NULL,
                `email`               VARCHAR(191),
                `phone`               VARCHAR(30),
                `address`             JSON,
                `company_name`        VARCHAR(150),
                `contact_person`      VARCHAR(150),
                `credit_terms`        INT NOT NULL DEFAULT 30,
                `outstanding_balance` DECIMAL(15,2) NOT NULL DEFAULT 0,
                `total_purchases`     DECIMAL(15,2) NOT NULL DEFAULT 0,
                `bank_details`        JSON,
                `status`              ENUM('active','inactive') NOT NULL DEFAULT 'active',
                `notes`               TEXT,
                `created_by`          INT UNSIGNED,
                `deleted_at`          TIMESTAMP NULL,
                `created_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_suppliers_code` (`supplier_code`),
                INDEX `idx_suppliers_branch_id` (`branch_id`),
                FULLTEXT INDEX `ft_suppliers_name` (`name`, `company_name`),
                CONSTRAINT `fk_suppliers_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `suppliers`");
        $pdo->exec("DROP TABLE IF EXISTS `customers`");
    }
}
