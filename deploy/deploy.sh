#!/usr/bin/env bash
#
# ATL Express - deploy script (Docker Compose + Caddy)
# Jalankan di VPS:  bash /home/ubuntu/atlexpress/deploy/deploy.sh
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

APP_URL="${APP_URL:-https://atlexpress.biz.id}"

if [ ! -f .env ]; then
    echo "ERROR: .env belum ada. Buat dulu:"
    echo "  cp deploy/.env.production.example .env"
    echo "  lalu edit .env (isi APP_KEY via 'php artisan key:generate' atau atur DB_PASSWORD)."
    exit 1
fi

echo "==> [1/9] git pull"
git pull origin main

echo "==> [2/9] build frontend assets (npm)"
docker compose --profile tools run --rm assets sh -c "npm ci --no-audit --no-fund && npm run build"

echo "==> [3/9] composer install (--no-dev)"
docker compose run --rm app composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader

echo "==> [4/9] permissions (www-data)"
docker compose run --rm app sh -c "chown -R www-data:www-data storage bootstrap/cache"

echo "==> [5/9] build image"
docker compose build app worker scheduler

echo "==> [6/9] start/update containers"
docker compose up -d --remove-orphans

echo "==> [7/9] migrate"
docker compose run --rm app php artisan migrate --force

echo "==> [8/9] rebuild caches"
docker compose run --rm app php artisan optimize:clear
docker compose run --rm app php artisan optimize
docker compose restart worker scheduler

echo "==> [9/9] health check"
curl -fsSI "$APP_URL" >/dev/null && echo "OK: $APP_URL"