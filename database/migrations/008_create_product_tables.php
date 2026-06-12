<?php

declare(strict_types=1);

class CreateProductTables
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `categories` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `parent_id`   INT UNSIGNED,
                `name`        VARCHAR(150) NOT NULL,
                `slug`        VARCHAR(191) NOT NULL,
                `description` TEXT,
                `image`       VARCHAR(255),
                `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
                `sort_order`  INT NOT NULL DEFAULT 0,
                `deleted_at`  TIMESTAMP NULL,
                `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_categories_slug` (`slug`),
                INDEX `idx_categories_parent_id` (`parent_id`),
                CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `brands` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`        VARCHAR(150) NOT NULL,
                `slug`        VARCHAR(191) NOT NULL,
                `description` TEXT,
                `logo`        VARCHAR(255),
                `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
                `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_brands_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `units` (
                `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`              VARCHAR(50) NOT NULL,
                `abbreviation`      VARCHAR(20) NOT NULL,
                `base_unit_id`      INT UNSIGNED,
                `conversion_factor` DECIMAL(10,4) NOT NULL DEFAULT 1,
                `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT `fk_units_base_unit` FOREIGN KEY (`base_unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `products` (
                `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `category_id`       INT UNSIGNED NOT NULL,
                `brand_id`          INT UNSIGNED,
                `unit_id`           INT UNSIGNED NOT NULL,
                `name`              VARCHAR(255) NOT NULL,
                `slug`              VARCHAR(291) NOT NULL,
                `sku`               VARCHAR(100) NOT NULL,
                `barcode`           VARCHAR(100),
                `description`       TEXT,
                `short_description` VARCHAR(500),
                `type`              ENUM('simple','variant','service') NOT NULL DEFAULT 'simple',
                `purchase_price`    DECIMAL(15,2) NOT NULL DEFAULT 0,
                `selling_price`     DECIMAL(15,2) NOT NULL DEFAULT 0,
                `min_selling_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
                `vat_rate`          DECIMAL(5,2) NOT NULL DEFAULT 15,
                `is_vat_inclusive`  TINYINT(1) NOT NULL DEFAULT 0,
                `reorder_point`     INT NOT NULL DEFAULT 0,
                `is_active`         TINYINT(1) NOT NULL DEFAULT 1,
                `images`            JSON,
                `attributes`        JSON,
                `meta`              JSON,
                `created_by`        INT UNSIGNED,
                `deleted_at`        TIMESTAMP NULL,
                `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_products_sku` (`sku`),
                UNIQUE KEY `uk_products_slug` (`slug`),
                INDEX `idx_products_category_id` (`category_id`),
                INDEX `idx_products_brand_id` (`brand_id`),
                INDEX `idx_products_is_active` (`is_active`),
                FULLTEXT INDEX `ft_products_name` (`name`, `description`),
                CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
                CONSTRAINT `fk_products_brand`    FOREIGN KEY (`brand_id`)    REFERENCES `brands` (`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_products_unit`     FOREIGN KEY (`unit_id`)     REFERENCES `units` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `product_variants` (
                `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `product_id`     INT UNSIGNED NOT NULL,
                `sku`            VARCHAR(100) NOT NULL,
                `barcode`        VARCHAR(100),
                `attributes`     JSON,
                `purchase_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
                `selling_price`  DECIMAL(15,2) NOT NULL DEFAULT 0,
                `images`         JSON,
                `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
                `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_product_variants_sku` (`sku`),
                INDEX `idx_product_variants_product_id` (`product_id`),
                CONSTRAINT `fk_product_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `product_variants`");
        $pdo->exec("DROP TABLE IF EXISTS `products`");
        $pdo->exec("DROP TABLE IF EXISTS `units`");
        $pdo->exec("DROP TABLE IF EXISTS `brands`");
        $pdo->exec("DROP TABLE IF EXISTS `categories`");
    }
}
