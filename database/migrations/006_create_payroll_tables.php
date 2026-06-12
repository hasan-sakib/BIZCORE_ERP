<?php

declare(strict_types=1);

class CreatePayrollTables
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `salary_structures` (
                `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `employee_id`   INT UNSIGNED NOT NULL,
                `basic_salary`  DECIMAL(15,2) NOT NULL DEFAULT 0,
                `gross_salary`  DECIMAL(15,2) NOT NULL DEFAULT 0,
                `net_salary`    DECIMAL(15,2) NOT NULL DEFAULT 0,
                `effective_date` DATE NOT NULL,
                `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
                `created_by`    INT UNSIGNED,
                `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_salary_structures_employee_id` (`employee_id`),
                CONSTRAINT `fk_salary_structures_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `salary_components` (
                `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `salary_structure_id`  INT UNSIGNED NOT NULL,
                `component_type`       ENUM('allowance','deduction') NOT NULL,
                `name`                 VARCHAR(100) NOT NULL,
                `amount`               DECIMAL(15,2) NOT NULL DEFAULT 0,
                `percentage`           DECIMAL(5,2),
                `is_percentage`        TINYINT(1) NOT NULL DEFAULT 0,
                `is_taxable`           TINYINT(1) NOT NULL DEFAULT 0,
                `created_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_salary_components_structure_id` (`salary_structure_id`),
                CONSTRAINT `fk_salary_components_structure` FOREIGN KEY (`salary_structure_id`) REFERENCES `salary_structures` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `payroll` (
                `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `employee_id`      INT UNSIGNED NOT NULL,
                `branch_id`        INT UNSIGNED NOT NULL,
                `month`            TINYINT UNSIGNED NOT NULL,
                `year`             SMALLINT UNSIGNED NOT NULL,
                `basic_salary`     DECIMAL(15,2) NOT NULL DEFAULT 0,
                `total_allowances` DECIMAL(15,2) NOT NULL DEFAULT 0,
                `total_deductions` DECIMAL(15,2) NOT NULL DEFAULT 0,
                `gross_salary`     DECIMAL(15,2) NOT NULL DEFAULT 0,
                `tax_amount`       DECIMAL(15,2) NOT NULL DEFAULT 0,
                `net_salary`       DECIMAL(15,2) NOT NULL DEFAULT 0,
                `working_days`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `present_days`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `absent_days`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `overtime_hours`   DECIMAL(5,2) NOT NULL DEFAULT 0,
                `overtime_amount`  DECIMAL(15,2) NOT NULL DEFAULT 0,
                `status`           ENUM('draft','processed','paid','cancelled') NOT NULL DEFAULT 'draft',
                `payment_date`     DATE,
                `payment_method`   VARCHAR(50),
                `processed_by`     INT UNSIGNED,
                `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_payroll_emp_month_year` (`employee_id`, `month`, `year`),
                INDEX `idx_payroll_branch_month_year` (`branch_id`, `month`, `year`),
                INDEX `idx_payroll_status` (`status`),
                CONSTRAINT `fk_payroll_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
                CONSTRAINT `fk_payroll_branch`   FOREIGN KEY (`branch_id`)   REFERENCES `branches` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `payroll`");
        $pdo->exec("DROP TABLE IF EXISTS `salary_components`");
        $pdo->exec("DROP TABLE IF EXISTS `salary_structures`");
    }
}
