# Deployment Guide — BizCore ERP

## Local Development

### Prerequisites

- Docker Desktop 4.x+ (or Docker Engine 24+)
- Docker Compose v2 (`docker compose` not `docker-compose`)
- Git

### 1. Clone

```bash
git clone https://github.com/your-org/bizcore-erp.git
cd bizcore-erp
```

### 2. Configure Environment

```bash
cp .env.example .env
```

Minimum required values:
```dotenv
APP_KEY=<base64-encoded-32-byte-key>
JWT_SECRET=<min-32-char-random-string>
DB_PASSWORD=secret
```

Generate `APP_KEY`:
```bash
php -r "echo 'APP_KEY=' . base64_encode(random_bytes(32)) . PHP_EOL;"
```

### 3. Start Services

```bash
make up
# or: docker compose up -d --build
```

Services started:
| Service | Port | Notes |
|---|---|---|
| nginx | 8080 | Web application |
| php-fpm | 9000 | Internal only |
| mysql | 3307 | Exposed for local tools |
| redis | 6380 | Exposed for local tools |
| phpmyadmin | 8081 | Dev only |
| mailhog | 8025 | Email UI (dev only) |

### 4. Initialise Database

```bash
make migrate          # Creates all tables
make seed             # Admin user, roles, permissions, chart of accounts
make seed-demo        # (Optional) Sample branches, employees, products, invoices
```

### 5. Access

- App: http://localhost:8080
- Admin: admin@bizcore.io / Admin@1234
- phpMyAdmin: http://localhost:8081 (server: mysql, user: bizcore, pass: from .env)
- MailHog: http://localhost:8025

---

## Production Deployment

### Server Requirements

- VPS or dedicated server: 2 vCPU / 4 GB RAM minimum
- Ubuntu 22.04 LTS (recommended)
- Docker 24+ and Docker Compose v2
- Domain name with DNS A record pointing to server IP
- SSL certificate (Let's Encrypt recommended)

### 1. Server Preparation

```bash
# Update packages
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER

# Install Docker Compose
sudo apt install docker-compose-plugin

# Install Nginx for SSL termination (optional — can use Nginx in Docker)
sudo apt install nginx certbot python3-certbot-nginx
```

### 2. Clone on Server

```bash
sudo mkdir -p /opt/bizcore
sudo chown $USER:$USER /opt/bizcore
cd /opt/bizcore
git clone https://github.com/your-org/bizcore-erp.git .
```

### 3. Production Environment

```bash
cp .env.example .env
nano .env
```

Required production settings:
```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_PASSWORD=<strong-random-password>
REDIS_PASSWORD=<strong-random-password>

JWT_SECRET=<64-char-random-string>
JWT_TTL=60

MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=noreply@your-domain.com
MAIL_PASSWORD=<mail-password>
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="BizCore ERP"
```

### 4. Start Production Stack

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

Production compose overrides:
- No MailHog service
- No phpMyAdmin service
- `restart: unless-stopped` on all services
- CPU and memory limits applied
- OPcache fully configured

### 5. SSL with Let's Encrypt

```bash
# Using Nginx on host as reverse proxy
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Or configure SSL inside Nginx Docker container
# (see docker/nginx/default.conf — uncomment SSL section)
```

Host Nginx proxy config (`/etc/nginx/sites-available/bizcore`):
```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;

    ssl_certificate     /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/bizcore /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### 6. Run Migrations

```bash
docker compose exec app php database/migrate.php run
docker compose exec app php database/seed.php DatabaseSeeder
```

### 7. File Permissions

```bash
docker compose exec app chown -R www-data:www-data storage/
docker compose exec app chmod -R 755 storage/
```

### 8. Scheduled Tasks (Cron)

Add to crontab for leave expiry, payroll reminders, etc.:
```bash
crontab -e
# Add:
* * * * * docker exec bizcore-app php /var/www/html/artisan schedule:run >> /dev/null 2>&1
```

---

## CI/CD with GitHub Actions

### Secrets Required

In GitHub repository → Settings → Secrets:
- `DOCKER_HUB_USERNAME` — Docker Hub username
- `DOCKER_HUB_TOKEN` — Docker Hub access token
- `SSH_PRIVATE_KEY` — Production server SSH private key
- `PRODUCTION_HOST` — Production server IP/hostname
- `PRODUCTION_USER` — SSH user on production server
- `PRODUCTION_PATH` — Deployment path (`/opt/bizcore`)

### Pipeline Flow

```
Push to main
    ↓
ci.yml: Tests + Linting + Static Analysis
    ↓ (on success)
deploy.yml: Docker build → push → SSH pull → migrate → restart
```

---

## Backup Strategy

### Database Backup

The MySQL container includes a backup script at `docker/mysql/backup.sh`:

```bash
# Manual backup
docker compose exec mysql /backup.sh

# Automated via cron on host
0 2 * * * docker exec bizcore-mysql /backup.sh
```

Backups are stored in `/docker/mysql/backups/` with 7-day retention.

### Restore

```bash
docker compose exec -T mysql mysql -u bizcore -p bizcore_erp < backup.sql
```

---

## Monitoring

Check container health:
```bash
docker compose ps
docker compose logs app --tail=50
docker compose logs nginx --tail=50
```

Application logs are written to `storage/logs/app.log` (inside container).

Container stats:
```bash
docker stats bizcore-app bizcore-mysql bizcore-redis
```
