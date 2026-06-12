<?php

declare(strict_types=1);

class CreateInventoryTables
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `warehouses` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `branch_id`  INT UNSIGNED NOT NULL,
                `name`       VARCHAR(150) NOT NULL,
                `code`       VARCHAR(30) NOT NULL,
                `address`    TEXT,
                `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
                `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_warehouses_code` (`code`),
                INDEX `idx_warehouses_branch_id` (`branch_id`),
                CONSTRAINT `fk_warehouses_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `inventory` (
                `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `product_id`         INT UNSIGNED NOT NULL,
                `variant_id`         INT UNSIGNED,
                `warehouse_id`       INT UNSIGNED NOT NULL,
                `branch_id`          INT UNSIGNED NOT NULL,
                `quantity`           DECIMAL(15,4) NOT NULL DEFAULT 0,
                `reserved_quantity`  DECIMAL(15,4) NOT NULL DEFAULT 0,
                `avg_cost`           DECIMAL(15,4) NOT NULL DEFAULT 0,
                `last_restock_date`  DATE,
                `created_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_inventory_product_variant_warehouse` (`product_id`, `variant_id`, `warehouse_id`),
                INDEX `idx_inventory_warehouse_id` (`warehouse_id`),
                INDEX `idx_inventory_branch_id` (`branch_id`),
                CONSTRAINT `fk_inventory_product`   FOREIGN KEY (`product_id`)   REFERENCES `products` (`id`),
                CONSTRAINT `fk_inventory_variant`   FOREIGN KEY (`variant_id`)   REFERENCES `product_variants` (`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_inventory_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
                CONSTRAINT `fk_inventory_branch`    FOREIGN KEY (`branch_id`)    REFERENCES `branches` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `stock_movements` (
                `id`             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `product_id`     INT UNSIGNED NOT NULL,
                `variant_id`     INT UNSIGNED,
                `warehouse_id`   INT UNSIGNED NOT NULL,
                `branch_id`      INT UNSIGNED NOT NULL,
                `movement_type`  ENUM('purchase','sale','return_in','return_out','transfer_in','transfer_out','adjustment','opening') NOT NULL,
                `reference_type` VARCHAR(100),
                `reference_id`   INT UNSIGNED,
                `quantity`       DECIMAL(15,4) NOT NULL,
                `unit_cost`      DECIMAL(15,4) NOT NULL DEFAULT 0,
                `total_cost`     DECIMAL(15,4) NOT NULL DEFAULT 0,
                `balance_after`  DECIMAL(15,4) NOT NULL DEFAULT 0,
                `notes`          TEXT,
                `created_by`     INT UNSIGNED NOT NULL,
                `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_stock_movements_product_id` (`product_id`),
                INDEX `idx_stock_movements_warehouse_id` (`warehouse_id`),
                INDEX `idx_stock_movements_branch_date` (`branch_id`, `created_at`),
                INDEX `idx_stock_movements_reference` (`reference_type`, `reference_id`),
                CONSTRAINT `fk_stock_movements_product`   FOREIGN KEY (`product_id`)   REFERENCES `products` (`id`),
                CONSTRAINT `fk_stock_movements_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
                CONSTRAINT `fk_stock_movements_branch`    FOREIGN KEY (`branch_id`)    REFERENCES `branches` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `stock_transfers` (
                `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `from_warehouse_id`  INT UNSIGNED NOT NULL,
                `to_warehouse_id`    INT UNSIGNED NOT NULL,
                `from_branch_id`     INT UNSIGNED NOT NULL,
                `to_branch_id`       INT UNSIGNED NOT NULL,
                `transfer_number`    VARCHAR(50) NOT NULL,
                `transfer_date`      DATE NOT NULL,
                `status`             ENUM('draft','in_transit','received','cancelled') NOT NULL DEFAULT 'draft',
                `notes`              TEXT,
                `requested_by`       INT UNSIGNED NOT NULL,
                `approved_by`        INT UNSIGNED,
                `received_by`        INT UNSIGNED,
                `created_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_stock_transfers_number` (`transfer_number`),
                INDEX `idx_stock_transfers_from_warehouse` (`from_warehouse_id`),
                INDEX `idx_stock_transfers_to_warehouse` (`to_warehouse_id`),
                CONSTRAINT `fk_stock_transfers_from_warehouse` FOREIGN KEY (`from_warehouse_id`) REFERENCES `warehouses` (`id`),
                CONSTRAINT `fk_stock_transfers_to_warehouse`   FOREIGN KEY (`to_warehouse_id`)   REFERENCES `warehouses` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `stock_transfer_items` (
                `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `transfer_id`     INT UNSIGNED NOT NULL,
                `product_id`      INT UNSIGNED NOT NULL,
                `variant_id`      INT UNSIGNED,
                `requested_qty`   DECIMAL(15,4) NOT NULL DEFAULT 0,
                `transferred_qty` DECIMAL(15,4) NOT NULL DEFAULT 0,
                `received_qty`    DECIMAL(15,4) NOT NULL DEFAULT 0,
                `unit_cost`       DECIMAL(15,4) NOT NULL DEFAULT 0,
                INDEX `idx_transfer_items_transfer_id` (`transfer_id`),
                CONSTRAINT `fk_transfer_items_transfer` FOREIGN KEY (`transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_transfer_items_product`  FOREIGN KEY (`product_id`)  REFERENCES `products` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `stock_transfer_items`");
        $pdo->exec("DROP TABLE IF EXISTS `stock_transfers`");
        $pdo->exec("DROP TABLE IF EXISTS `stock_movements`");
        $pdo->exec("DROP TABLE IF EXISTS `inventory`");
        $pdo->exec("DROP TABLE IF EXISTS `warehouses`");
    }
}
