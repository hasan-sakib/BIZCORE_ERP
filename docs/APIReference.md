# API Reference — BizCore ERP

Base URL: `http://localhost:8080/api/v1`  
Content-Type: `application/json`  
Authentication: `Authorization: Bearer <jwt_token>`

## Response Envelope

All responses use a consistent envelope:

```json
{
  "success": true,
  "message": "Success",
  "data": { ... }
}
```

Error response:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": "Email is required"
  }
}
```

Paginated response:
```json
{
  "success": true,
  "message": "Success",
  "data": [ ... ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "total_pages": 8,
    "from": 1,
    "to": 20
  }
}
```

## HTTP Status Codes

| Code | Meaning |
|---|---|
| 200 | OK |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized (invalid/missing token) |
| 403 | Forbidden (insufficient permissions) |
| 404 | Not Found |
| 422 | Unprocessable Entity (validation error) |
| 423 | Locked (account locked) |
| 429 | Too Many Requests (rate limited) |
| 500 | Internal Server Error |

---

## Authentication

### POST `/auth/login`

```json
// Request
{
  "email": "admin@bizcore.io",
  "password": "Admin@1234"
}

// Response 200
{
  "success": true,
  "data": {
    "token": "eyJ...",
    "refresh_token": "eyJ...",
    "expires_in": 3600,
    "user": {
      "id": 1,
      "name": "System Admin",
      "email": "admin@bizcore.io",
      "role_id": 1,
      "branch_id": 1
    }
  }
}
```

### POST `/auth/logout`
Requires: `Authorization: Bearer <token>`  
Blacklists the provided JWT in Redis.

### POST `/auth/refresh`
```json
{ "refresh_token": "eyJ..." }
```

### GET `/auth/me`
Returns authenticated user profile.

---

## Products

### GET `/products`

Query params:
- `search` — Search name, SKU, barcode
- `category_id` — Filter by category
- `brand_id` — Filter by brand
- `page`, `per_page`, `sort`, `order`

### POST `/products`

```json
{
  "name": "Product Name",
  "category_id": 1,
  "unit_id": 1,
  "selling_price": 100.00,
  "purchase_price": 75.00,
  "vat_rate": 15,
  "is_vat_inclusive": 0,
  "reorder_point": 10,
  "barcode": "8901234567890"
}
```

### GET `/products/{id}`
Returns product with variants and per-warehouse stock levels.

### PUT `/products/{id}`
Partial update — omit fields to keep existing values.

### DELETE `/products/{id}`
Soft delete — sets `deleted_at` and `is_active = 0`.

### GET `/products/barcode/{barcode}`
Barcode/SKU lookup, cached in Redis for 1 hour.

---

## Inventory

### GET `/inventory`

Query params: `search`, `warehouse_id`, `low_stock=1`, `category_id`, `page`, `per_page`

### POST `/inventory/stock-in`

```json
{
  "product_id": 5,
  "warehouse_id": 1,
  "quantity": 100,
  "unit_cost": 75.00,
  "reference": "PO-2024-00001",
  "notes": "Initial stock"
}
```

### POST `/inventory/stock-out`

```json
{
  "product_id": 5,
  "warehouse_id": 1,
  "quantity": 10,
  "reference": "INV-2024-00001"
}
```

### POST `/inventory/adjust`

```json
{
  "product_id": 5,
  "warehouse_id": 1,
  "new_quantity": 95,
  "reason": "Physical count discrepancy"
}
```

### POST `/inventory/transfer`

```json
{
  "product_id": 5,
  "from_warehouse_id": 1,
  "to_warehouse_id": 2,
  "quantity": 20,
  "notes": "Transfer to branch warehouse"
}
```

### GET `/inventory/movements`

Query params: `product_id`, `warehouse_id`, `type` (in/out/transfer/adjustment), `from`, `to`, `page`, `per_page`

---

## Customers

### GET `/customers`

Query params: `search`, `type` (individual/corporate), `page`, `per_page`

### POST `/customers`

```json
{
  "name": "ABC Company Ltd",
  "email": "contact@abc.com",
  "phone": "01712345678",
  "type": "corporate",
  "billing_address": "123 Dhaka, Bangladesh",
  "credit_limit": 100000,
  "credit_period": 30,
  "vat_number": "VAT-12345"
}
```

### GET `/customers/{id}`
Returns customer with recent invoices and stats.

### GET `/customers/{id}/ledger`

Query params: `from` (date), `to` (date)

Returns interleaved invoice + payment transactions.

---

## Sales

### GET `/sales/orders`

Query params: `status` (draft/confirmed/processing/completed/cancelled), `customer_id`, `from`, `to`, `page`, `per_page`

### POST `/sales/orders`

```json
{
  "customer_id": 10,
  "items": [
    { "product_id": 5, "quantity": 2, "unit_price": 100 },
    { "product_id": 8, "quantity": 1, "unit_price": 250 }
  ],
  "discount_amount": 50,
  "notes": "Urgent delivery"
}
```

### GET `/sales/invoices`

Query params: `status` (draft/sent/partial/paid/cancelled), `customer_id`, `overdue=1`, `from`, `to`, `page`, `per_page`

### POST `/sales/invoices`

```json
{
  "customer_id": 10,
  "warehouse_id": 1,
  "due_date": "2024-12-31",
  "items": [
    {
      "product_id": 5,
      "quantity": 2,
      "unit_price": 100,
      "vat_rate": 15,
      "discount": 10
    }
  ],
  "discount_amount": 0,
  "notes": "Standard invoice"
}
```

Creating an invoice automatically calls `InventoryService::stockOut()` for each item.

### POST `/sales/payments`

```json
{
  "customer_id": 10,
  "amount": 5000.00,
  "method": "bkash",
  "reference": "BKASH-TXN-123456",
  "notes": "Payment received via bKash"
}
```

Payment is automatically allocated FIFO to oldest overdue invoices first.

---

## Employees

### GET `/employees`

Query params: `search`, `department_id`, `status` (active/inactive/on_leave/terminated), `page`, `per_page`

### POST `/employees`

```json
{
  "first_name": "Mohammed",
  "last_name": "Rahman",
  "email": "m.rahman@company.com",
  "phone": "01812345678",
  "date_of_joining": "2024-01-15",
  "department_id": 2,
  "designation_id": 5,
  "gender": "male",
  "date_of_birth": "1990-05-20",
  "national_id": "1234567890123",
  "present_address": "Dhaka, Bangladesh",
  "basic_salary": 30000
}
```

### POST `/employees/{id}/transfer`

```json
{
  "to_branch_id": 2,
  "transfer_date": "2024-06-01",
  "reason": "Business requirement"
}
```

### GET `/employees/{id}/attendance`

Query params: `from`, `to`, `page`, `per_page`

### POST `/employees/{id}/payroll`

```json
{
  "month": 6,
  "year": 2024
}
```

---

## Reports

### GET `/reports/dashboard`
Returns Redis-cached (5 min) dashboard metrics including revenue trend, top products, low stock alerts.

### GET `/reports/trial-balance`
Query: `as_of=2024-12-31`

### GET `/reports/income-statement`
Query: `from=2024-01-01&to=2024-12-31`

### GET `/reports/balance-sheet`
Query: `as_of=2024-12-31`

### GET `/reports/sales`
Query: `from=2024-06-01&to=2024-06-30`

Returns: summary, by_product (top 20), by_customer (top 20)

### GET `/reports/vat-return`
Query: `from=2024-06-01&to=2024-06-30`

Returns output VAT and net VAT payable.

---

## Rate Limits

| Route | Limit | Window |
|---|---|---|
| `/auth/login` | 5 requests | 5 minutes |
| `/api/*` | 60 requests | 1 minute |
| All others | 120 requests | 1 minute |

When rate limited, response is `429 Too Many Requests` with header:
```
Retry-After: 47
```
