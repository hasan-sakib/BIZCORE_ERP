<?php

declare(strict_types=1);

namespace Database\Seeders;

use PDO;
use RuntimeException;

/**
 * DemoDataSeeder
 *
 * Populates the database with realistic demo data for the BizCore ERP.
 * Covers: departments, designations, employees, customers, suppliers,
 * product categories, brands, units, products, warehouses, inventory,
 * purchase orders, sales orders, invoices, payments, attendance, and payroll.
 *
 * Must run after the core seeders (Branch, Role, User, Account, Settings).
 *
 * All monetary amounts are in BDT (Bangladeshi Taka).
 */
final class DemoDataSeeder
{
    /** @var array<string, int> Caches resolved IDs for cross-table references. */
    private array $ids = [];

    public function __construct(
        private readonly PDO $pdo,
    ) {}

    public function run(): void
    {
        $this->pdo->beginTransaction();

        try {
            $this->seedDepartments();
            $this->seedDesignations();
            $this->seedEmployees();
            $this->seedCustomers();
            $this->seedSuppliers();
            $this->seedCategories();
            $this->seedBrands();
            $this->seedUnits();
            $this->seedProducts();
            $this->seedWarehouses();
            $this->seedOpeningStock();
            $this->seedPurchaseOrders();
            $this->seedSalesOrders();
            $this->seedInvoices();
            $this->seedPayments();
            $this->seedAttendance();
            $this->seedPayroll();

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw new RuntimeException('DemoDataSeeder failed: ' . $e->getMessage(), 0, $e);
        }
    }

    // =========================================================================
    // Departments
    // =========================================================================

    private function seedDepartments(): void
    {
        $branchId = $this->resolveBranchId('HQ');
        $now      = date('Y-m-d H:i:s');

        $departments = [
            ['code' => 'DEPT-SALES', 'name' => 'Sales',      'description' => 'Manages customer relationships and revenue generation'],
            ['code' => 'DEPT-HR',    'name' => 'HR',         'description' => 'Human resources and talent management'],
            ['code' => 'DEPT-ACC',   'name' => 'Accounts',   'description' => 'Financial accounting and reporting'],
            ['code' => 'DEPT-IT',    'name' => 'IT',         'description' => 'Information technology and systems'],
            ['code' => 'DEPT-OPS',   'name' => 'Operations', 'description' => 'Day-to-day operational activities'],
        ];

        $sql = <<<'SQL'
            INSERT INTO departments (branch_id, code, name, description, is_active, created_at, updated_at)
            VALUES (:branch_id, :code, :name, :description, 1, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE name = VALUES(name), updated_at = VALUES(updated_at)
        SQL;

        $stmt = $this->pdo->prepare($sql);

        foreach ($departments as $dept) {
            $stmt->execute([
                'branch_id'   => $branchId,
                'code'        => $dept['code'],
                'name'        => $dept['name'],
                'description' => $dept['description'],
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $this->ids['dept_' . strtolower($dept['name'])] = (int) $this->pdo->lastInsertId()
                ?: $this->fetchId('departments', 'code', $dept['code']);
        }
    }

    // =========================================================================
    // Designations
    // =========================================================================

    private function seedDesignations(): void
    {
        $now = date('Y-m-d H:i:s');

        $designations = [
            ['dept' => 'sales',      'title' => 'Sales Manager',          'grade' => 'M2'],
            ['dept' => 'sales',      'title' => 'Sales Executive',        'grade' => 'E1'],
            ['dept' => 'hr',         'title' => 'HR Manager',             'grade' => 'M2'],
            ['dept' => 'hr',         'title' => 'HR Executive',           'grade' => 'E1'],
            ['dept' => 'accounts',   'title' => 'Chief Accountant',       'grade' => 'M1'],
            ['dept' => 'accounts',   'title' => 'Junior Accountant',      'grade' => 'E1'],
            ['dept' => 'it',         'title' => 'IT Manager',             'grade' => 'M2'],
            ['dept' => 'operations', 'title' => 'Operations Coordinator', 'grade' => 'E2'],
        ];

        $sql = <<<'SQL'
            INSERT INTO designations (department_id, title, grade, is_active, created_at, updated_at)
            VALUES (:department_id, :title, :grade, 1, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE title = VALUES(title), updated_at = VALUES(updated_at)
        SQL;

        $stmt = $this->pdo->prepare($sql);

        foreach ($designations as $idx => $des) {
            $deptKey = 'dept_' . $des['dept'];
            $stmt->execute([
                'department_id' => $this->ids[$deptKey],
                'title'         => $des['title'],
                'grade'         => $des['grade'],
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            $this->ids['desig_' . $idx] = (int) $this->pdo->lastInsertId();
        }
    }

    // =========================================================================
    // Employees
    // =========================================================================

    private function seedEmployees(): void
    {
        $branchId = $this->resolveBranchId('HQ');
        $now      = date('Y-m-d H:i:s');

        $employees = [
            [
                'employee_id'    => 'EMP-0001',
                'name'           => 'Rahim Uddin',
                'email'          => 'rahim.uddin@bizcore.local',
                'phone'          => '+8801811111101',
                'department'     => 'sales',
                'designation'    => 0,
                'gender'         => 'male',
                'date_of_birth'  => '1985-03-15',
                'join_date'      => '2020-01-10',
                'basic_salary'   => 85000.00,
                'nid'            => '1234567890001',
                'address'        => 'House 5, Road 12, Dhanmondi, Dhaka',
            ],
            [
                'employee_id'    => 'EMP-0002',
                'name'           => 'Nasrin Akter',
                'email'          => 'nasrin.akter@bizcore.local',
                'phone'          => '+8801811111102',
                'department'     => 'sales',
                'designation'    => 1,
                'gender'         => 'female',
                'date_of_birth'  => '1992-07-22',
                'join_date'      => '2021-03-01',
                'basic_salary'   => 45000.00,
                'nid'            => '1234567890002',
                'address'        => 'Flat 3A, Mirpur-10, Dhaka',
            ],
            [
                'employee_id'    => 'EMP-0003',
                'name'           => 'Karim Hossain',
                'email'          => 'karim.hossain@bizcore.local',
                'phone'          => '+8801811111103',
                'department'     => 'hr',
                'designation'    => 2,
                'gender'         => 'male',
                'date_of_birth'  => '1980-11-05',
                'join_date'      => '2019-06-15',
                'basic_salary'   => 90000.00,
                'nid'            => '1234567890003',
                'address'        => 'House 18, Gulshan-2, Dhaka',
            ],
            [
                'employee_id'    => 'EMP-0004',
                'name'           => 'Fatema Begum',
                'email'          => 'fatema.begum@bizcore.local',
                'phone'          => '+8801811111104',
                'department'     => 'hr',
                'designation'    => 3,
                'gender'         => 'female',
                'date_of_birth'  => '1993-04-18',
                'join_date'      => '2022-01-05',
                'basic_salary'   => 42000.00,
                'nid'            => '1234567890004',
                'address'        => 'Flat B2, Uttara, Dhaka',
            ],
            [
                'employee_id'    => 'EMP-0005',
                'name'           => 'Jamal Uddin',
                'email'          => 'jamal.uddin@bizcore.local',
                'phone'          => '+8801811111105',
                'department'     => 'accounts',
                'designation'    => 4,
                'gender'         => 'male',
                'date_of_birth'  => '1978-09-30',
                'join_date'      => '2018-09-01',
                'basic_salary'   => 100000.00,
                'nid'            => '1234567890005',
                'address'        => 'Road 6, Banani, Dhaka',
            ],
            [
                'employee_id'    => 'EMP-0006',
                'name'           => 'Rima Chowdhury',
                'email'          => 'rima.chowdhury@bizcore.local',
                'phone'          => '+8801811111106',
                'department'     => 'accounts',
                'designation'    => 5,
                'gender'         => 'female',
                'date_of_birth'  => '1995-01-12',
                'join_date'      => '2023-02-10',
                'basic_salary'   => 38000.00,
                'nid'            => '1234567890006',
                'address'        => 'Mohammadpur, Dhaka',
            ],
            [
                'employee_id'    => 'EMP-0007',
                'name'           => 'Tanvir Ahmed',
                'email'          => 'tanvir.ahmed@bizcore.local',
                'phone'          => '+8801811111107',
                'department'     => 'it',
                'designation'    => 6,
                'gender'         => 'male',
                'date_of_birth'  => '1987-06-25',
                'join_date'      => '2020-07-20',
                'basic_salary'   => 95000.00,
                'nid'            => '1234567890007',
                'address'        => 'Bashundhara R/A, Dhaka',
            ],
            [
                'employee_id'    => 'EMP-0008',
                'name'           => 'Shirin Sultana',
                'email'          => 'shirin.sultana@bizcore.local',
                'phone'          => '+8801811111108',
                'department'     => 'operations',
                'designation'    => 7,
                'gender'         => 'female',
                'date_of_birth'  => '1990-12-08',
                'join_date'      => '2021-11-01',
                'basic_salary'   => 55000.00,
                'nid'            => '1234567890008',
                'address'        => 'Tejgaon, Dhaka',
            ],
            [
                'employee_id'    => 'EMP-0009',
                'name'           => 'Arif Rahman',
                'email'          => 'arif.rahman@bizcore.local',
                'phone'          => '+8801811111109',
                'department'     => 'sales',
                'designation'    => 1,
                'gender'         => 'male',
                'date_of_birth'  => '1994-08-17',
                'join_date'      => '2022-08-15',
                'basic_salary'   => 40000.00,
                'nid'            => '1234567890009',
                'address'        => 'Shyamoli, Dhaka',
            ],
            [
                'employee_id'    => 'EMP-0010',
                'name'           => 'Meher Nigar',
                'email'          => 'meher.nigar@bizcore.local',
                'phone'          => '+8801811111110',
                'department'     => 'hr',
                'designation'    => 3,
                'gender'         => 'female',
                'date_of_birth'  => '1991-03-28',
                'join_date'      => '2023-05-01',
                'basic_salary'   => 40000.00,
                'nid'            => '1234567890010',
                'address'        => 'Khilgaon, Dhaka',
            ],
        ];

        $sql = <<<'SQL'
            INSERT INTO employees
                (branch_id, department_id, designation_id, employee_id, name, email, phone,
                 gender, date_of_birth, join_date, basic_salary, nid, address,
                 status, created_at, updated_at)
            VALUES
                (:branch_id, :department_id, :designation_id, :employee_id, :name, :email, :phone,
                 :gender, :date_of_birth, :join_date, :basic_salary, :nid, :address,
                 'active', :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                name       = VALUES(name),
                updated_at = VALUES(updated_at)
        SQL;

        $stmt = $this->pdo->prepare($sql);

        foreach ($employees as $idx => $emp) {
            $stmt->execute([
                'branch_id'      => $branchId,
                'department_id'  => $this->ids['dept_' . $emp['department']],
                'designation_id' => $this->ids['desig_' . $emp['designation']],
                'employee_id'    => $emp['employee_id'],
                'name'           => $emp['name'],
                'email'          => $emp['email'],
                'phone'          => $emp['phone'],
                'gender'         => $emp['gender'],
                'date_of_birth'  => $emp['date_of_birth'],
                'join_date'      => $emp['join_date'],
                'basic_salary'   => $emp['basic_salary'],
                'nid'            => $emp['nid'],
                'address'        => $emp['address'],
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);

            $this->ids['emp_' . $idx] = (int) $this->pdo->lastInsertId()
                ?: $this->fetchId('employees', 'employee_id', $emp['employee_id']);
        }
    }

    // =========================================================================
    // Customers
    // =========================================================================

    private function seedCustomers(): void
    {
        $branchId = $this->resolveBranchId('HQ');
        $now      = date('Y-m-d H:i:s');

        $customers = [
            [
                'code'    => 'CUST-0001',
                'name'    => 'Dhaka Electronics Ltd.',
                'contact' => 'Rafiqul Islam',
                'email'   => 'procurement@dhakaelectronics.com',
                'phone'   => '+8801911111201',
                'address' => 'Elephant Road, Dhaka',
                'credit'  => 500000.00,
            ],
            [
                'code'    => 'CUST-0002',
                'name'    => 'Star Traders',
                'contact' => 'Monir Hossain',
                'email'   => 'info@startraders.com.bd',
                'phone'   => '+8801911111202',
                'address' => 'Nawabpur Road, Dhaka',
                'credit'  => 300000.00,
            ],
            [
                'code'    => 'CUST-0003',
                'name'    => 'Chittagong Wholesale Hub',
                'contact' => 'Selim Khan',
                'email'   => 'selim@ctwholesale.bd',
                'phone'   => '+8801911111203',
                'address' => 'Khatungonj, Chittagong',
                'credit'  => 750000.00,
            ],
            [
                'code'    => 'CUST-0004',
                'name'    => 'Modern Office Solutions',
                'contact' => 'Afsana Parvin',
                'email'   => 'afsana@modernoffice.com.bd',
                'phone'   => '+8801911111204',
                'address' => 'Motijheel, Dhaka',
                'credit'  => 200000.00,
            ],
            [
                'code'    => 'CUST-0005',
                'name'    => 'Sylhet Trading Co.',
                'contact' => 'Habibur Rahman',
                'email'   => 'habib@sylhettrading.com',
                'phone'   => '+8801911111205',
                'address' => 'Zindabazar, Sylhet',
                'credit'  => 400000.00,
            ],
        ];

        $sql = <<<'SQL'
            INSERT INTO customers
                (branch_id, code, name, contact_person, email, phone, billing_address,
                 credit_limit, outstanding_balance, is_active, created_at, updated_at)
            VALUES
                (:branch_id, :code, :name, :contact_person, :email, :phone, :billing_address,
                 :credit_limit, 0.00, 1, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE name = VALUES(name), updated_at = VALUES(updated_at)
        SQL;

        $stmt = $this->pdo->prepare($sql);

        foreach ($customers as $idx => $cust) {
            $stmt->execute([
                'branch_id'      => $branchId,
                'code'           => $cust['code'],
                'name'           => $cust['name'],
                'contact_person' => $cust['contact'],
                'email'          => $cust['email'],
                'phone'          => $cust['phone'],
                'billing_address'=> $cust['address'],
                'credit_limit'   => $cust['credit'],
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);

            $this->ids['cust_' . $idx] = (int) $this->pdo->lastInsertId()
                ?: $this->fetchId('customers', 'code', $cust['code']);
        }
    }

    // =========================================================================
    // Suppliers
    // =========================================================================

    private function seedSuppliers(): void
    {
        $branchId = $this->resolveBranchId('HQ');
        $now      = date('Y-m-d H:i:s');

        $suppliers = [
            [
                'code'    => 'SUPP-0001',
                'name'    => 'Galaxy Electronics Import',
                'contact' => 'Zahir Raihan',
                'email'   => 'supply@galaxyimport.com.bd',
                'phone'   => '+8801611111301',
                'address' => 'Bangsal Road, Dhaka',
                'terms'   => 30,
            ],
            [
                'code'    => 'SUPP-0002',
                'name'    => 'Office Pro Supplies',
                'contact' => 'Sabina Yasmin',
                'email'   => 'orders@officepro.com.bd',
                'phone'   => '+8801611111302',
                'address' => 'Paltan, Dhaka',
                'terms'   => 15,
            ],
            [
                'code'    => 'SUPP-0003',
                'name'    => 'Furniture World BD',
                'contact' => 'Iqbal Hossain',
                'email'   => 'iqbal@furnitureworld.com.bd',
                'phone'   => '+8801611111303',
                'address' => 'Mohakhali, Dhaka',
                'terms'   => 45,
            ],
            [
                'code'    => 'SUPP-0004',
                'name'    => 'Tech Components Ltd.',
                'contact' => 'Khalid Mahmud',
                'email'   => 'khalid@techcomponents.bd',
                'phone'   => '+8801611111304',
                'address' => 'Agargaon, Dhaka',
                'terms'   => 30,
            ],
            [
                'code'    => 'SUPP-0005',
                'name'    => 'Pan Asian Distributors',
                'contact' => 'Nur Islam',
                'email'   => 'nur@panasian.com.bd',
                'phone'   => '+8801611111305',
                'address' => 'Agrabad, Chittagong',
                'terms'   => 60,
            ],
        ];

        $sql = <<<'SQL'
            INSERT INTO suppliers
                (branch_id, code, name, contact_person, email, phone, address,
                 payment_terms_days, outstanding_balance, is_active, created_at, updated_at)
            VALUES
                (:branch_id, :code, :name, :contact_person, :email, :phone, :address,
                 :payment_terms_days, 0.00, 1, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE name = VALUES(name), updated_at = VALUES(updated_at)
        SQL;

        $stmt = $this->pdo->prepare($sql);

        foreach ($suppliers as $idx => $supp) {
            $stmt->execute([
                'branch_id'          => $branchId,
                'code'               => $supp['code'],
                'name'               => $supp['name'],
                'contact_person'     => $supp['contact'],
                'email'              => $supp['email'],
                'phone'              => $supp['phone'],
                'address'            => $supp['address'],
                'payment_terms_days' => $supp['terms'],
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);

            $this->ids['supp_' . $idx] = (int) $this->pdo->lastInsertId()
                ?: $this->fetchId('suppliers', 'code', $supp['code']);
        }
    }

    // =========================================================================
    // Product Categories
    // =========================================================================

    private function seedCategories(): void
    {
        $now = date('Y-m-d H:i:s');

        $categories = [
            ['code' => 'CAT-ELEC', 'name' => 'Electronics',     'description' => 'Electronic devices and accessories'],
            ['code' => 'CAT-OFFC', 'name' => 'Office Supplies',  'description' => 'General office stationery and consumables'],
            ['code' => 'CAT-FURN', 'name' => 'Furniture',        'description' => 'Office and commercial furniture'],
        ];

        $sql = <<<'SQL'
            INSERT INTO categories (code, name, description, is_active, created_at, updated_at)
            VALUES (:code, :name, :description, 1, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE name = VALUES(name), updated_at = VALUES(updated_at)
        SQL;

        $stmt = $this->pdo->prepare($sql);

        foreach ($categories as $idx => $cat) {
            $stmt->execute([
                'code'        => $cat['code'],
                'name'        => $cat['name'],
                'description' => $cat['description'],
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $this->ids['cat_' . $idx] = (int) $this->pdo->lastInsertId()
                ?: $this->fetchId('categories', 'code', $cat['code']);
        }
    }

    // =========================================================================
    // Brands
    // =========================================================================

    private function seedBrands(): void
    {
        $now = date('Y-m-d H:i:s');

        $brands = [
            ['name' => 'Samsung',    'country' => 'South Korea'],
            ['name' => 'Walton',     'country' => 'Bangladesh'],
        ];

        $sql = <<<'SQL'
            INSERT INTO brands (name, country_of_origin, is_active, created_at, updated_at)
            VALUES (:name, :country_of_origin, 1, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE name = VALUES(name), updated_at = VALUES(updated_at)
        SQL;

        $stmt = $this->pdo->prepare($sql);

        foreach ($brands as $idx => $brand) {
            $stmt->execute([
                'name'              => $brand['name'],
                'country_of_origin' => $brand['country'],
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);

            $this->ids['brand_' . $idx] = (int) $this->pdo->lastInsertId()
                ?: $this->fetchId('brands', 'name', $brand['name']);
        }
    }

    // =========================================================================
    // Units of Measure
    // =========================================================================

    private function seedUnits(): void
    {
        $now = date('Y-m-d H:i:s');

        $sql = <<<'SQL'
            INSERT INTO units (name, abbreviation, is_active, created_at, updated_at)
            VALUES (:name, :abbreviation, 1, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE name = VALUES(name), updated_at = VALUES(updated_at)
        SQL;

        $this->pdo->prepare($sql)->execute([
            'name'         => 'Piece',
            'abbreviation' => 'PCS',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        $this->ids['unit_pcs'] = (int) $this->pdo->lastInsertId()
            ?: $this->fetchId('units', 'abbreviation', 'PCS');
    }

    // =========================================================================
    // Products
    // =========================================================================

    private function seedProducts(): void
    {
        $now = date('Y-m-d H:i:s');

        $products = [
            [
                'sku'         => 'PRD-ELEC-001',
                'name'        => 'Samsung Galaxy Tab A8',
                'description' => '10.5" Android tablet, 32GB storage',
                'category'    => 0,
                'brand'       => 0,
                'cost'        => 25000.00,
                'price'       => 32000.00,
                'vat_rate'    => 15.00,
                'reorder_qty' => 5,
            ],
            [
                'sku'         => 'PRD-ELEC-002',
                'name'        => 'Walton Laptop WOB315U',
                'description' => '15.6" laptop, Core i5, 8GB RAM, 512GB SSD',
                'category'    => 0,
                'brand'       => 1,
                'cost'        => 55000.00,
                'price'       => 68000.00,
                'vat_rate'    => 15.00,
                'reorder_qty' => 3,
            ],
            [
                'sku'         => 'PRD-ELEC-003',
                'name'        => 'Samsung 24" Monitor S24',
                'description' => '24 inch Full HD IPS monitor',
                'category'    => 0,
                'brand'       => 0,
                'cost'        => 18000.00,
                'price'       => 23000.00,
                'vat_rate'    => 15.00,
                'reorder_qty' => 5,
            ],
            [
                'sku'         => 'PRD-ELEC-004',
                'name'        => 'Wireless Keyboard & Mouse Combo',
                'description' => 'Ergonomic wireless keyboard and mouse set',
                'category'    => 0,
                'brand'       => 0,
                'cost'        => 1800.00,
                'price'       => 2500.00,
                'vat_rate'    => 15.00,
                'reorder_qty' => 10,
            ],
            [
                'sku'         => 'PRD-OFFC-001',
                'name'        => 'A4 Paper Ream (80GSM)',
                'description' => '500 sheets per ream, 80 GSM white paper',
                'category'    => 1,
                'brand'       => 0,
                'cost'        => 420.00,
                'price'       => 550.00,
                'vat_rate'    => 0.00,
                'reorder_qty' => 50,
            ],
            [
                'sku'         => 'PRD-OFFC-002',
                'name'        => 'Ballpoint Pen Box (Blue)',
                'description' => 'Box of 12 blue ballpoint pens',
                'category'    => 1,
                'brand'       => 0,
                'cost'        => 60.00,
                'price'       => 90.00,
                'vat_rate'    => 0.00,
                'reorder_qty' => 100,
            ],
            [
                'sku'         => 'PRD-OFFC-003',
                'name'        => 'Stapler Heavy Duty',
                'description' => 'Heavy duty stapler, 50-sheet capacity',
                'category'    => 1,
                'brand'       => 0,
                'cost'        => 350.00,
                'price'       => 480.00,
                'vat_rate'    => 15.00,
                'reorder_qty' => 20,
            ],
            [
                'sku'         => 'PRD-FURN-001',
                'name'        => 'Executive Office Chair',
                'description' => 'High-back ergonomic executive chair with lumbar support',
                'category'    => 2,
                'brand'       => 0,
                'cost'        => 12000.00,
                'price'       => 16500.00,
                'vat_rate'    => 15.00,
                'reorder_qty' => 5,
            ],
            [
                'sku'         => 'PRD-FURN-002',
                'name'        => 'L-Shape Office Desk',
                'description' => 'Large L-shaped workstation desk with cable management',
                'category'    => 2,
                'brand'       => 0,
                'cost'        => 22000.00,
                'price'       => 29000.00,
                'vat_rate'    => 15.00,
                'reorder_qty' => 3,
            ],
            [
                'sku'         => 'PRD-FURN-003',
                'name'        => '4-Drawer Filing Cabinet',
                'description' => 'Steel filing cabinet with lock, 4-drawer vertical',
                'category'    => 2,
                'brand'       => 0,
                'cost'        => 9500.00,
                'price'       => 13000.00,
                'vat_rate'    => 15.00,
                'reorder_qty' => 5,
            ],
        ];

        $sql = <<<'SQL'
            INSERT INTO products
                (sku, name, description, category_id, brand_id, unit_id,
                 cost_price, selling_price, vat_rate, reorder_quantity,
                 is_active, created_at, updated_at)
            VALUES
                (:sku, :name, :description, :category_id, :brand_id, :unit_id,
                 :cost_price, :selling_price, :vat_rate, :reorder_quantity,
                 1, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                name       = VALUES(name),
                updated_at = VALUES(updated_at)
        SQL;

        $stmt = $this->pdo->prepare($sql);

        foreach ($products as $idx => $prod) {
            $brandId = ($prod['brand'] === 0 && isset($this->ids['brand_0']))
                ? $this->ids['brand_' . $prod['brand']]
                : ($this->ids['brand_' . $prod['brand']] ?? null);

            $stmt->execute([
                'sku'              => $prod['sku'],
                'name'             => $prod['name'],
                'description'      => $prod['description'],
                'category_id'      => $this->ids['cat_' . $prod['category']],
                'brand_id'         => $brandId,
                'unit_id'          => $this->ids['unit_pcs'],
                'cost_price'       => $prod['cost'],
                'selling_price'    => $prod['price'],
                'vat_rate'         => $prod['vat_rate'],
                'reorder_quantity' => $prod['reorder_qty'],
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);

            $this->ids['prod_' . $idx] = (int) $this->pdo->lastInsertId()
                ?: $this->fetchId('products', 'sku', $prod['sku']);
        }
    }

    // =========================================================================
    // Warehouses
    // =========================================================================

    private function seedWarehouses(): void
    {
        $branchId = $this->resolveBranchId('HQ');
        $now      = date('Y-m-d H:i:s');

        $warehouses = [
            ['code' => 'WH-HQ-MAIN', 'name' => 'HQ Main Warehouse',     'address' => 'Gulshan-1, Dhaka'],
            ['code' => 'WH-HQ-SHOW', 'name' => 'HQ Showroom Storage',   'address' => 'Ground Floor, Gulshan-1'],
        ];

        $sql = <<<'SQL'
            INSERT INTO warehouses (branch_id, code, name, address, is_active, created_at, updated_at)
            VALUES (:branch_id, :code, :name, :address, 1, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE name = VALUES(name), updated_at = VALUES(updated_at)
        SQL;

        $stmt = $this->pdo->prepare($sql);

        foreach ($warehouses as $idx => $wh) {
            $stmt->execute([
                'branch_id'  => $branchId,
                'code'       => $wh['code'],
                'name'       => $wh['name'],
                'address'    => $wh['address'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->ids['wh_' . $idx] = (int) $this->pdo->lastInsertId()
                ?: $this->fetchId('warehouses', 'code', $wh['code']);
        }
    }

    // =========================================================================
    // Opening Stock
    // =========================================================================

    private function seedOpeningStock(): void
    {
        $now        = date('Y-m-d H:i:s');
        $warehouseId = $this->ids['wh_0'];

        // qty_on_hand for each product index 0-9
        $stockQtys = [20, 10, 15, 30, 200, 500, 80, 12, 8, 20];

        $sql = <<<'SQL'
            INSERT INTO inventory (product_id, warehouse_id, quantity_on_hand, quantity_reserved, created_at, updated_at)
            VALUES (:product_id, :warehouse_id, :quantity_on_hand, 0, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                quantity_on_hand = VALUES(quantity_on_hand),
                updated_at       = VALUES(updated_at)
        SQL;

        $stmt = $this->pdo->prepare($sql);

        foreach ($stockQtys as $idx => $qty) {
            $productId = $this->ids['prod_' . $idx] ?? null;
            if ($productId === null) {
                continue;
            }

            $stmt->execute([
                'product_id'       => $productId,
                'warehouse_id'     => $warehouseId,
                'quantity_on_hand' => $qty,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }
    }

    // =========================================================================
    // Purchase Orders
    // =========================================================================

    private function seedPurchaseOrders(): void
    {
        $branchId = $this->resolveBranchId('HQ');
        $now      = date('Y-m-d H:i:s');
        $userId   = $this->resolveUserId('super@bizcore.local');

        $orders = [
            [
                'number'     => 'PO-2024-0001',
                'supplier'   => 0,
                'date'       => '2024-01-15',
                'due'        => '2024-02-15',
                'status'     => 'received',
                'items'      => [
                    ['product' => 0, 'qty' => 10, 'unit_cost' => 25000.00],
                    ['product' => 1, 'qty' => 5,  'unit_cost' => 55000.00],
                ],
            ],
            [
                'number'     => 'PO-2024-0002',
                'supplier'   => 1,
                'date'       => '2024-02-01',
                'due'        => '2024-02-16',
                'status'     => 'received',
                'items'      => [
                    ['product' => 4, 'qty' => 100, 'unit_cost' => 420.00],
                    ['product' => 5, 'qty' => 200, 'unit_cost' => 60.00],
                    ['product' => 6, 'qty' => 30,  'unit_cost' => 350.00],
                ],
            ],
            [
                'number'     => 'PO-2024-0003',
                'supplier'   => 2,
                'date'       => '2024-03-10',
                'due'        => '2024-04-24',
                'status'     => 'pending',
                'items'      => [
                    ['product' => 7, 'qty' => 6,  'unit_cost' => 12000.00],
                    ['product' => 8, 'qty' => 4,  'unit_cost' => 22000.00],
                ],
            ],
            [
                'number'     => 'PO-2024-0004',
                'supplier'   => 3,
                'date'       => '2024-03-20',
                'due'        => '2024-04-19',
                'status'     => 'pending',
                'items'      => [
                    ['product' => 2, 'qty' => 8,  'unit_cost' => 18000.00],
                    ['product' => 3, 'qty' => 20, 'unit_cost' => 1800.00],
                ],
            ],
            [
                'number'     => 'PO-2024-0005',
                'supplier'   => 0,
                'date'       => '2024-04-05',
                'due'        => '2024-05-05',
                'status'     => 'partial',
                'items'      => [
                    ['product' => 0, 'qty' => 15, 'unit_cost' => 24500.00],
                    ['product' => 1, 'qty' => 8,  'unit_cost' => 54000.00],
                ],
            ],
        ];

        $poSql = <<<'SQL'
            INSERT INTO purchase_orders
                (branch_id, supplier_id, number, order_date, due_date, subtotal, vat_amount,
                 total_amount, status, created_by, created_at, updated_at)
            VALUES
                (:branch_id, :supplier_id, :number, :order_date, :due_date, :subtotal, :vat_amount,
                 :total_amount, :status, :created_by, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = VALUES(updated_at)
        SQL;

        $itemSql = <<<'SQL'
            INSERT INTO purchase_order_items
                (purchase_order_id, product_id, quantity, unit_cost, subtotal, created_at, updated_at)
            VALUES
                (:purchase_order_id, :product_id, :quantity, :unit_cost, :subtotal, :created_at, :updated_at)
        SQL;

        $poStmt   = $this->pdo->prepare($poSql);
        $itemStmt = $this->pdo->prepare($itemSql);

        foreach ($orders as $idx => $order) {
            $subtotal = 0.0;
            foreach ($order['items'] as $item) {
                $subtotal += $item['qty'] * $item['unit_cost'];
            }
            $vatAmount   = $subtotal * 0.00;  // PO typically excludes output VAT
            $totalAmount = $subtotal + $vatAmount;

            $poStmt->execute([
                'branch_id'   => $branchId,
                'supplier_id' => $this->ids['supp_' . $order['supplier']],
                'number'      => $order['number'],
                'order_date'  => $order['date'],
                'due_date'    => $order['due'],
                'subtotal'    => $subtotal,
                'vat_amount'  => $vatAmount,
                'total_amount'=> $totalAmount,
                'status'      => $order['status'],
                'created_by'  => $userId,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $poId = (int) $this->pdo->lastInsertId()
                ?: $this->fetchId('purchase_orders', 'number', $order['number']);

            $this->ids['po_' . $idx] = $poId;

            foreach ($order['items'] as $item) {
                $itemStmt->execute([
                    'purchase_order_id' => $poId,
                    'product_id'        => $this->ids['prod_' . $item['product']],
                    'quantity'          => $item['qty'],
                    'unit_cost'         => $item['unit_cost'],
                    'subtotal'          => $item['qty'] * $item['unit_cost'],
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            }
        }
    }

    // =========================================================================
    // Sales Orders
    // =========================================================================

    private function seedSalesOrders(): void
    {
        $branchId = $this->resolveBranchId('HQ');
        $now      = date('Y-m-d H:i:s');
        $userId   = $this->resolveUserId('sales@bizcore.local');

        $orders = [
            [
                'number'   => 'SO-2024-0001',
                'customer' => 0,
                'date'     => '2024-02-01',
                'status'   => 'invoiced',
                'items'    => [
                    ['product' => 0, 'qty' => 2, 'price' => 32000.00, 'vat' => 15.0],
                    ['product' => 2, 'qty' => 2, 'price' => 23000.00, 'vat' => 15.0],
                ],
            ],
            [
                'number'   => 'SO-2024-0002',
                'customer' => 1,
                'date'     => '2024-02-15',
                'status'   => 'invoiced',
                'items'    => [
                    ['product' => 4, 'qty' => 20, 'price' => 550.00,  'vat' => 0.0],
                    ['product' => 5, 'qty' => 50, 'price' => 90.00,   'vat' => 0.0],
                    ['product' => 6, 'qty' => 10, 'price' => 480.00,  'vat' => 15.0],
                ],
            ],
            [
                'number'   => 'SO-2024-0003',
                'customer' => 2,
                'date'     => '2024-03-01',
                'status'   => 'invoiced',
                'items'    => [
                    ['product' => 7, 'qty' => 3,  'price' => 16500.00, 'vat' => 15.0],
                    ['product' => 8, 'qty' => 2,  'price' => 29000.00, 'vat' => 15.0],
                    ['product' => 9, 'qty' => 4,  'price' => 13000.00, 'vat' => 15.0],
                ],
            ],
            [
                'number'   => 'SO-2024-0004',
                'customer' => 3,
                'date'     => '2024-03-20',
                'status'   => 'confirmed',
                'items'    => [
                    ['product' => 1, 'qty' => 2,  'price' => 68000.00, 'vat' => 15.0],
                    ['product' => 3, 'qty' => 5,  'price' => 2500.00,  'vat' => 15.0],
                ],
            ],
            [
                'number'   => 'SO-2024-0005',
                'customer' => 4,
                'date'     => '2024-04-10',
                'status'   => 'pending',
                'items'    => [
                    ['product' => 0, 'qty' => 5,  'price' => 32000.00, 'vat' => 15.0],
                    ['product' => 2, 'qty' => 3,  'price' => 23000.00, 'vat' => 15.0],
                ],
            ],
        ];

        $soSql = <<<'SQL'
            INSERT INTO sales_orders
                (branch_id, customer_id, number, order_date, subtotal, vat_amount,
                 total_amount, status, created_by, created_at, updated_at)
            VALUES
                (:branch_id, :customer_id, :number, :order_date, :subtotal, :vat_amount,
                 :total_amount, :status, :created_by, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = VALUES(updated_at)
        SQL;

        $itemSql = <<<'SQL'
            INSERT INTO sales_order_items
                (sales_order_id, product_id, quantity, unit_price, vat_rate, vat_amount, subtotal, created_at, updated_at)
            VALUES
                (:sales_order_id, :product_id, :quantity, :unit_price, :vat_rate, :vat_amount, :subtotal, :created_at, :updated_at)
        SQL;

        $soStmt   = $this->pdo->prepare($soSql);
        $itemStmt = $this->pdo->prepare($itemSql);

        foreach ($orders as $idx => $order) {
            $subtotal  = 0.0;
            $vatAmount = 0.0;

            foreach ($order['items'] as $item) {
                $lineSubtotal = $item['qty'] * $item['price'];
                $lineVat      = $lineSubtotal * ($item['vat'] / 100);
                $subtotal    += $lineSubtotal;
                $vatAmount   += $lineVat;
            }

            $soStmt->execute([
                'branch_id'    => $branchId,
                'customer_id'  => $this->ids['cust_' . $order['customer']],
                'number'       => $order['number'],
                'order_date'   => $order['date'],
                'subtotal'     => $subtotal,
                'vat_amount'   => $vatAmount,
                'total_amount' => $subtotal + $vatAmount,
                'status'       => $order['status'],
                'created_by'   => $userId,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            $soId = (int) $this->pdo->lastInsertId()
                ?: $this->fetchId('sales_orders', 'number', $order['number']);

            $this->ids['so_' . $idx] = $soId;

            foreach ($order['items'] as $item) {
                $lineSubtotal = $item['qty'] * $item['price'];
                $lineVat      = $lineSubtotal * ($item['vat'] / 100);

                $itemStmt->execute([
                    'sales_order_id' => $soId,
                    'product_id'     => $this->ids['prod_' . $item['product']],
                    'quantity'       => $item['qty'],
                    'unit_price'     => $item['price'],
                    'vat_rate'       => $item['vat'],
                    'vat_amount'     => $lineVat,
                    'subtotal'       => $lineSubtotal + $lineVat,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }
        }
    }

    // =========================================================================
    // Invoices
    // =========================================================================

    private function seedInvoices(): void
    {
        $branchId = $this->resolveBranchId('HQ');
        $now      = date('Y-m-d H:i:s');
        $userId   = $this->resolveUserId('accountant@bizcore.local');

        $invoices = [
            [
                'number'      => 'INV-2024-0001',
                'so_idx'      => 0,
                'customer'    => 0,
                'date'        => '2024-02-03',
                'due'         => '2024-03-03',
                'subtotal'    => 110000.00,
                'vat'         => 16500.00,
                'total'       => 126500.00,
                'paid'        => 126500.00,
                'status'      => 'paid',
            ],
            [
                'number'      => 'INV-2024-0002',
                'so_idx'      => 1,
                'customer'    => 1,
                'date'        => '2024-02-17',
                'due'         => '2024-03-17',
                'subtotal'    => 20300.00,
                'vat'         => 720.00,
                'total'       => 21020.00,
                'paid'        => 10000.00,
                'status'      => 'partial',
            ],
            [
                'number'      => 'INV-2024-0003',
                'so_idx'      => 2,
                'customer'    => 2,
                'date'        => '2024-03-05',
                'due'         => '2024-04-04',
                'subtotal'    => 159500.00,
                'vat'         => 23925.00,
                'total'       => 183425.00,
                'paid'        => 0.00,
                'status'      => 'pending',
            ],
        ];

        $sql = <<<'SQL'
            INSERT INTO invoices
                (branch_id, sales_order_id, customer_id, number, invoice_date, due_date,
                 subtotal, vat_amount, total_amount, paid_amount, status,
                 created_by, created_at, updated_at)
            VALUES
                (:branch_id, :sales_order_id, :customer_id, :number, :invoice_date, :due_date,
                 :subtotal, :vat_amount, :total_amount, :paid_amount, :status,
                 :created_by, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE status = VALUES(status), paid_amount = VALUES(paid_amount), updated_at = VALUES(updated_at)
        SQL;

        $stmt = $this->pdo->prepare($sql);

        foreach ($invoices as $idx => $inv) {
            $stmt->execute([
                'branch_id'      => $branchId,
                'sales_order_id' => $this->ids['so_' . $inv['so_idx']] ?? null,
                'customer_id'    => $this->ids['cust_' . $inv['customer']],
                'number'         => $inv['number'],
                'invoice_date'   => $inv['date'],
                'due_date'       => $inv['due'],
                'subtotal'       => $inv['subtotal'],
                'vat_amount'     => $inv['vat'],
                'total_amount'   => $inv['total'],
                'paid_amount'    => $inv['paid'],
                'status'         => $inv['status'],
                'created_by'     => $userId,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);

            $this->ids['inv_' . $idx] = (int) $this->pdo->lastInsertId()
                ?: $this->fetchId('invoices', 'number', $inv['number']);
        }
    }

    // =========================================================================
    // Payments
    // =========================================================================

    private function seedPayments(): void
    {
        $branchId = $this->resolveBranchId('HQ');
        $now      = date('Y-m-d H:i:s');
        $userId   = $this->resolveUserId('accountant@bizcore.local');
        $cashAccId = $this->fetchId('accounts', 'code', '1020');

        $payments = [
            [
                'number'   => 'PAY-2024-0001',
                'inv_idx'  => 0,
                'customer' => 0,
                'amount'   => 126500.00,
                'method'   => 'bank_transfer',
                'ref'      => 'TXN-SCB-20240205',
                'date'     => '2024-02-05',
                'note'     => 'Full payment for INV-2024-0001',
            ],
            [
                'number'   => 'PAY-2024-0002',
                'inv_idx'  => 1,
                'customer' => 1,
                'amount'   => 10000.00,
                'method'   => 'bkash',
                'ref'      => 'BKASH-TXN-20240220',
                'date'     => '2024-02-20',
                'note'     => 'Partial payment for INV-2024-0002',
            ],
        ];

        $sql = <<<'SQL'
            INSERT INTO payments
                (branch_id, invoice_id, customer_id, account_id, number, payment_date,
                 amount, payment_method, reference, notes, status,
                 created_by, created_at, updated_at)
            VALUES
                (:branch_id, :invoice_id, :customer_id, :account_id, :number, :payment_date,
                 :amount, :payment_method, :reference, :notes, 'completed',
                 :created_by, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE amount = VALUES(amount), updated_at = VALUES(updated_at)
        SQL;

        $stmt = $this->pdo->prepare($sql);

        foreach ($payments as $pay) {
            $stmt->execute([
                'branch_id'      => $branchId,
                'invoice_id'     => $this->ids['inv_' . $pay['inv_idx']],
                'customer_id'    => $this->ids['cust_' . $pay['customer']],
                'account_id'     => $cashAccId,
                'number'         => $pay['number'],
                'payment_date'   => $pay['date'],
                'amount'         => $pay['amount'],
                'payment_method' => $pay['method'],
                'reference'      => $pay['ref'],
                'notes'          => $pay['note'],
                'created_by'     => $userId,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }
    }

    // =========================================================================
    // Attendance (last 7 days)
    // =========================================================================

    private function seedAttendance(): void
    {
        $now = date('Y-m-d H:i:s');

        $sql = <<<'SQL'
            INSERT INTO attendance
                (employee_id, date, check_in, check_out, status, working_hours, created_at, updated_at)
            VALUES
                (:employee_id, :date, :check_in, :check_out, :status, :working_hours, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                check_out     = VALUES(check_out),
                working_hours = VALUES(working_hours),
                updated_at    = VALUES(updated_at)
        SQL;

        $stmt = $this->pdo->prepare($sql);

        for ($day = 6; $day >= 0; $day--) {
            $date    = date('Y-m-d', strtotime("-{$day} days"));
            $weekday = (int) date('N', strtotime($date));

            // Skip weekends (6=Saturday, 7=Sunday for Bangladesh)
            if ($weekday >= 6) {
                continue;
            }

            for ($empIdx = 0; $empIdx < 10; $empIdx++) {
                $empId = $this->ids['emp_' . $empIdx] ?? null;
                if ($empId === null) {
                    continue;
                }

                // Simulate occasional absences (emp 3 absent on day 1)
                if ($empIdx === 3 && $day === 1) {
                    $stmt->execute([
                        'employee_id'  => $empId,
                        'date'         => $date,
                        'check_in'     => null,
                        'check_out'    => null,
                        'status'       => 'absent',
                        'working_hours'=> 0,
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ]);
                    continue;
                }

                // Normal attendance with slight variation
                $checkInOffset  = random_int(-15, 20);  // ±15 min
                $checkOutOffset = random_int(-10, 30);  // ±10 min
                $checkIn        = date('H:i:s', strtotime("09:00:00") + ($checkInOffset * 60));
                $checkOut       = date('H:i:s', strtotime("18:00:00") + ($checkOutOffset * 60));
                $workingHours   = round((strtotime($checkOut) - strtotime($checkIn)) / 3600, 2);
                $status         = ($checkInOffset > 10) ? 'late' : 'present';

                $stmt->execute([
                    'employee_id'  => $empId,
                    'date'         => $date,
                    'check_in'     => $checkIn,
                    'check_out'    => $checkOut,
                    'status'       => $status,
                    'working_hours'=> $workingHours,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }
        }
    }

    // =========================================================================
    // Payroll (current month, draft)
    // =========================================================================

    private function seedPayroll(): void
    {
        $now          = date('Y-m-d H:i:s');
        $currentMonth = date('Y-m');
        $periodStart  = date('Y-m-01');
        $periodEnd    = date('Y-m-t');
        $userId       = $this->resolveUserId('hr@bizcore.local');
        $branchId     = $this->resolveBranchId('HQ');

        $sql = <<<'SQL'
            INSERT INTO payroll
                (branch_id, employee_id, period_start, period_end, basic_salary,
                 house_rent_allowance, medical_allowance, transport_allowance,
                 gross_salary, tax_deduction, other_deduction, net_salary,
                 status, created_by, created_at, updated_at)
            VALUES
                (:branch_id, :employee_id, :period_start, :period_end, :basic_salary,
                 :hra, :medical, :transport,
                 :gross_salary, :tax_deduction, :other_deduction, :net_salary,
                 'draft', :created_by, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                net_salary = VALUES(net_salary),
                updated_at = VALUES(updated_at)
        SQL;

        $stmt = $this->pdo->prepare($sql);

        // Basic salary figures per employee index
        $basicSalaries = [85000, 45000, 90000, 42000, 100000, 38000, 95000, 55000, 40000, 40000];

        for ($empIdx = 0; $empIdx < 10; $empIdx++) {
            $empId = $this->ids['emp_' . $empIdx] ?? null;
            if ($empId === null) {
                continue;
            }

            $basic     = (float) $basicSalaries[$empIdx];
            $hra       = round($basic * 0.40, 2);      // 40% HRA
            $medical   = round($basic * 0.10, 2);      // 10% Medical
            $transport = round($basic * 0.05, 2);      // 5% Transport
            $gross     = $basic + $hra + $medical + $transport;

            // Simplified income tax (5% above 50,000 gross)
            $taxable    = max(0, $gross - 50000);
            $tax        = round($taxable * 0.05, 2);
            $otherDed   = 0.00;
            $net        = $gross - $tax - $otherDed;

            $stmt->execute([
                'branch_id'       => $branchId,
                'employee_id'     => $empId,
                'period_start'    => $periodStart,
                'period_end'      => $periodEnd,
                'basic_salary'    => $basic,
                'hra'             => $hra,
                'medical'         => $medical,
                'transport'       => $transport,
                'gross_salary'    => $gross,
                'tax_deduction'   => $tax,
                'other_deduction' => $otherDed,
                'net_salary'      => $net,
                'created_by'      => $userId,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function resolveBranchId(string $code): int
    {
        if (!isset($this->ids['branch_' . $code])) {
            $this->ids['branch_' . $code] = $this->fetchId('branches', 'code', $code)
                ?? throw new RuntimeException("Branch '{$code}' not found. Run BranchSeeder first.");
        }

        return $this->ids['branch_' . $code];
    }

    private function resolveUserId(string $email): int
    {
        $cacheKey = 'user_' . md5($email);
        if (!isset($this->ids[$cacheKey])) {
            $this->ids[$cacheKey] = $this->fetchId('users', 'email', $email)
                ?? throw new RuntimeException("User '{$email}' not found. Run UserSeeder first.");
        }

        return $this->ids[$cacheKey];
    }

    private function fetchId(string $table, string $column, string $value): ?int
    {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM `{$table}` WHERE `{$column}` = :val LIMIT 1",
        );
        $stmt->execute(['val' => $value]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? (int) $row['id'] : null;
    }
}
