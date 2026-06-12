<?php

declare(strict_types=1);

class CreateSalesTables
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `quotations` (
                `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `branch_id`       INT UNSIGNED NOT NULL,
                `customer_id`     INT UNSIGNED NOT NULL,
                `quotation_number` VARCHAR(50) NOT NULL,
                `date`            DATE NOT NULL,
                `expiry_date`     DATE,
                `status`          ENUM('draft','sent','accepted','rejected','expired') NOT NULL DEFAULT 'draft',
                `subtotal`        DECIMAL(15,2) NOT NULL DEFAULT 0,
                `vat_amount`      DECIMAL(15,2) NOT NULL DEFAULT 0,
                `discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
                `total_amount`    DECIMAL(15,2) NOT NULL DEFAULT 0,
                `notes`           TEXT,
                `terms`           TEXT,
                `created_by`      INT UNSIGNED NOT NULL,
                `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_quotations_number` (`quotation_number`),
                INDEX `idx_quotations_branch_id` (`branch_id`),
                INDEX `idx_quotations_customer_id` (`customer_id`),
                CONSTRAINT `fk_quotations_branch`   FOREIGN KEY (`branch_id`)   REFERENCES `branches` (`id`),
                CONSTRAINT `fk_quotations_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `quotation_items` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `quotation_id` INT UNSIGNED NOT NULL,
                `product_id`  INT UNSIGNED NOT NULL,
                `variant_id`  INT UNSIGNED,
                `quantity`    DECIMAL(15,4) NOT NULL DEFAULT 0,
                `unit_price`  DECIMAL(15,4) NOT NULL DEFAULT 0,
                `vat_rate`    DECIMAL(5,2) NOT NULL DEFAULT 0,
                `vat_amount`  DECIMAL(15,2) NOT NULL DEFAULT 0,
                `discount`    DECIMAL(5,2) NOT NULL DEFAULT 0,
                `total`       DECIMAL(15,2) NOT NULL DEFAULT 0,
                INDEX `idx_quotation_items_quotation_id` (`quotation_id`),
                CONSTRAINT `fk_quotation_items_quotation` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_quotation_items_product`   FOREIGN KEY (`product_id`)   REFERENCES `products` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `sales_orders` (
                `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `branch_id`        INT UNSIGNED NOT NULL,
                `customer_id`      INT UNSIGNED NOT NULL,
                `quotation_id`     INT UNSIGNED,
                `order_number`     VARCHAR(50) NOT NULL,
                `order_date`       DATE NOT NULL,
                `expected_delivery` DATE,
                `status`           ENUM('draft','confirmed','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'draft',
                `warehouse_id`     INT UNSIGNED NOT NULL,
                `subtotal`         DECIMAL(15,2) NOT NULL DEFAULT 0,
                `vat_amount`       DECIMAL(15,2) NOT NULL DEFAULT 0,
                `discount_amount`  DECIMAL(15,2) NOT NULL DEFAULT 0,
                `total_amount`     DECIMAL(15,2) NOT NULL DEFAULT 0,
                `paid_amount`      DECIMAL(15,2) NOT NULL DEFAULT 0,
                `notes`            TEXT,
                `created_by`       INT UNSIGNED NOT NULL,
                `approved_by`      INT UNSIGNED,
                `deleted_at`       TIMESTAMP NULL,
                `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_sales_orders_number` (`order_number`),
                INDEX `idx_sales_orders_branch_id` (`branch_id`),
                INDEX `idx_sales_orders_customer_id` (`customer_id`),
                INDEX `idx_sales_orders_status` (`status`),
                CONSTRAINT `fk_sales_orders_branch`     FOREIGN KEY (`branch_id`)    REFERENCES `branches` (`id`),
                CONSTRAINT `fk_sales_orders_customer`   FOREIGN KEY (`customer_id`)  REFERENCES `customers` (`id`),
                CONSTRAINT `fk_sales_orders_warehouse`  FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `sales_order_items` (
                `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `order_id`      INT UNSIGNED NOT NULL,
                `product_id`    INT UNSIGNED NOT NULL,
                `variant_id`    INT UNSIGNED,
                `quantity`      DECIMAL(15,4) NOT NULL DEFAULT 0,
                `delivered_qty` DECIMAL(15,4) NOT NULL DEFAULT 0,
                `unit_price`    DECIMAL(15,4) NOT NULL DEFAULT 0,
                `vat_rate`      DECIMAL(5,2) NOT NULL DEFAULT 0,
                `vat_amount`    DECIMAL(15,2) NOT NULL DEFAULT 0,
                `discount`      DECIMAL(5,2) NOT NULL DEFAULT 0,
                `total`         DECIMAL(15,2) NOT NULL DEFAULT 0,
                INDEX `idx_so_items_order_id` (`order_id`),
                CONSTRAINT `fk_so_items_order`   FOREIGN KEY (`order_id`)   REFERENCES `sales_orders` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_so_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `invoices` (
                `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `branch_id`       INT UNSIGNED NOT NULL,
                `customer_id`     INT UNSIGNED NOT NULL,
                `sales_order_id`  INT UNSIGNED,
                `invoice_number`  VARCHAR(50) NOT NULL,
                `invoice_date`    DATE NOT NULL,
                `due_date`        DATE NOT NULL,
                `warehouse_id`    INT UNSIGNED NOT NULL,
                `subtotal`        DECIMAL(15,2) NOT NULL DEFAULT 0,
                `vat_amount`      DECIMAL(15,2) NOT NULL DEFAULT 0,
                `discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
                `total_amount`    DECIMAL(15,2) NOT NULL DEFAULT 0,
                `paid_amount`     DECIMAL(15,2) NOT NULL DEFAULT 0,
                `balance`         DECIMAL(15,2) NOT NULL DEFAULT 0,
                `status`          ENUM('draft','sent','partial','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
                `notes`           TEXT,
                `terms`           TEXT,
                `created_by`      INT UNSIGNED NOT NULL,
                `deleted_at`      TIMESTAMP NULL,
                `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_invoices_number` (`invoice_number`),
                INDEX `idx_invoices_branch_id` (`branch_id`),
                INDEX `idx_invoices_customer_id` (`customer_id`),
                INDEX `idx_invoices_status` (`status`),
                INDEX `idx_invoices_due_date` (`due_date`),
                CONSTRAINT `fk_invoices_branch`      FOREIGN KEY (`branch_id`)      REFERENCES `branches` (`id`),
                CONSTRAINT `fk_invoices_customer`    FOREIGN KEY (`customer_id`)    REFERENCES `customers` (`id`),
                CONSTRAINT `fk_invoices_sales_order` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_invoices_warehouse`   FOREIGN KEY (`warehouse_id`)   REFERENCES `warehouses` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `invoice_items` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `invoice_id`  INT UNSIGNED NOT NULL,
                `product_id`  INT UNSIGNED NOT NULL,
                `variant_id`  INT UNSIGNED,
                `quantity`    DECIMAL(15,4) NOT NULL DEFAULT 0,
                `unit_price`  DECIMAL(15,4) NOT NULL DEFAULT 0,
                `vat_rate`    DECIMAL(5,2) NOT NULL DEFAULT 0,
                `vat_amount`  DECIMAL(15,2) NOT NULL DEFAULT 0,
                `discount`    DECIMAL(5,2) NOT NULL DEFAULT 0,
                `total`       DECIMAL(15,2) NOT NULL DEFAULT 0,
                INDEX `idx_invoice_items_invoice_id` (`invoice_id`),
                CONSTRAINT `fk_invoice_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_invoice_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `payments` (
                `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `branch_id`      INT UNSIGNED NOT NULL,
                `payment_type`   ENUM('received','made') NOT NULL,
                `payer_type`     ENUM('customer','supplier') NOT NULL,
                `payer_id`       INT UNSIGNED NOT NULL,
                `payment_number` VARCHAR(50) NOT NULL,
                `payment_date`   DATE NOT NULL,
                `amount`         DECIMAL(15,2) NOT NULL DEFAULT 0,
                `payment_method` ENUM('cash','bank_transfer','cheque','bkash','nagad','card') NOT NULL DEFAULT 'cash',
                `reference_number` VARCHAR(100),
                `bank_name`      VARCHAR(100),
                `cheque_number`  VARCHAR(100),
                `cheque_date`    DATE,
                `notes`          TEXT,
                `status`         ENUM('pending','completed','bounced','cancelled') NOT NULL DEFAULT 'completed',
                `created_by`     INT UNSIGNED NOT NULL,
                `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_payments_number` (`payment_number`),
                INDEX `idx_payments_branch_id` (`branch_id`),
                INDEX `idx_payments_payer` (`payer_type`, `payer_id`),
                INDEX `idx_payments_date` (`payment_date`),
                CONSTRAINT `fk_payments_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `payment_allocations` (
                `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `payment_id`       INT UNSIGNED NOT NULL,
                `invoice_type`     ENUM('invoice','purchase_invoice') NOT NULL DEFAULT 'invoice',
                `invoice_id`       INT UNSIGNED NOT NULL,
                `allocated_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
                INDEX `idx_payment_allocations_payment_id` (`payment_id`),
                CONSTRAINT `fk_payment_allocations_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `payment_allocations`");
        $pdo->exec("DROP TABLE IF EXISTS `payments`");
        $pdo->exec("DROP TABLE IF EXISTS `invoice_items`");
        $pdo->exec("DROP TABLE IF EXISTS `invoices`");
        $pdo->exec("DROP TABLE IF EXISTS `sales_order_items`");
        $pdo->exec("DROP TABLE IF EXISTS `sales_orders`");
        $pdo->exec("DROP TABLE IF EXISTS `quotation_items`");
        $pdo->exec("DROP TABLE IF EXISTS `quotations`");
    }
}
