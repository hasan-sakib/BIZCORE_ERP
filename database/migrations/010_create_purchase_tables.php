<?php

declare(strict_types=1);

class CreatePurchaseTables
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `purchase_orders` (
                `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `branch_id`       INT UNSIGNED NOT NULL,
                `supplier_id`     INT UNSIGNED NOT NULL,
                `po_number`       VARCHAR(50) NOT NULL,
                `order_date`      DATE NOT NULL,
                `expected_date`   DATE,
                `status`          ENUM('draft','sent','partial','received','cancelled') NOT NULL DEFAULT 'draft',
                `subtotal`        DECIMAL(15,2) NOT NULL DEFAULT 0,
                `vat_amount`      DECIMAL(15,2) NOT NULL DEFAULT 0,
                `discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
                `total_amount`    DECIMAL(15,2) NOT NULL DEFAULT 0,
                `notes`           TEXT,
                `created_by`      INT UNSIGNED NOT NULL,
                `approved_by`     INT UNSIGNED,
                `deleted_at`      TIMESTAMP NULL,
                `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_purchase_orders_po_number` (`po_number`),
                INDEX `idx_purchase_orders_branch_id` (`branch_id`),
                INDEX `idx_purchase_orders_supplier_id` (`supplier_id`),
                INDEX `idx_purchase_orders_status` (`status`),
                CONSTRAINT `fk_purchase_orders_branch`   FOREIGN KEY (`branch_id`)   REFERENCES `branches` (`id`),
                CONSTRAINT `fk_purchase_orders_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `purchase_order_items` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `po_id`       INT UNSIGNED NOT NULL,
                `product_id`  INT UNSIGNED NOT NULL,
                `variant_id`  INT UNSIGNED,
                `quantity`    DECIMAL(15,4) NOT NULL DEFAULT 0,
                `received_qty` DECIMAL(15,4) NOT NULL DEFAULT 0,
                `unit_price`  DECIMAL(15,4) NOT NULL DEFAULT 0,
                `vat_rate`    DECIMAL(5,2) NOT NULL DEFAULT 0,
                `vat_amount`  DECIMAL(15,2) NOT NULL DEFAULT 0,
                `discount`    DECIMAL(5,2) NOT NULL DEFAULT 0,
                `total`       DECIMAL(15,2) NOT NULL DEFAULT 0,
                INDEX `idx_po_items_po_id` (`po_id`),
                CONSTRAINT `fk_po_items_po`      FOREIGN KEY (`po_id`)      REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_po_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `goods_receipts` (
                `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `po_id`        INT UNSIGNED,
                `branch_id`    INT UNSIGNED NOT NULL,
                `supplier_id`  INT UNSIGNED NOT NULL,
                `grn_number`   VARCHAR(50) NOT NULL,
                `receipt_date` DATE NOT NULL,
                `warehouse_id` INT UNSIGNED NOT NULL,
                `status`       ENUM('draft','received','cancelled') NOT NULL DEFAULT 'draft',
                `subtotal`     DECIMAL(15,2) NOT NULL DEFAULT 0,
                `vat_amount`   DECIMAL(15,2) NOT NULL DEFAULT 0,
                `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
                `notes`        TEXT,
                `created_by`   INT UNSIGNED NOT NULL,
                `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_goods_receipts_grn_number` (`grn_number`),
                INDEX `idx_goods_receipts_branch_id` (`branch_id`),
                INDEX `idx_goods_receipts_supplier_id` (`supplier_id`),
                CONSTRAINT `fk_goods_receipts_po`        FOREIGN KEY (`po_id`)        REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_goods_receipts_branch`    FOREIGN KEY (`branch_id`)    REFERENCES `branches` (`id`),
                CONSTRAINT `fk_goods_receipts_supplier`  FOREIGN KEY (`supplier_id`)  REFERENCES `suppliers` (`id`),
                CONSTRAINT `fk_goods_receipts_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `goods_receipt_items` (
                `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `grn_id`       INT UNSIGNED NOT NULL,
                `product_id`   INT UNSIGNED NOT NULL,
                `variant_id`   INT UNSIGNED,
                `po_item_id`   INT UNSIGNED,
                `quantity`     DECIMAL(15,4) NOT NULL DEFAULT 0,
                `unit_cost`    DECIMAL(15,4) NOT NULL DEFAULT 0,
                `vat_amount`   DECIMAL(15,2) NOT NULL DEFAULT 0,
                `total`        DECIMAL(15,2) NOT NULL DEFAULT 0,
                `batch_number` VARCHAR(100),
                `expiry_date`  DATE,
                INDEX `idx_grn_items_grn_id` (`grn_id`),
                CONSTRAINT `fk_grn_items_grn`     FOREIGN KEY (`grn_id`)     REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_grn_items_product`  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `expense_categories` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`       VARCHAR(100) NOT NULL,
                `code`       VARCHAR(20) NOT NULL,
                `description` TEXT,
                `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_expense_categories_code` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `expenses` (
                `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `branch_id`       INT UNSIGNED NOT NULL,
                `category_id`     INT UNSIGNED NOT NULL,
                `expense_number`  VARCHAR(50) NOT NULL,
                `date`            DATE NOT NULL,
                `amount`          DECIMAL(15,2) NOT NULL DEFAULT 0,
                `vat_amount`      DECIMAL(15,2) NOT NULL DEFAULT 0,
                `total_amount`    DECIMAL(15,2) NOT NULL DEFAULT 0,
                `description`     TEXT,
                `payment_method`  VARCHAR(50),
                `reference_number` VARCHAR(100),
                `approved_by`     INT UNSIGNED,
                `status`          ENUM('draft','approved','rejected','paid') NOT NULL DEFAULT 'draft',
                `receipt_path`    VARCHAR(255),
                `created_by`      INT UNSIGNED NOT NULL,
                `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_expenses_number` (`expense_number`),
                INDEX `idx_expenses_branch_id` (`branch_id`),
                INDEX `idx_expenses_date` (`date`),
                CONSTRAINT `fk_expenses_branch`   FOREIGN KEY (`branch_id`)   REFERENCES `branches` (`id`),
                CONSTRAINT `fk_expenses_category` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `expenses`");
        $pdo->exec("DROP TABLE IF EXISTS `expense_categories`");
        $pdo->exec("DROP TABLE IF EXISTS `goods_receipt_items`");
        $pdo->exec("DROP TABLE IF EXISTS `goods_receipts`");
        $pdo->exec("DROP TABLE IF EXISTS `purchase_order_items`");
        $pdo->exec("DROP TABLE IF EXISTS `purchase_orders`");
    }
}
