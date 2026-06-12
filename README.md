# BizCore ERP

**Multi-Branch Enterprise Resource Planning Platform — Production-Grade PHP 8.3**

A complete, modular ERP system covering HR Management, Inventory, Sales, Procurement, Accounting, CRM, and Analytics — built for Bangladeshi SMEs with bKash/Nagad integration, 15% VAT handling, and Bangla language support.

---

## Table of Contents

1. [Overview](#overview)
2. [Key Features](#key-features)
3. [Architecture](#architecture)
4. [Technology Stack](#technology-stack)
5. [Module List](#module-list)
6. [Quick Start (Docker)](#quick-start-docker)
7. [Environment Configuration](#environment-configuration)
8. [Database Setup](#database-setup)
9. [API Reference](#api-reference)
10. [Authentication](#authentication)
11. [Bangladesh-Specific Features](#bangladesh-specific-features)
12. [Testing](#testing)
13. [Deployment](#deployment)
14. [CI/CD Pipeline](#cicd-pipeline)
15. [Security](#security)
16. [Contributing](#contributing)
17. [License](#license)

---

## Overview

BizCore ERP is a portfolio-quality, self-hosted ERP platform built entirely in PHP 8.3 with zero external MVC framework dependencies. It implements clean architecture with a custom IoC container, query builder, JWT authentication, Redis caching, and double-entry accounting — all in a production-ready Docker environment.

**Live Demo:** See `docs/DeploymentGuide.md` for local setup instructions.

---

## Key Features

| Category | Features |
|---|---|
| **HRM** | Employee profiles, departments, designations, leave management, attendance tracking, transfers |
| **Payroll** | Bangladesh tax slabs (0–20%), salary structures, component-wise calculation, payslip PDF |
| **Inventory** | Weighted average costing, FIFO reserves, multi-warehouse, reorder alerts, barcode lookup |
| **Sales** | Quotations → Orders → Invoices → Payments, FIFO payment allocation, customer ledger |
| **Procurement** | Purchase orders, goods receipts, supplier management |
| **Accounting** | Double-entry bookkeeping, journal entries, trial balance, income statement, balance sheet |
| **CRM** | Customer profiles, credit limits, aging analysis, ledger |
| **Payments** | bKash & Nagad integration layers, cash/bank/cheque support |
| **Reports** | Dashboard KPIs, VAT return, payroll report, inventory valuation, P&L |
| **Multi-Branch** | Branch-scoped data, employee transfers across branches |
| **API** | RESTful API at `/api/v1/` with JWT, pagination, filtering |
| **Localization** | English + Bangla (bn), timezone Asia/Dhaka |

---

## Architecture

```
Presentation Layer    → Controllers (Web + API), Views (PHP templates)
Application Layer     → Services, DTOs, Validators
Domain Layer          → Entities (readonly, type-safe), Business Rules
Infrastructure Layer  → Repositories (PDO), Cache (Redis), Mail (PHPMailer)
```

**Core Design Patterns:**
- Repository Pattern — all database access through typed repository classes
- Service Pattern — business logic isolated from controllers and persistence
- PSR-11 Dependency Injection Container — auto-wiring via PHP Reflection
- Clean Architecture — each layer depends only on the layer below it

---

## Technology Stack

| Component | Technology |
|---|---|
| Language | PHP 8.3 (readonly properties, enums, union types, named args) |
| Web Server | Nginx 1.25 (Alpine) with FastCGI + rate limiting |
| Database | MySQL 8.0 with InnoDB, FULLTEXT indexes |
| Cache | Redis 7 (Predis client) |
| Auth | firebase/php-jwt + Redis token blacklist |
| PDF | DomPDF 2.x |
| Excel | PhpSpreadsheet 2.x |
| Email | PHPMailer 6.x → MailHog (dev) |
| Testing | PHPUnit 11, Faker 1.x |
| Code Quality | PHP_CodeSniffer (PSR-12), PHPStan (level 8) |
| CI/CD | GitHub Actions |
| Containers | Docker + Docker Compose |

---

## Module List

1. **Auth** — Login, logout, forgot/reset password, account lockout (5 attempts)
2. **Users & RBAC** — User management, roles, granular permissions
3. **Branches** — Multi-branch setup, branch-scoped data isolation
4. **Employees** — Full employee lifecycle, documents, emergency contacts
5. **Attendance** — Daily check-in/out, overtime tracking, leave requests
6. **Payroll** — Monthly processing, Bangladesh tax slabs, payslip generation
7. **CRM / Customers** — Customer profiles, credit control, ledger, aging
8. **Suppliers** — Supplier profiles, purchase history
9. **Products** — Categories, brands, variants, barcode, VAT rates
10. **Inventory** — Multi-warehouse stock, WAC costing, stock movements
11. **Purchase** — PO → GRN workflow, supplier invoice matching
12. **Sales** — Quotation → Order → Invoice → Payment workflow
13. **Expenses** — Expense claims, approval workflow, categories
14. **Accounting** — Double-entry, chart of accounts, journal entries
15. **Reports** — P&L, balance sheet, trial balance, VAT return
16. **Settings** — Company info, notification settings, integrations

---

## Quick Start (Docker)

### Prerequisites

- Docker 24+ and Docker Compose v2
- Git
- Make (optional but recommended)

### 1. Clone and configure

```bash
git clone https://github.com/your-org/bizcore-erp.git
cd bizcore-erp
cp .env.example .env
# Edit .env — set APP_KEY, DB_PASSWORD, JWT_SECRET
```

### 2. Generate application key

```bash
php -r "echo 'APP_KEY=' . base64_encode(random_bytes(32)) . PHP_EOL;"
# Paste the output into .env
```

### 3. Start containers

```bash
make up
# Or: docker compose up -d --build
```

### 4. Run migrations and seed

```bash
make migrate          # Runs all migrations
make seed             # Seeds: Roles, Permissions, Users, Accounts, Settings
make seed-demo        # (Optional) Adds demo data
```

### 5. Access the application

| Service | URL |
|---|---|
| Web App | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 |
| MailHog | http://localhost:8025 |

**Default login:**
- Email: `admin@bizcore.io`
- Password: `Admin@1234`

---

## Environment Configuration

Key `.env` variables:

```dotenv
APP_NAME="BizCore ERP"
APP_ENV=local          # local | staging | production
APP_DEBUG=true
APP_URL=http://localhost:8080
APP_KEY=               # 32-byte base64 key (required)
APP_LOCALE=en          # en | bn

DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=bizcore_erp
DB_USERNAME=bizcore
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PORT=6379

JWT_SECRET=            # Min 32-char secret (required)
JWT_TTL=60             # Token lifetime in minutes
JWT_REFRESH_TTL=10080  # Refresh token: 7 days

BKASH_APP_KEY=
BKASH_APP_SECRET=
BKASH_USERNAME=
BKASH_PASSWORD=

NAGAD_MERCHANT_ID=
NAGAD_MERCHANT_PRIVATE_KEY=

VAT_STANDARD_RATE=15
VAT_REGISTRATION_NUMBER=
```

---

## Database Setup

```bash
# Run all pending migrations
php database/migrate.php run

# Rollback the last batch
php database/migrate.php rollback

# Reset all and re-run
php database/migrate.php fresh

# Seed specific seeder
php database/seed.php RoleSeeder
php database/seed.php DatabaseSeeder   # runs all
```

**Migration files** live in `database/migrations/` and are numbered sequentially (`001_`, `002_`, …). Each file contains an `up()` and `down()` method.

---

## API Reference

All API endpoints are prefixed with `/api/v1/`.

### Authentication

```http
POST /api/v1/auth/login
Content-Type: application/json

{ "email": "admin@bizcore.io", "password": "Admin@1234" }
```

Response includes `token` (JWT Bearer) and `refresh_token`. Pass as:

```http
Authorization: Bearer <token>
```

### Pagination

All list endpoints support:
- `?page=1&per_page=20`
- `?sort=name&order=asc`
- `?search=keyword`

Response format:

```json
{
  "success": true,
  "message": "Success",
  "data": [...],
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

### Core Endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/v1/auth/login` | Login → JWT |
| POST | `/api/v1/auth/logout` | Blacklist token |
| POST | `/api/v1/auth/refresh` | Refresh JWT |
| GET | `/api/v1/auth/me` | Current user |
| GET | `/api/v1/products` | List products |
| POST | `/api/v1/products` | Create product |
| GET | `/api/v1/products/{id}` | Product detail + stock |
| PUT | `/api/v1/products/{id}` | Update product |
| DELETE | `/api/v1/products/{id}` | Soft delete |
| GET | `/api/v1/products/barcode/{code}` | Barcode lookup (cached) |
| GET | `/api/v1/inventory` | Stock levels |
| POST | `/api/v1/inventory/stock-in` | Receive stock |
| POST | `/api/v1/inventory/stock-out` | Issue stock |
| POST | `/api/v1/inventory/adjust` | Adjust stock |
| POST | `/api/v1/inventory/transfer` | Transfer between warehouses |
| GET | `/api/v1/customers` | List customers |
| POST | `/api/v1/customers` | Create customer |
| GET | `/api/v1/customers/{id}/ledger` | Customer ledger |
| GET | `/api/v1/sales/orders` | List sales orders |
| POST | `/api/v1/sales/orders` | Create sales order |
| GET | `/api/v1/sales/invoices` | List invoices |
| POST | `/api/v1/sales/invoices` | Create invoice + deduct stock |
| POST | `/api/v1/sales/payments` | Receive payment (FIFO allocation) |
| GET | `/api/v1/employees` | List employees |
| POST | `/api/v1/employees` | Create employee |
| POST | `/api/v1/employees/{id}/payroll` | Process payroll |
| GET | `/api/v1/reports/dashboard` | Dashboard metrics |
| GET | `/api/v1/reports/trial-balance` | Trial balance |
| GET | `/api/v1/reports/income-statement` | P&L |
| GET | `/api/v1/reports/balance-sheet` | Balance sheet |
| GET | `/api/v1/reports/vat-return` | VAT return |

Full API documentation: `docs/APIReference.md`

---

## Authentication

- **Session-based** (web) — PHP sessions with CSRF token protection
- **JWT** (API) — Signed with HS256, stored in Redis blacklist on logout
- **Account lockout** — 5 failed attempts → 15-minute lockout
- **Password history** — Last 5 passwords stored hashed (bcrypt cost 12)
- **Remember Me** — 30-day persistent cookie
- **Rate limiting** — Login: 5/5min | API: 60/min | General: 120/min

---

## Bangladesh-Specific Features

### VAT (Value Added Tax)
- Standard rate: **15%** (configurable in `.env`)
- VAT-inclusive and VAT-exclusive product pricing
- VAT return report with output/input tax calculation
- VAT registration number on invoices

### Income Tax Slabs (FY 2024–25)
| Annual Income (BDT) | Rate |
|---|---|
| Up to 3,50,000 | 0% |
| 3,50,001 – 4,50,000 | 5% |
| 4,50,001 – 7,50,000 | 10% |
| 7,50,001 – 11,50,000 | 15% |
| Above 11,50,000 | 20% |

### Mobile Payments
- **bKash** — Create payment, verify payment, refund
- **Nagad** — Create order, verify payment, refund
- Both integrate via the respective gateway APIs with credentials from `.env`

### Localization
- Language files: `resources/lang/en/app.php` and `resources/lang/bn/app.php`
- Timezone: `Asia/Dhaka`
- Currency symbol: ৳ (Taka)
- Date format: `d M Y` (BD standard)

---

## Testing

```bash
# Run all tests
make test
# Or: ./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Feature

# Coverage report (requires Xdebug)
./vendor/bin/phpunit --coverage-html coverage/

# Static analysis
./vendor/bin/phpstan analyse --level=8

# Code style check
./vendor/bin/phpcs --standard=phpcs.xml
```

**Test structure:**
```
tests/
├── Unit/
│   ├── AuthServiceTest.php
│   ├── InventoryServiceTest.php
│   ├── PayrollServiceTest.php
│   ├── AccountingServiceTest.php
│   └── SalesServiceTest.php
└── Feature/
    ├── AuthFeatureTest.php
    ├── ApiAuthTest.php
    ├── ProductApiTest.php
    ├── InventoryApiTest.php
    └── SalesApiTest.php
```

Target coverage: **80%+**

---

## Deployment

### Production Docker

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

Production compose adds:
- Resource limits (CPU/memory per container)
- `restart: unless-stopped`
- No development services (phpMyAdmin, MailHog)
- OPcache fully enabled

### Environment Checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_KEY` set (32-byte random)
- [ ] `JWT_SECRET` set (minimum 32 chars)
- [ ] Strong `DB_PASSWORD`
- [ ] Redis password set in `redis.conf` + `REDIS_PASSWORD` in `.env`
- [ ] SSL/TLS configured on Nginx
- [ ] File upload directory permissions (`storage/uploads` → 755)
- [ ] Cron job for scheduled tasks (if applicable)

Full deployment guide: `docs/DeploymentGuide.md`

---

## CI/CD Pipeline

GitHub Actions workflows in `.github/workflows/`:

### `ci.yml` — Runs on every push/PR
1. PHP 8.3 setup with extensions
2. MySQL 8 + Redis services
3. `composer install`
4. Run migrations on test database
5. PHPUnit test suite
6. PHPStan static analysis (level 8)
7. PHP_CodeSniffer (PSR-12)

### `deploy.yml` — Runs on push to `main`
1. Build production Docker image
2. Push to container registry
3. SSH to production server
4. Pull new image, run migrations, restart containers

---

## Security

- **SQL Injection** — All queries use PDO prepared statements
- **XSS** — All output sanitized via `htmlspecialchars()`
- **CSRF** — Synchronizer token pattern on all state-changing web routes
- **Clickjacking** — `X-Frame-Options: DENY` header
- **Content Sniffing** — `X-Content-Type-Options: nosniff`
- **CSP** — Content Security Policy header via Nginx
- **Rate Limiting** — Redis sliding window per IP
- **Password Hashing** — bcrypt with cost 12
- **JWT Blacklisting** — Logged-out tokens stored in Redis with TTL
- **HTTPS** — Enforced in production Nginx config

Security guide: `docs/SecurityGuide.md`

---

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Follow PSR-12 coding standards: `./vendor/bin/phpcs`
4. Write tests for new functionality
5. Submit a pull request

See `docs/DevelopmentGuide.md` for coding conventions and architecture guidelines.

---

## License

MIT License — see [LICENSE](LICENSE) file for details.

---

*Built with PHP 8.3 · MySQL 8 · Redis · Docker · No MVC Framework*
