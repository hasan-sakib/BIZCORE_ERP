# Database Design — BizCore ERP

## Overview

MySQL 8.0 with InnoDB engine. All tables use `utf8mb4` with `utf8mb4_unicode_ci` collation for full Unicode support (including Bangla script).

**Conventions:**
- All tables have `created_at`, `updated_at` (TIMESTAMP, auto-updated)
- Soft deletes via `deleted_at TIMESTAMP NULL`
- Audit columns: `created_by`, `updated_by` (FK to users.id, nullable)
- Primary keys: `INT UNSIGNED AUTO_INCREMENT`
- Foreign keys: always `INT UNSIGNED`, indexed
- Boolean columns: `TINYINT(1)` with `DEFAULT 1` or `DEFAULT 0`

## Schema Groups

### Group 1: Core (001-004)

#### `branches`
Multi-branch foundation. All major entities reference `branch_id`.

| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED PK | |
| name | VARCHAR(255) | |
| code | VARCHAR(20) UNIQUE | Auto-generated, e.g., `HQ-001` |
| address, city, country | VARCHAR | |
| phone, email | VARCHAR | |
| is_active | TINYINT(1) | Default 1 |

#### `roles`
RBAC roles. Each user has one role.

| Column | Type | Notes |
|---|---|---|
| id, name, slug | | slug: unique kebab-case |
| description | TEXT | |
| permissions | JSON | Array of permission strings |
| is_system | TINYINT(1) | System roles cannot be deleted |

#### `users`
Application users linked to branches and roles.

| Column | Type | Notes |
|---|---|---|
| id, branch_id, role_id | | FKs to branches, roles |
| name, email UNIQUE | | |
| password | VARCHAR(255) | bcrypt hash |
| phone, avatar | | |
| status | ENUM | active/inactive/suspended/pending |
| failed_login_attempts | INT | For lockout |
| locked_until | TIMESTAMP NULL | Lockout expiry |

#### `audit_logs`
Immutable audit trail for all entity changes.

| Column | Type | Notes |
|---|---|---|
| entity_type, entity_id | | e.g., 'Product', 5 |
| action | ENUM | created/updated/deleted/restored |
| changes | JSON | Before/after values |
| user_id, ip_address | | |

---

### Group 2: HRM (005)

#### `departments`
```
id, branch_id, name, head_employee_id (nullable), is_active
```

#### `designations`
```
id, branch_id, department_id, name, grade, is_active
```

#### `employees`
Core HR entity. ~35 columns including:
- Personal: `first_name`, `last_name`, `date_of_birth`, `gender`, `national_id`
- Address: `present_address`, `permanent_address`
- Employment: `employee_number` (UNIQUE), `date_of_joining`, `basic_salary`
- Status: ENUM `active/inactive/on_leave/terminated`
- FULLTEXT index on `(first_name, last_name, email, employee_number)`

#### `attendance`
```
UNIQUE KEY (employee_id, date)
Columns: check_in, check_out, status (present/absent/late/half_day), overtime_hours, leave_request_id
```

#### `leave_requests`
```
employee_id, leave_type_id, from_date, to_date, days, reason, status (pending/approved/rejected)
```

---

### Group 3: Payroll (006)

#### `salary_structures`
```
employee_id UNIQUE (one active structure per employee)
basic_salary, gross_salary, is_active
```

#### `salary_components`
```
salary_structure_id, name, component_type (basic/allowance/deduction/bonus), amount, is_percentage
```

#### `payroll`
```
UNIQUE KEY (employee_id, month, year)
gross_salary, basic_salary, overtime_amount, tax_amount, net_salary
status: draft/processed/paid/cancelled
```

---

### Group 4: CRM (007)

#### `customers`
```
branch_id, customer_code (UNIQUE), name, email, phone
type: individual/corporate
credit_limit, credit_period (days), balance (current outstanding)
FULLTEXT (name, email, phone, customer_code)
```

#### `suppliers`
```
branch_id, supplier_code (UNIQUE), name, contact_person, email, phone
opening_balance, current_balance
FULLTEXT (name, email, phone)
```

---

### Group 5: Products (008)

#### `categories`
Self-referential (`parent_id` FK to self) for hierarchical categories.

#### `products`
~25 columns including:
- `category_id`, `brand_id`, `unit_id`
- `sku` (UNIQUE), `barcode` (UNIQUE)
- `slug` (UNIQUE) for URL-friendly names
- `type`: simple/variable/service
- `purchase_price`, `selling_price`, `min_selling_price`
- `vat_rate`, `is_vat_inclusive`
- `reorder_point` for low-stock alerts
- `images` JSON, `attributes` JSON
- FULLTEXT on `(name, sku, barcode, description)`

#### `product_variants`
For variable products (size, color combinations):
```
product_id, sku (UNIQUE), attributes JSON, additional_price, is_active
```

---

### Group 6: Inventory (009)

#### `warehouses`
```
branch_id, name, location, is_active
```

#### `inventory`
Current stock levels per product/variant/warehouse:
```
UNIQUE KEY (product_id, variant_id, warehouse_id)
quantity DECIMAL(10,2), reserved_quantity DECIMAL(10,2)
avg_cost DECIMAL(10,4)  ← weighted average cost
```

#### `stock_movements`
Immutable ledger of all stock changes:
```
product_id, variant_id, warehouse_id
type: in/out/transfer/adjustment/return
quantity DECIMAL(10,2), unit_cost, reference, notes
```

---

### Group 7: Purchase (010)

#### `purchase_orders`
```
branch_id, supplier_id, po_number (UNIQUE)
status: draft/sent/partial/received/cancelled
subtotal, vat_amount, total_amount, paid_amount, balance
```

#### `goods_receipts`
```
purchase_order_id, grn_number (UNIQUE)
status: draft/partial/complete
```

#### `expenses`
```
branch_id, category_id, expense_number (UNIQUE)
amount, vat_amount, total_amount
status: draft/submitted/approved/rejected/paid
```

---

### Group 8: Sales (011)

#### `sales_orders`
```
branch_id, customer_id, order_number (UNIQUE)
status: draft/confirmed/processing/shipped/completed/cancelled
subtotal, discount_amount, vat_amount, total_amount
```

#### `invoices`
```
branch_id, customer_id, sales_order_id (nullable)
invoice_number (UNIQUE)
status: draft/sent/partial/paid/cancelled
invoice_date, due_date
subtotal, discount_amount, vat_amount, total_amount, paid_amount, balance
```

#### `payments`
```
branch_id, customer_id, payment_number (UNIQUE)
method: cash/bank/cheque/bkash/nagad/card
amount, status: pending/completed/failed/refunded
```

#### `payment_allocations`
FIFO invoice allocation:
```
payment_id, invoice_id, amount
```

---

### Group 9: Accounting (012)

#### `accounts`
Self-referential chart of accounts:
```
parent_id (nullable FK to self)
code (UNIQUE), name
type: asset/liability/equity/revenue/expense
normal_balance: debit/credit
balance DECIMAL(15,2)
is_system: 1 for built-in accounts (cannot delete)
```

#### `journal_entries`
```
entry_number (UNIQUE), date, description
type: general/sales/purchase/payroll/adjustment
status: draft/posted/reversed
reference_type, reference_id (polymorphic)
```

#### `journal_entry_lines`
Each journal entry must have balanced lines (sum of debits = sum of credits):
```
journal_entry_id, account_id
debit DECIMAL(15,2), credit DECIMAL(15,2)
description
```

---

## Indexing Strategy

All indexes are named with `idx_` prefix for clarity:

**Composite indexes** (most important for performance):
- `invoices`: `idx_branch_status_date (branch_id, status, invoice_date)`
- `employees`: `idx_branch_status (branch_id, status, deleted_at)`
- `inventory`: `idx_product_warehouse (product_id, warehouse_id)` (UNIQUE)
- `stock_movements`: `idx_product_date (product_id, created_at)`
- `payroll`: `idx_branch_month_year (branch_id, month, year)`

**FULLTEXT indexes** for search:
- `products`: `ft_product_search (name, sku, barcode, description)`
- `employees`: `ft_employee_search (first_name, last_name, email, employee_number)`
- `customers`: `ft_customer_search (name, email, phone, customer_code)`

## Double-Entry Accounting

Every financial transaction creates balanced journal entries:

```
Sales Invoice of ৳1,000 + 15% VAT = ৳1,150:
  DR Accounts Receivable    1,150.00
      CR Sales Revenue          1,000.00
      CR VAT Payable              150.00

Payment Received ৳1,150:
  DR Cash                   1,150.00
      CR Accounts Receivable    1,150.00
```

The `AccountingService::postEntry()` method validates `abs(total_debit - total_credit) < 0.01` before posting. If validation fails, a `ValidationException` is thrown and the transaction is rolled back.
