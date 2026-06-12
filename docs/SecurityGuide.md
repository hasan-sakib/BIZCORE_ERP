# Security Guide — BizCore ERP

## Authentication & Authorization

### Session Security
- Sessions use `session_regenerate_id(true)` on login to prevent session fixation
- Session cookie: `HttpOnly`, `Secure` (in production), `SameSite=Lax`
- Session lifetime: 120 minutes idle timeout

### Password Security
- bcrypt with cost factor 12 (`password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12])`)
- Password history: last 5 passwords stored and checked on reset
- Account lockout after 5 failed attempts (15-minute cooldown)
- Failed attempts tracked per user in `login_history` table

### JWT Security
- Algorithm: HS256
- Secret: minimum 32 characters, stored in `.env` (never in code)
- Access token TTL: 60 minutes (configurable)
- Refresh token TTL: 7 days
- Logout blacklists token hash in Redis with TTL matching remaining JWT lifetime
- All API endpoints validate the blacklist before processing

### RBAC
- Permissions stored in database, cached in Redis per user (300s TTL)
- `Permissions::can(string $permission)` checked in controllers
- Super-admin role bypasses all permission checks
- Every route that modifies data has an explicit permission guard

---

## Input Validation & Sanitization

### Output Encoding
All template output uses `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` via the `sanitize()` helper. Never echo raw user input.

### SQL Injection Prevention
All database queries use PDO prepared statements:
```php
// Safe — parameterised
$this->db->fetchAll("SELECT * FROM users WHERE email = ?", [$email]);

// Safe — query builder
$this->db->table('users')->where('email', $email)->first();

// NEVER — string interpolation with user input
"SELECT * FROM users WHERE email = '{$email}'"  // FORBIDDEN
```

### CSRF Protection
- All state-changing web routes (POST/PUT/DELETE) require a CSRF token
- Token generated per-session, stored in `$_SESSION['_csrf_token']`
- Validated via `CsrfMiddleware` before the controller is invoked
- API routes (`/api/*`) and webhook routes are explicitly excluded

---

## HTTP Security Headers

Applied by Nginx for all responses:

```nginx
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: default-src 'self'; script-src 'self' cdn.jsdelivr.net cdnjs.cloudflare.com code.jquery.com; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com; img-src 'self' data:; font-src 'self' cdnjs.cloudflare.com;
```

Server information hidden:
```nginx
server_tokens off;
```

---

## Rate Limiting

Implemented via Redis sliding window per IP address:

| Route | Limit | Window |
|---|---|---|
| `/auth/login` | 5 requests | 5 minutes |
| `/api/*` | 60 requests | 1 minute |
| All others | 120 requests | 1 minute |

Returns `429 Too Many Requests` with `Retry-After` header when exceeded.

---

## File Upload Security

All file uploads:
1. Validated by MIME type (not just extension)
2. Stored outside the web root (`storage/uploads/`)
3. Served via signed URLs, not directly accessible
4. Filename sanitized (UUID-based, original name discarded)
5. Maximum size enforced: 20 MB (PHP and Nginx levels)

Blocked upload types: `.php`, `.phar`, `.phtml`, `.sh`, `.exe`, `.js` in upload directories.

---

## Sensitive Data

### Environment Variables
- Never commit `.env` — it's in `.gitignore`
- Rotate secrets if accidentally exposed
- Use different secrets per environment (dev/staging/production)

### Logging
- Passwords are never logged
- JWT tokens are never logged (only token hash for blacklist)
- Credit card / payment details are not stored — only gateway reference IDs
- PII in logs is masked (email: first 2 chars + *** + domain)

### Database
- Passwords stored as bcrypt hash only
- National ID and bank details encrypted at rest using `encrypt()` helper (AES-256-GCM)
- Soft deletes used throughout — data is not permanently destroyed

---

## Network Security

### Production Checklist
- [ ] `APP_DEBUG=false` — never expose stack traces
- [ ] `APP_ENV=production`
- [ ] SSL/TLS enforced with HTTP → HTTPS redirect
- [ ] Redis password set (`requirepass` in `redis.conf` + `REDIS_PASSWORD` in `.env`)
- [ ] MySQL: `bizcore` user has only DML privileges (no GRANT, no DROP, no FILE)
- [ ] phpMyAdmin and MailHog NOT exposed in production
- [ ] Nginx blocks access to `.env`, `.git`, `composer.json`, `composer.lock`
- [ ] Firewall: only ports 80, 443, 22 (SSH) exposed to public internet
- [ ] MySQL port 3306 and Redis port 6379 NOT exposed to public internet

### MySQL User Privileges

The `bizcore` user should have:
```sql
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP
ON bizcore_erp.* TO 'bizcore'@'localhost';

-- NOT granted:
-- GRANT, SUPER, FILE, PROCESS, RELOAD
```

---

## Security Incident Response

If a JWT secret is compromised:
1. Update `JWT_SECRET` in `.env`
2. Restart the application container
3. All existing tokens immediately become invalid (all users must re-login)
4. Audit `login_history` table for suspicious activity

If a user account is compromised:
1. Admin → Users → Force logout (blacklists all active tokens)
2. Reset password
3. Review `audit_logs` table for actions taken by that user

If the database is compromised:
1. Rotate all credentials in `.env`
2. Run `php database/seed.php UserSeeder --reset-passwords`
3. Notify affected users to change passwords
4. Review `audit_logs` for data exfiltration patterns
