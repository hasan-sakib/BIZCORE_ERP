-- BizCore ERP MySQL Initialization
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Create production database
CREATE DATABASE IF NOT EXISTS `bizcore_erp`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Create testing database
CREATE DATABASE IF NOT EXISTS `bizcore_testing`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Grant permissions
GRANT ALL PRIVILEGES ON `bizcore_erp`.* TO 'bizcore'@'%';
GRANT ALL PRIVILEGES ON `bizcore_testing`.* TO 'bizcore'@'%';
FLUSH PRIVILEGES;

USE `bizcore_erp`;

-- Global SQL mode
SET GLOBAL sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
SET GLOBAL time_zone = '+06:00';
SET GLOBAL character_set_server = 'utf8mb4';
SET GLOBAL collation_server = 'utf8mb4_unicode_ci';

-- Migrations tracking table
CREATE TABLE IF NOT EXISTS `migrations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(255) NOT NULL,
    `batch` INT NOT NULL DEFAULT 1,
    `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
