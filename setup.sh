#!/usr/bin/env bash
# BizCore ERP — Laravel Setup Script
# Run this ONCE after starting Docker Desktop:
#   chmod +x setup.sh
#   ./setup.sh

set -e

CONTAINER="bizcore-app"
APP_DIR="/var/www/html"

echo "==> Starting Docker services..."
docker compose up -d --build

echo "==> Waiting for MySQL to be healthy..."
until docker compose exec mysql mysqladmin ping -h localhost -u root -prootpassword --silent 2>/dev/null; do
  printf '.'
  sleep 2
done
echo ""

echo "==> Installing Composer dependencies..."
docker compose exec app composer install --no-interaction

echo "==> Generating application key..."
docker compose exec app php artisan key:generate --ansi

echo "==> Running database migrations..."
docker compose exec app php artisan migrate --force

echo "==> Seeding database..."
docker compose exec app php artisan db:seed --force

echo "==> Creating storage link..."
docker compose exec app php artisan storage:link

echo "==> Caching config/routes/views..."
docker compose exec app php artisan optimize

echo ""
echo "==========================================="
echo "  BizCore ERP (Laravel) is ready!"
echo "  App:         http://localhost:8080"
echo "  PHPMyAdmin:  http://localhost:8081"
echo "  MailHog:     http://localhost:8025"
echo "==========================================="
