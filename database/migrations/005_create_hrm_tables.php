<?php

declare(strict_types=1);

class CreateHrmTables
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `departments` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `branch_id`   INT UNSIGNED NOT NULL,
                `name`        VARCHAR(150) NOT NULL,
                `code`        VARCHAR(20) NOT NULL,
                `description` TEXT,
                `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
                `created_by`  INT UNSIGNED,
                `deleted_at`  TIMESTAMP NULL,
                `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_depts_branch_code` (`branch_id`, `code`),
                INDEX `idx_departments_branch_id` (`branch_id`),
                CONSTRAINT `fk_departments_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `designations` (
                `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `department_id` INT UNSIGNED NOT NULL,
                `name`          VARCHAR(150) NOT NULL,
                `code`          VARCHAR(20),
                `level`         TINYINT UNSIGNED DEFAULT 1,
                `description`   TEXT,
                `deleted_at`    TIMESTAMP NULL,
                `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_designations_dept_id` (`department_id`),
                CONSTRAINT `fk_designations_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `employees` (
                `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `employee_number`    VARCHAR(30) NOT NULL,
                `user_id`            INT UNSIGNED,
                `branch_id`          INT UNSIGNED NOT NULL,
                `department_id`      INT UNSIGNED NOT NULL,
                `designation_id`     INT UNSIGNED NOT NULL,
                `first_name`         VARCHAR(100) NOT NULL,
                `last_name`          VARCHAR(100) NOT NULL,
                `email`              VARCHAR(191) NOT NULL,
                `phone`              VARCHAR(30),
                `date_of_birth`      DATE,
                `gender`             ENUM('male','female','other'),
                `blood_group`        VARCHAR(5),
                `nid_number`         VARCHAR(50),
                `religion`           VARCHAR(50),
                `marital_status`     ENUM('single','married','divorced','widowed'),
                `address`            JSON,
                `emergency_contact`  JSON,
                `bank_details`       TEXT,
                `join_date`          DATE NOT NULL,
                `confirmation_date`  DATE,
                `status`             ENUM('active','inactive','terminated','on_leave') NOT NULL DEFAULT 'active',
                `avatar`             VARCHAR(255),
                `documents`          JSON,
                `created_by`         INT UNSIGNED,
                `updated_by`         INT UNSIGNED,
                `deleted_at`         TIMESTAMP NULL,
                `created_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_employees_number` (`employee_number`),
                UNIQUE KEY `uk_employees_email` (`email`),
                INDEX `idx_employees_branch_id` (`branch_id`),
                INDEX `idx_employees_department_id` (`department_id`),
                INDEX `idx_employees_status` (`status`),
                FULLTEXT INDEX `ft_employees_name` (`first_name`, `last_name`),
                CONSTRAINT `fk_employees_branch`      FOREIGN KEY (`branch_id`)      REFERENCES `branches` (`id`),
                CONSTRAINT `fk_employees_department`  FOREIGN KEY (`department_id`)  REFERENCES `departments` (`id`),
                CONSTRAINT `fk_employees_designation` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`),
                CONSTRAINT `fk_employees_user`        FOREIGN KEY (`user_id`)        REFERENCES `users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `employee_transfers` (
                `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `employee_id`        INT UNSIGNED NOT NULL,
                `from_branch_id`     INT UNSIGNED NOT NULL,
                `to_branch_id`       INT UNSIGNED NOT NULL,
                `from_department_id` INT UNSIGNED NOT NULL,
                `to_department_id`   INT UNSIGNED NOT NULL,
                `transfer_date`      DATE NOT NULL,
                `reason`             TEXT,
                `approved_by`        INT UNSIGNED,
                `status`             ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                `created_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_transfers_employee_id` (`employee_id`),
                CONSTRAINT `fk_transfers_employee`   FOREIGN KEY (`employee_id`)    REFERENCES `employees` (`id`),
                CONSTRAINT `fk_transfers_from_branch` FOREIGN KEY (`from_branch_id`) REFERENCES `branches` (`id`),
                CONSTRAINT `fk_transfers_to_branch`   FOREIGN KEY (`to_branch_id`)   REFERENCES `branches` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `leave_types` (
                `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`           VARCHAR(100) NOT NULL,
                `days_per_year`  INT NOT NULL DEFAULT 0,
                `carry_forward`  TINYINT(1) NOT NULL DEFAULT 0,
                `paid`           TINYINT(1) NOT NULL DEFAULT 1,
                `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `leave_requests` (
                `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `employee_id`    INT UNSIGNED NOT NULL,
                `leave_type_id`  INT UNSIGNED NOT NULL,
                `from_date`      DATE NOT NULL,
                `to_date`        DATE NOT NULL,
                `days`           INT NOT NULL DEFAULT 1,
                `reason`         TEXT,
                `status`         ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                `approved_by`    INT UNSIGNED,
                `approved_at`    TIMESTAMP NULL,
                `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_leave_requests_employee_id` (`employee_id`),
                INDEX `idx_leave_requests_status` (`status`),
                CONSTRAINT `fk_leave_requests_employee`   FOREIGN KEY (`employee_id`)   REFERENCES `employees` (`id`),
                CONSTRAINT `fk_leave_requests_leave_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `attendance` (
                `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `employee_id`     INT UNSIGNED NOT NULL,
                `branch_id`       INT UNSIGNED NOT NULL,
                `date`            DATE NOT NULL,
                `check_in`        DATETIME,
                `check_out`       DATETIME,
                `working_hours`   DECIMAL(5,2) DEFAULT 0,
                `overtime_hours`  DECIMAL(5,2) DEFAULT 0,
                `status`          ENUM('present','absent','half_day','late','on_leave','holiday') NOT NULL DEFAULT 'present',
                `notes`           TEXT,
                `created_by`      INT UNSIGNED,
                `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_attendance_employee_date` (`employee_id`, `date`),
                INDEX `idx_attendance_branch_date` (`branch_id`, `date`),
                CONSTRAINT `fk_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
                CONSTRAINT `fk_attendance_branch`   FOREIGN KEY (`branch_id`)   REFERENCES `branches` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `attendance`");
        $pdo->exec("DROP TABLE IF EXISTS `leave_requests`");
        $pdo->exec("DROP TABLE IF EXISTS `leave_types`");
        $pdo->exec("DROP TABLE IF EXISTS `employee_transfers`");
        $pdo->exec("DROP TABLE IF EXISTS `employees`");
        $pdo->exec("DROP TABLE IF EXISTS `designations`");
        $pdo->exec("DROP TABLE IF EXISTS `departments`");
    }
}
