-- ============================================================
-- BizCore ERP — Sample Data Seed
-- Run: docker exec bizcore-mysql mysql -ubizcore -psecret bizcore_erp < database/seeds/sample_data.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Clear existing sample data to allow re-runs
DELETE FROM stock_movements; DELETE FROM inventory;
DELETE FROM goods_receipt_items; DELETE FROM goods_receipts;
DELETE FROM purchase_order_items; DELETE FROM purchase_orders;
DELETE FROM payment_allocations; DELETE FROM payments;
DELETE FROM invoice_items; DELETE FROM invoices;
DELETE FROM sales_order_items; DELETE FROM sales_orders;
DELETE FROM quotation_items; DELETE FROM quotations;
DELETE FROM expenses; DELETE FROM expense_categories;
DELETE FROM attendance; DELETE FROM employees;
DELETE FROM designations; DELETE FROM departments;
DELETE FROM suppliers; DELETE FROM customers;
DELETE FROM warehouses; DELETE FROM products;
DELETE FROM units; DELETE FROM brands; DELETE FROM categories;

-- ============================================================
-- 1. CATEGORIES
-- ============================================================
INSERT INTO categories (name, slug, description, status) VALUES
('Electronics',      'electronics',      'Electronic gadgets and devices',      'active'),
('Office Supplies',  'office-supplies',  'Stationery and office equipment',     'active'),
('Furniture',        'furniture',        'Office and home furniture',            'active'),
('Clothing',         'clothing',         'Apparel and accessories',              'active'),
('Food & Beverage',  'food-beverage',    'Consumables and refreshments',        'active'),
('Software',         'software',         'Licensed software products',           'active'),
('Accessories',      'accessories',      'General accessories',                  'active'),
('Books',            'books',            'Books and publications',               'active'),
('Tools',            'tools',            'Hardware tools and equipment',         'active'),
('Health & Safety',  'health-safety',    'Safety equipment and medical items',   'active');

-- ============================================================
-- 2. BRANDS
-- ============================================================
INSERT INTO brands (name, slug, description, status) VALUES
('Samsung',    'samsung',    'Samsung Electronics',        'active'),
('Apple',      'apple',      'Apple Inc.',                 'active'),
('Dell',       'dell',       'Dell Technologies',          'active'),
('HP',         'hp',         'Hewlett-Packard',            'active'),
('Sony',       'sony',       'Sony Corporation',           'active'),
('LG',         'lg',         'LG Electronics',             'active'),
('Lenovo',     'lenovo',     'Lenovo Group',               'active'),
('Asus',       'asus',       'ASUSTeK Computer',           'active'),
('Generic',    'generic',    'Generic / Unbranded',        'active'),
('Local Brand','local-brand','Locally manufactured items', 'active');

-- ============================================================
-- 3. UNITS
-- ============================================================
INSERT INTO units (name, abbreviation) VALUES
('Piece',      'pcs'),
('Kilogram',   'kg'),
('Gram',       'g'),
('Litre',      'L'),
('Metre',      'm'),
('Box',        'box'),
('Carton',     'ctn'),
('Dozen',      'dz'),
('Set',        'set'),
('Pair',       'pair');

-- ============================================================
-- 4. PRODUCTS  (category_id 1-10, brand_id 1-10, unit_id 1)
-- ============================================================
INSERT INTO products (category_id, brand_id, unit_id, name, slug, sku, type, purchase_price, selling_price, min_selling_price, reorder_point, is_active, created_by) VALUES
(1,  1,  1, 'Samsung Galaxy A54',       'samsung-galaxy-a54',       'SKU-001', 'simple', 28000.00, 35000.00, 32000.00, 5,  1, 1),
(1,  2,  1, 'Apple iPad 10th Gen',      'apple-ipad-10',            'SKU-002', 'simple', 55000.00, 68000.00, 63000.00, 3,  1, 1),
(1,  3,  1, 'Dell Inspiron Laptop',     'dell-inspiron-laptop',     'SKU-003', 'simple', 65000.00, 80000.00, 75000.00, 2,  1, 1),
(2,  9,  6, 'A4 Paper Ream 80gsm',      'a4-paper-ream-80gsm',      'SKU-004', 'simple',   350.00,   500.00,   450.00, 50, 1, 1),
(2,  9,  1, 'Ballpoint Pen Set',        'ballpoint-pen-set',        'SKU-005', 'simple',    80.00,   150.00,   120.00, 100,1, 1),
(3,  9,  9, 'Office Chair',             'office-chair',             'SKU-006', 'simple',  8000.00, 12000.00, 10000.00, 5,  1, 1),
(3,  9,  1, 'Study Table',              'study-table',              'SKU-007', 'simple', 12000.00, 18000.00, 16000.00, 3,  1, 1),
(4, 10,  1, 'Corporate T-Shirt',        'corporate-t-shirt',        'SKU-008', 'simple',   450.00,   800.00,   650.00, 20, 1, 1),
(1,  4,  1, 'HP LaserJet Printer',      'hp-laserjet-printer',      'SKU-009', 'simple', 18000.00, 25000.00, 22000.00, 3,  1, 1),
(6,  9,  1, 'Antivirus Software 1yr',   'antivirus-software-1yr',   'SKU-010', 'simple',   800.00,  1500.00,  1200.00, 10, 1, 1);

-- ============================================================
-- 5. WAREHOUSES
-- ============================================================
INSERT INTO warehouses (branch_id, name, code, address, is_primary, status) VALUES
(1, 'Main Warehouse Dhaka',       'WH-DHA-01', '123 Motijheel, Dhaka',          1, 'active'),
(1, 'Secondary Warehouse Dhaka',  'WH-DHA-02', '45 Mirpur Road, Dhaka',         0, 'active'),
(2, 'Chittagong Main Warehouse',  'WH-CTG-01', '78 CDA Avenue, Chittagong',     1, 'active'),
(2, 'Chittagong Port Warehouse',  'WH-CTG-02', '12 Port Access Road, Chittagong',0,'active'),
(1, 'Gulshan Distribution Hub',   'WH-DHA-03', '9 Gulshan-2, Dhaka',            0, 'active'),
(1, 'Uttara Storage Facility',    'WH-DHA-04', '3 Sector 7, Uttara, Dhaka',     0, 'active'),
(2, 'Agrabad Warehouse',          'WH-CTG-03', '22 Agrabad, Chittagong',        0, 'active'),
(1, 'Narayanganj Depot',          'WH-NAR-01', '5 B.B. Road, Narayanganj',      0, 'active'),
(1, 'Gazipur Warehouse',          'WH-GAZ-01', '17 Tongi, Gazipur',             0, 'active'),
(2, 'Comilla Storage',            'WH-COM-01', '33 Kandirpar, Comilla',         0, 'active');

-- ============================================================
-- 6. CUSTOMERS
-- ============================================================
INSERT INTO customers (branch_id, customer_code, name, email, phone, company_name, credit_limit, status, created_by) VALUES
(1,'CUST-001','Rahim Trading Co.',       'rahim@example.com',    '01711-111111','Rahim & Sons Ltd.',       50000.00,'active',1),
(1,'CUST-002','Karim Electronics',       'karim@example.com',    '01722-222222','Karim Electronics Ltd.',  80000.00,'active',1),
(1,'CUST-003','Nisha Fashion House',     'nisha@example.com',    '01733-333333','Nisha Garments',          30000.00,'active',1),
(1,'CUST-004','Ahmed Stationery Store',  'ahmed@example.com',    '01744-444444','Ahmed Bros.',             20000.00,'active',1),
(1,'CUST-005','Dhaka IT Solutions',      'dhaka.it@example.com', '01755-555555','Dhaka IT Pvt. Ltd.',     100000.00,'active',1),
(2,'CUST-006','Chittagong Mart',         'ctg.mart@example.com', '01766-666666','CTG Mart Ltd.',           60000.00,'active',1),
(2,'CUST-007','Port City Supplies',      'port.city@example.com','01777-777777','Port City Co.',           40000.00,'active',1),
(1,'CUST-008','BD Office Supplies',      'bdoffice@example.com', '01788-888888','BD Office Ltd.',          25000.00,'active',1),
(1,'CUST-009','TechWorld Bangladesh',    'techworld@example.com','01799-999999','TechWorld BD',            90000.00,'active',1),
(2,'CUST-010','Green Valley Traders',    'green@example.com',    '01800-100100','Green Valley Ltd.',       35000.00,'active',1);

-- ============================================================
-- 7. SUPPLIERS
-- ============================================================
INSERT INTO suppliers (branch_id, supplier_code, name, email, phone, company_name, credit_terms, status, created_by) VALUES
(1,'SUP-001','Galaxy Distributors',     'galaxy@supplier.com',   '01611-111111','Galaxy Dist. Ltd.',    30,'active',1),
(1,'SUP-002','Apple Authorized Dealer', 'apple.bd@supplier.com', '01622-222222','iStore Bangladesh',    45,'active',1),
(1,'SUP-003','Dell Bangladesh',         'dell.bd@supplier.com',  '01633-333333','Dell BD Pvt. Ltd.',    60,'active',1),
(1,'SUP-004','Paper World',             'paper@supplier.com',    '01644-444444','Paper World Ltd.',     15,'active',1),
(1,'SUP-005','Office Mate BD',          'officemate@supplier.com','01655-555555','Office Mate Co.',     30,'active',1),
(2,'SUP-006','Furniture Factory CTG',   'furn.ctg@supplier.com', '01666-666666','CTG Furniture Ltd.',  45,'active',1),
(2,'SUP-007','Garments Wholesale BD',   'garments@supplier.com', '01677-777777','BD Garments Co.',     30,'active',1),
(1,'SUP-008','HP Authorized Partner',   'hp.bd@supplier.com',    '01688-888888','HP Bangladesh',        60,'active',1),
(1,'SUP-009','Software Solutions BD',   'software@supplier.com', '01699-999999','SW Solutions Ltd.',   15,'active',1),
(2,'SUP-010','Electronics Hub CTG',     'elec.ctg@supplier.com', '01700-100100','Electronics Hub',     30,'active',1);

-- ============================================================
-- 8. DEPARTMENTS
-- ============================================================
INSERT INTO departments (branch_id, name, code, description, status) VALUES
(1,'Information Technology', 'IT',   'Software, hardware and IT support',         'active'),
(1,'Human Resources',        'HR',   'Recruitment, training and employee welfare', 'active'),
(1,'Finance & Accounts',     'FIN',  'Accounting, budgeting and financial control','active'),
(1,'Sales & Marketing',      'SAL',  'Sales operations and marketing campaigns',   'active'),
(1,'Procurement',            'PRO',  'Purchasing and vendor management',           'active'),
(2,'Operations',             'OPS',  'Day-to-day operations and logistics',        'active'),
(2,'Customer Service',       'CS',   'Client support and after-sales service',     'active'),
(1,'Research & Development', 'R&D',  'Product research and innovation',            'active'),
(1,'Administration',         'ADM',  'General administration and facilities',      'active'),
(2,'Warehouse & Logistics',  'WHL',  'Inventory and delivery management',          'active');

-- ============================================================
-- 9. DESIGNATIONS
-- ============================================================
INSERT INTO designations (department_id, name, code, level) VALUES
(1,'Software Engineer',        'SE',   3),
(1,'IT Manager',               'ITM',  5),
(2,'HR Executive',             'HRE',  3),
(2,'HR Manager',               'HRM',  5),
(3,'Accountant',               'ACC',  3),
(3,'Finance Manager',          'FM',   5),
(4,'Sales Executive',          'SEX',  3),
(4,'Sales Manager',            'SM',   5),
(5,'Procurement Officer',      'PO',   3),
(6,'Operations Supervisor',    'OS',   4);

-- ============================================================
-- 10. EMPLOYEES
-- ============================================================
INSERT INTO employees (employee_number, branch_id, department_id, designation_id, first_name, last_name, email, phone, gender, join_date, status, created_by) VALUES
('EMP-001',1,1,1,'Arif',    'Hossain',  'arif@bizcore.com',    '01711-001001','male',  '2023-01-15','active',1),
('EMP-002',1,2,3,'Fatima',  'Begum',    'fatima@bizcore.com',  '01711-002002','female','2023-02-01','active',1),
('EMP-003',1,3,5,'Kamal',   'Uddin',    'kamal@bizcore.com',   '01711-003003','male',  '2023-03-10','active',1),
('EMP-004',1,4,7,'Sumaiya', 'Khan',     'sumaiya@bizcore.com', '01711-004004','female','2023-04-05','active',1),
('EMP-005',1,5,9,'Rafiq',   'Islam',    'rafiq@bizcore.com',   '01711-005005','male',  '2023-05-20','active',1),
('EMP-006',2,6,10,'Nasrin', 'Akter',    'nasrin@bizcore.com',  '01711-006006','female','2023-06-01','active',1),
('EMP-007',2,7,7,'Jahir',   'Ahmed',    'jahir@bizcore.com',   '01711-007007','male',  '2023-07-15','active',1),
('EMP-008',1,1,2,'Rubel',   'Mia',      'rubel@bizcore.com',   '01711-008008','male',  '2023-08-10','active',1),
('EMP-009',1,3,6,'Shirin',  'Sultana',  'shirin@bizcore.com',  '01711-009009','female','2023-09-01','active',1),
('EMP-010',2,10,10,'Milon', 'Chowdhury','milon@bizcore.com',   '01711-010010','male',  '2023-10-01','active',1);

-- ============================================================
-- 11. ATTENDANCE  (last 10 days for EMP-001)
-- ============================================================
INSERT INTO attendance (employee_id, branch_id, date, check_in, check_out, working_hours, status, created_by) VALUES
(1,1,DATE_SUB(CURDATE(),INTERVAL 9 DAY),DATE_SUB(CURDATE(),INTERVAL 9 DAY) + INTERVAL 9 HOUR, DATE_SUB(CURDATE(),INTERVAL 9 DAY) + INTERVAL 18 HOUR,9.00,'present',1),
(1,1,DATE_SUB(CURDATE(),INTERVAL 8 DAY),DATE_SUB(CURDATE(),INTERVAL 8 DAY) + INTERVAL 9 HOUR, DATE_SUB(CURDATE(),INTERVAL 8 DAY) + INTERVAL 18 HOUR,9.00,'present',1),
(1,1,DATE_SUB(CURDATE(),INTERVAL 7 DAY),DATE_SUB(CURDATE(),INTERVAL 7 DAY) + INTERVAL 9 HOUR, DATE_SUB(CURDATE(),INTERVAL 7 DAY) + INTERVAL 18 HOUR,9.00,'present',1),
(1,1,DATE_SUB(CURDATE(),INTERVAL 6 DAY),NULL,NULL,0.00,'absent',1),
(1,1,DATE_SUB(CURDATE(),INTERVAL 5 DAY),DATE_SUB(CURDATE(),INTERVAL 5 DAY) + INTERVAL 10 HOUR,DATE_SUB(CURDATE(),INTERVAL 5 DAY) + INTERVAL 18 HOUR,8.00,'late',1),
(2,1,DATE_SUB(CURDATE(),INTERVAL 4 DAY),DATE_SUB(CURDATE(),INTERVAL 4 DAY) + INTERVAL 9 HOUR, DATE_SUB(CURDATE(),INTERVAL 4 DAY) + INTERVAL 18 HOUR,9.00,'present',1),
(2,1,DATE_SUB(CURDATE(),INTERVAL 3 DAY),DATE_SUB(CURDATE(),INTERVAL 3 DAY) + INTERVAL 9 HOUR, DATE_SUB(CURDATE(),INTERVAL 3 DAY) + INTERVAL 18 HOUR,9.00,'present',1),
(3,1,DATE_SUB(CURDATE(),INTERVAL 2 DAY),DATE_SUB(CURDATE(),INTERVAL 2 DAY) + INTERVAL 9 HOUR, DATE_SUB(CURDATE(),INTERVAL 2 DAY) + INTERVAL 17 HOUR,8.00,'present',1),
(4,1,DATE_SUB(CURDATE(),INTERVAL 1 DAY),DATE_SUB(CURDATE(),INTERVAL 1 DAY) + INTERVAL 9 HOUR, DATE_SUB(CURDATE(),INTERVAL 1 DAY) + INTERVAL 18 HOUR,9.00,'present',1),
(5,1,CURDATE(),CURDATE() + INTERVAL 9 HOUR,NULL,0.00,'present',1);

-- ============================================================
-- 12. EXPENSE CATEGORIES
-- ============================================================
INSERT INTO expense_categories (name, code, description) VALUES
('Office Supplies',    'EXP-OFF',  'Stationery, printing, and office items'),
('Travel & Transport', 'EXP-TRV',  'Business travel and transportation costs'),
('Utilities',          'EXP-UTL',  'Electricity, water, and internet bills'),
('Rent',               'EXP-RNT',  'Office and warehouse rental costs'),
('Salaries',           'EXP-SAL',  'Employee salary disbursements'),
('Marketing',          'EXP-MKT',  'Advertising and promotional expenses'),
('Maintenance',        'EXP-MNT',  'Repairs and maintenance'),
('IT & Software',      'EXP-ITS',  'Software licenses and IT services'),
('Miscellaneous',      'EXP-MSC',  'Other uncategorized expenses'),
('Training',           'EXP-TRN',  'Staff training and development');

-- ============================================================
-- 13. EXPENSES
-- ============================================================
INSERT INTO expenses (branch_id, category_id, expense_number, date, amount, vat_amount, total_amount, description, payment_method, status, created_by) VALUES
(1,1,'EXP-2024-001',DATE_SUB(CURDATE(),INTERVAL 30 DAY), 5000.00, 750.00, 5750.00,'Monthly office stationery purchase','cash','approved',1),
(1,2,'EXP-2024-002',DATE_SUB(CURDATE(),INTERVAL 28 DAY), 3500.00, 525.00, 4025.00,'Client visit travel expenses','cash','approved',1),
(1,3,'EXP-2024-003',DATE_SUB(CURDATE(),INTERVAL 25 DAY),12000.00,1800.00,13800.00,'Monthly electricity and internet bill','bank_transfer','approved',1),
(1,4,'EXP-2024-004',DATE_SUB(CURDATE(),INTERVAL 20 DAY),80000.00,    0.00,80000.00,'Office rent June 2024','bank_transfer','approved',1),
(1,6,'EXP-2024-005',DATE_SUB(CURDATE(),INTERVAL 18 DAY),15000.00,2250.00,17250.00,'Social media advertising campaign','bank_transfer','draft',1),
(2,3,'EXP-2024-006',DATE_SUB(CURDATE(),INTERVAL 15 DAY), 8500.00,1275.00, 9775.00,'Chittagong office utility bills','bank_transfer','approved',1),
(1,7,'EXP-2024-007',DATE_SUB(CURDATE(),INTERVAL 12 DAY), 4200.00, 630.00, 4830.00,'AC maintenance and repairs','cash','approved',1),
(1,8,'EXP-2024-008',DATE_SUB(CURDATE(),INTERVAL 10 DAY), 6000.00, 900.00, 6900.00,'Annual software license renewal','bank_transfer','approved',1),
(1,10,'EXP-2024-009',DATE_SUB(CURDATE(),INTERVAL 5 DAY), 9000.00,1350.00,10350.00,'Staff training workshop','bank_transfer','draft',1),
(2,9,'EXP-2024-010',DATE_SUB(CURDATE(),INTERVAL 2 DAY), 2500.00, 375.00, 2875.00,'Miscellaneous office expenses','cash','draft',1);

-- ============================================================
-- 14. INVENTORY (initial stock)
-- ============================================================
INSERT INTO inventory (product_id, warehouse_id, branch_id, quantity, avg_cost) VALUES
(1,1,1,50.0000,28000.00),(2,1,1,30.0000,55000.00),(3,1,1,20.0000,65000.00),
(4,1,1,500.0000,350.00), (5,1,1,300.0000,80.00),  (6,1,1,15.0000,8000.00),
(7,1,1,10.0000,12000.00),(8,1,1,100.0000,450.00), (9,1,1,25.0000,18000.00),
(10,1,1,80.0000,800.00);

-- ============================================================
-- 15. PURCHASE ORDERS
-- ============================================================
INSERT INTO purchase_orders (branch_id, supplier_id, po_number, order_date, expected_date, status, subtotal, vat_amount, total_amount, created_by) VALUES
(1,1,'PO-2024-001',DATE_SUB(CURDATE(),INTERVAL 45 DAY),DATE_SUB(CURDATE(),INTERVAL 35 DAY),'received',1400000.00,210000.00,1610000.00,1),
(1,2,'PO-2024-002',DATE_SUB(CURDATE(),INTERVAL 40 DAY),DATE_SUB(CURDATE(),INTERVAL 30 DAY),'received',1650000.00,247500.00,1897500.00,1),
(1,4,'PO-2024-003',DATE_SUB(CURDATE(),INTERVAL 35 DAY),DATE_SUB(CURDATE(),INTERVAL 25 DAY),'received',  52500.00,  7875.00,  60375.00,1),
(1,5,'PO-2024-004',DATE_SUB(CURDATE(),INTERVAL 30 DAY),DATE_SUB(CURDATE(),INTERVAL 20 DAY),'sent',       24000.00,  3600.00,  27600.00,1),
(1,8,'PO-2024-005',DATE_SUB(CURDATE(),INTERVAL 25 DAY),DATE_SUB(CURDATE(),INTERVAL 15 DAY),'received',  540000.00, 81000.00, 621000.00,1),
(2,10,'PO-2024-006',DATE_SUB(CURDATE(),INTERVAL 20 DAY),DATE_SUB(CURDATE(),INTERVAL 10 DAY),'sent',     840000.00,126000.00, 966000.00,1),
(1,3,'PO-2024-007',DATE_SUB(CURDATE(),INTERVAL 15 DAY),DATE_SUB(CURDATE(),INTERVAL 5 DAY),'draft',    1300000.00,195000.00,1495000.00,1),
(1,9,'PO-2024-008',DATE_SUB(CURDATE(),INTERVAL 10 DAY),DATE_ADD(CURDATE(),INTERVAL 5 DAY),'draft',      64000.00,  9600.00,  73600.00,1),
(2,6,'PO-2024-009',DATE_SUB(CURDATE(),INTERVAL 8 DAY), DATE_ADD(CURDATE(),INTERVAL 7 DAY),'draft',     360000.00, 54000.00, 414000.00,1),
(1,7,'PO-2024-010',DATE_SUB(CURDATE(),INTERVAL 5 DAY), DATE_ADD(CURDATE(),INTERVAL 10 DAY),'draft',    135000.00, 20250.00, 155250.00,1);

-- PO Items
INSERT INTO purchase_order_items (po_id, product_id, quantity, unit_price, vat_rate, vat_amount, total) VALUES
(1,1,50.0000,28000.0000,15.00,210000.00,1610000.00),
(2,2,30.0000,55000.0000,15.00,247500.00,1897500.00),
(3,4,150.0000,350.0000,15.00,7875.00,60375.00),
(4,5,300.0000,80.0000,15.00,3600.00,27600.00),
(5,9,30.0000,18000.0000,15.00,81000.00,621000.00),
(6,1,30.0000,28000.0000,15.00,126000.00,966000.00),
(7,3,20.0000,65000.0000,15.00,195000.00,1495000.00),
(8,10,80.0000,800.0000,15.00,9600.00,73600.00),
(9,6,30.0000,12000.0000,15.00,54000.00,414000.00),
(10,8,300.0000,450.0000,15.00,20250.00,155250.00);

-- ============================================================
-- 16. GOODS RECEIPTS
-- ============================================================
INSERT INTO goods_receipts (po_id, branch_id, supplier_id, grn_number, receipt_date, warehouse_id, status, subtotal, total_amount, created_by) VALUES
(1,1,1,'GRN-2024-001',DATE_SUB(CURDATE(),INTERVAL 35 DAY),1,'received',1400000.00,1610000.00,1),
(2,1,2,'GRN-2024-002',DATE_SUB(CURDATE(),INTERVAL 30 DAY),1,'received',1650000.00,1897500.00,1),
(3,1,4,'GRN-2024-003',DATE_SUB(CURDATE(),INTERVAL 25 DAY),1,'received',  52500.00,  60375.00,1),
(5,1,8,'GRN-2024-004',DATE_SUB(CURDATE(),INTERVAL 15 DAY),1,'received', 540000.00, 621000.00,1),
(NULL,2,10,'GRN-2024-005',DATE_SUB(CURDATE(),INTERVAL 10 DAY),3,'draft',420000.00, 483000.00,1),
(NULL,1,3,'GRN-2024-006',DATE_SUB(CURDATE(),INTERVAL 8 DAY),2,'draft', 650000.00, 747500.00,1),
(NULL,1,5,'GRN-2024-007',DATE_SUB(CURDATE(),INTERVAL 6 DAY),1,'draft',  24000.00,  27600.00,1),
(NULL,2,6,'GRN-2024-008',DATE_SUB(CURDATE(),INTERVAL 4 DAY),3,'draft', 240000.00, 276000.00,1),
(NULL,1,1,'GRN-2024-009',DATE_SUB(CURDATE(),INTERVAL 2 DAY),1,'draft', 560000.00, 644000.00,1),
(NULL,1,9,'GRN-2024-010',DATE_SUB(CURDATE(),INTERVAL 1 DAY),2,'draft',  48000.00,  55200.00,1);

INSERT INTO goods_receipt_items (grn_id, product_id, quantity, unit_cost, total) VALUES
(1,1,50.0000,28000.0000,1400000.00),(2,2,30.0000,55000.0000,1650000.00),
(3,4,150.0000,350.0000,52500.00),   (4,9,30.0000,18000.0000,540000.00),
(5,1,15.0000,28000.0000,420000.00), (6,3,10.0000,65000.0000,650000.00),
(7,5,300.0000,80.0000,24000.00),    (8,6,20.0000,12000.0000,240000.00),
(9,1,20.0000,28000.0000,560000.00), (10,10,60.0000,800.0000,48000.00);

-- ============================================================
-- 17. SALES QUOTATIONS
-- ============================================================
INSERT INTO quotations (branch_id, customer_id, quotation_number, date, expiry_date, status, subtotal, vat_amount, total_amount, created_by) VALUES
(1,1,'QT-2024-001',DATE_SUB(CURDATE(),INTERVAL 30 DAY),DATE_SUB(CURDATE(),INTERVAL 15 DAY),'accepted',  70000.00,10500.00,  80500.00,1),
(1,2,'QT-2024-002',DATE_SUB(CURDATE(),INTERVAL 28 DAY),DATE_SUB(CURDATE(),INTERVAL 13 DAY),'accepted', 136000.00,20400.00, 156400.00,1),
(1,5,'QT-2024-003',DATE_SUB(CURDATE(),INTERVAL 25 DAY),DATE_SUB(CURDATE(),INTERVAL 10 DAY),'sent',     160000.00,24000.00, 184000.00,1),
(1,4,'QT-2024-004',DATE_SUB(CURDATE(),INTERVAL 22 DAY),DATE_SUB(CURDATE(),INTERVAL 7 DAY),'expired',    15000.00, 2250.00,  17250.00,1),
(2,6,'QT-2024-005',DATE_SUB(CURDATE(),INTERVAL 20 DAY),DATE_ADD(CURDATE(),INTERVAL 5 DAY),'sent',       70000.00,10500.00,  80500.00,1),
(1,9,'QT-2024-006',DATE_SUB(CURDATE(),INTERVAL 18 DAY),DATE_ADD(CURDATE(),INTERVAL 7 DAY),'draft',     250000.00,37500.00, 287500.00,1),
(2,7,'QT-2024-007',DATE_SUB(CURDATE(),INTERVAL 15 DAY),DATE_ADD(CURDATE(),INTERVAL 10 DAY),'sent',      50000.00, 7500.00,  57500.00,1),
(1,3,'QT-2024-008',DATE_SUB(CURDATE(),INTERVAL 12 DAY),DATE_ADD(CURDATE(),INTERVAL 13 DAY),'draft',     24000.00, 3600.00,  27600.00,1),
(1,8,'QT-2024-009',DATE_SUB(CURDATE(),INTERVAL 8 DAY), DATE_ADD(CURDATE(),INTERVAL 17 DAY),'draft',    125000.00,18750.00, 143750.00,1),
(2,10,'QT-2024-010',DATE_SUB(CURDATE(),INTERVAL 5 DAY),DATE_ADD(CURDATE(),INTERVAL 20 DAY),'draft',     40000.00, 6000.00,  46000.00,1);

INSERT INTO quotation_items (quotation_id, product_id, quantity, unit_price, vat_rate, vat_amount, total) VALUES
(1,1,2.0000,35000.0000,15.00,10500.00,80500.00),
(2,2,2.0000,68000.0000,15.00,20400.00,156400.00),
(3,3,2.0000,80000.0000,15.00,24000.00,184000.00),
(4,4,30.0000,500.0000,15.00,2250.00,17250.00),
(5,1,2.0000,35000.0000,15.00,10500.00,80500.00),
(6,3,2.0000,80000.0000,15.00,12000.00,92000.00),
(7,9,2.0000,25000.0000,15.00,7500.00,57500.00),
(8,8,30.0000,800.0000,15.00,3600.00,27600.00),
(9,9,5.0000,25000.0000,15.00,18750.00,143750.00),
(10,8,50.0000,800.0000,15.00,6000.00,46000.00);

-- ============================================================
-- 18. SALES ORDERS
-- ============================================================
INSERT INTO sales_orders (branch_id, customer_id, order_number, order_date, expected_delivery, status, warehouse_id, subtotal, vat_amount, total_amount, created_by) VALUES
(1,1,'SO-2024-001',DATE_SUB(CURDATE(),INTERVAL 25 DAY),DATE_SUB(CURDATE(),INTERVAL 20 DAY),'delivered',1, 70000.00,10500.00, 80500.00,1),
(1,2,'SO-2024-002',DATE_SUB(CURDATE(),INTERVAL 22 DAY),DATE_SUB(CURDATE(),INTERVAL 17 DAY),'delivered',1,136000.00,20400.00,156400.00,1),
(1,5,'SO-2024-003',DATE_SUB(CURDATE(),INTERVAL 18 DAY),DATE_SUB(CURDATE(),INTERVAL 13 DAY),'shipped',  1,160000.00,24000.00,184000.00,1),
(2,6,'SO-2024-004',DATE_SUB(CURDATE(),INTERVAL 15 DAY),DATE_SUB(CURDATE(),INTERVAL 10 DAY),'processing',3, 70000.00,10500.00, 80500.00,1),
(1,9,'SO-2024-005',DATE_SUB(CURDATE(),INTERVAL 12 DAY),DATE_SUB(CURDATE(),INTERVAL 5 DAY), 'confirmed',1,250000.00,37500.00,287500.00,1),
(1,3,'SO-2024-006',DATE_SUB(CURDATE(),INTERVAL 10 DAY),DATE_ADD(CURDATE(),INTERVAL 2 DAY), 'confirmed',1, 24000.00, 3600.00, 27600.00,1),
(2,7,'SO-2024-007',DATE_SUB(CURDATE(),INTERVAL 8 DAY), DATE_ADD(CURDATE(),INTERVAL 5 DAY), 'draft',    3, 50000.00, 7500.00, 57500.00,1),
(1,4,'SO-2024-008',DATE_SUB(CURDATE(),INTERVAL 5 DAY), DATE_ADD(CURDATE(),INTERVAL 7 DAY), 'draft',    1, 15000.00, 2250.00, 17250.00,1),
(1,8,'SO-2024-009',DATE_SUB(CURDATE(),INTERVAL 3 DAY), DATE_ADD(CURDATE(),INTERVAL 10 DAY),'draft',    1,125000.00,18750.00,143750.00,1),
(2,10,'SO-2024-010',DATE_SUB(CURDATE(),INTERVAL 1 DAY),DATE_ADD(CURDATE(),INTERVAL 14 DAY),'draft',    3, 40000.00, 6000.00, 46000.00,1);

INSERT INTO sales_order_items (order_id, product_id, quantity, unit_price, vat_rate, vat_amount, total) VALUES
(1,1,2.0000,35000.0000,15.00,10500.00,80500.00),
(2,2,2.0000,68000.0000,15.00,20400.00,156400.00),
(3,3,2.0000,80000.0000,15.00,24000.00,184000.00),
(4,1,2.0000,35000.0000,15.00,10500.00,80500.00),
(5,3,2.0000,80000.0000,15.00,24000.00,184000.00),
(6,8,30.0000,800.0000,15.00,3600.00,27600.00),
(7,9,2.0000,25000.0000,15.00,7500.00,57500.00),
(8,4,30.0000,500.0000,15.00,2250.00,17250.00),
(9,9,5.0000,25000.0000,15.00,18750.00,143750.00),
(10,8,50.0000,800.0000,15.00,6000.00,46000.00);

-- ============================================================
-- 19. INVOICES
-- ============================================================
INSERT INTO invoices (branch_id, customer_id, sales_order_id, invoice_number, invoice_date, due_date, warehouse_id, subtotal, total_amount, paid_amount, balance, status, created_by) VALUES
(1,1,1,'INV-2024-001',DATE_SUB(CURDATE(),INTERVAL 22 DAY),DATE_SUB(CURDATE(),INTERVAL 7 DAY), 1, 70000.00, 80500.00,80500.00,     0.00,'paid',    1),
(1,2,2,'INV-2024-002',DATE_SUB(CURDATE(),INTERVAL 19 DAY),DATE_SUB(CURDATE(),INTERVAL 4 DAY), 1,136000.00,156400.00,80000.00, 76400.00,'partial',  1),
(1,5,3,'INV-2024-003',DATE_SUB(CURDATE(),INTERVAL 16 DAY),DATE_ADD(CURDATE(),INTERVAL 14 DAY),1,160000.00,184000.00,    0.00,184000.00,'sent',     1),
(2,6,4,'INV-2024-004',DATE_SUB(CURDATE(),INTERVAL 13 DAY),DATE_ADD(CURDATE(),INTERVAL 17 DAY),3, 70000.00, 80500.00,    0.00, 80500.00,'sent',     1),
(1,9,5,'INV-2024-005',DATE_SUB(CURDATE(),INTERVAL 10 DAY),DATE_ADD(CURDATE(),INTERVAL 20 DAY),1,250000.00,287500.00,    0.00,287500.00,'draft',    1),
(1,4,NULL,'INV-2024-006',DATE_SUB(CURDATE(),INTERVAL 8 DAY),DATE_ADD(CURDATE(),INTERVAL 22 DAY),1,15000.00,17250.00,17250.00,  0.00,'paid',      1),
(2,7,NULL,'INV-2024-007',DATE_SUB(CURDATE(),INTERVAL 6 DAY),DATE_ADD(CURDATE(),INTERVAL 24 DAY),3,50000.00,57500.00, 0.00, 57500.00,'draft',     1),
(1,3,NULL,'INV-2024-008',DATE_SUB(CURDATE(),INTERVAL 4 DAY),DATE_ADD(CURDATE(),INTERVAL 26 DAY),1,24000.00,27600.00, 0.00, 27600.00,'draft',     1),
(1,8,NULL,'INV-2024-009',DATE_SUB(CURDATE(),INTERVAL 2 DAY),DATE_ADD(CURDATE(),INTERVAL 28 DAY),1,125000.00,143750.00,0.00,143750.00,'draft',    1),
(2,10,NULL,'INV-2024-010',DATE_SUB(CURDATE(),INTERVAL 1 DAY),DATE_ADD(CURDATE(),INTERVAL 29 DAY),3,40000.00,46000.00, 0.00,46000.00,'draft',     1);

INSERT INTO invoice_items (invoice_id, product_id, quantity, unit_price, vat_rate, vat_amount, total) VALUES
(1,1,2.0000,35000.0000,15.00,10500.00,80500.00),
(2,2,2.0000,68000.0000,15.00,20400.00,156400.00),
(3,3,2.0000,80000.0000,15.00,24000.00,184000.00),
(4,1,2.0000,35000.0000,15.00,10500.00,80500.00),
(5,3,2.0000,80000.0000,15.00,24000.00,184000.00),
(6,4,30.0000,500.0000,15.00,2250.00,17250.00),
(7,9,2.0000,25000.0000,15.00,7500.00,57500.00),
(8,8,30.0000,800.0000,15.00,3600.00,27600.00),
(9,9,5.0000,25000.0000,15.00,18750.00,143750.00),
(10,8,50.0000,800.0000,15.00,6000.00,46000.00);

-- ============================================================
-- 20. PAYMENTS
-- ============================================================
INSERT INTO payments (branch_id, payment_type, payer_type, payer_id, payment_number, payment_date, amount, payment_method, status, created_by) VALUES
(1,'received','customer',1,'PAY-2024-001',DATE_SUB(CURDATE(),INTERVAL 20 DAY),80500.00,'bank_transfer','completed',1),
(1,'received','customer',2,'PAY-2024-002',DATE_SUB(CURDATE(),INTERVAL 17 DAY),80000.00,'bank_transfer','completed',1),
(1,'received','customer',4,'PAY-2024-003',DATE_SUB(CURDATE(),INTERVAL 6 DAY), 17250.00,'cash',         'completed',1),
(1,'made','supplier',    1,'PAY-2024-004',DATE_SUB(CURDATE(),INTERVAL 33 DAY),805000.00,'bank_transfer','completed',1),
(1,'made','supplier',    2,'PAY-2024-005',DATE_SUB(CURDATE(),INTERVAL 28 DAY),948750.00,'bank_transfer','completed',1),
(2,'received','customer',6,'PAY-2024-006',DATE_SUB(CURDATE(),INTERVAL 10 DAY),40000.00,'bkash',        'completed',1),
(1,'received','customer',5,'PAY-2024-007',DATE_SUB(CURDATE(),INTERVAL 8 DAY), 50000.00,'bank_transfer','completed',1),
(1,'made','supplier',    4,'PAY-2024-008',DATE_SUB(CURDATE(),INTERVAL 22 DAY),30187.50,'cash',         'completed',1),
(2,'made','supplier',   10,'PAY-2024-009',DATE_SUB(CURDATE(),INTERVAL 8 DAY),483000.00,'bank_transfer','completed',1),
(1,'received','customer',9,'PAY-2024-010',DATE_SUB(CURDATE(),INTERVAL 3 DAY),100000.00,'bank_transfer','completed',1);

-- ============================================================
-- 21. STOCK MOVEMENTS (opening stock)
-- ============================================================
INSERT INTO stock_movements (product_id, warehouse_id, branch_id, movement_type, reference_type, quantity, unit_cost, total_cost, balance_after, created_by) VALUES
(1,1,1,'opening','manual',50.0000,28000.0000,1400000.0000,50.0000,1),
(2,1,1,'opening','manual',30.0000,55000.0000,1650000.0000,30.0000,1),
(3,1,1,'opening','manual',20.0000,65000.0000,1300000.0000,20.0000,1),
(4,1,1,'opening','manual',500.0000,350.0000,175000.0000,500.0000,1),
(5,1,1,'opening','manual',300.0000,80.0000,24000.0000,300.0000,1),
(6,1,1,'opening','manual',15.0000,8000.0000,120000.0000,15.0000,1),
(7,1,1,'opening','manual',10.0000,12000.0000,120000.0000,10.0000,1),
(8,1,1,'opening','manual',100.0000,450.0000,45000.0000,100.0000,1),
(9,1,1,'opening','manual',25.0000,18000.0000,450000.0000,25.0000,1),
(10,1,1,'opening','manual',80.0000,800.0000,64000.0000,80.0000,1);

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Sample data inserted successfully!' AS result;
