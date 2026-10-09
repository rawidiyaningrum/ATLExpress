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

echo "==> [1/12] git pull"
git pull origin main

# Backup DB sebelum migrate, dan sedekat mungkin dengan git pull supaya
# tidak ada langkah panjang di mana kode sudah berubah tapi backup belum ada.
echo "==> [2/12] backup DB (sebelum migrate)"
BACKUP_DIR="${BACKUP_DIR:-backups}"
mkdir -p "$BACKUP_DIR"
BACKUP_FILE="$BACKUP_DIR/atlexpress_$(date +%F_%H%M).sql"
docker compose exec -T db pg_dump -U "${DB_USERNAME:-atlexpress}" -d "${DB_DATABASE:-atlexpress}" > "$BACKUP_FILE"
echo "backup disimpan di $BACKUP_FILE"

echo "==> [3/12] build frontend assets (npm)"
docker compose --profile tools run --rm assets sh -c "npm ci --no-audit --no-fund && npm run build"

echo "==> [4/12] composer install (--no-dev)"
docker compose run --rm app composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader

echo "==> [5/12] permissions (www-data)"
docker compose run --rm app sh -c "chown -R www-data:www-data storage bootstrap/cache"

echo "==> [6/12] build image"
docker compose build app worker scheduler

# Migrate sebelum container di-restart. Kode di-bind mount ke host, jadi begitu
# git pull selesai kodenya sudah live, dan container lama masih melayani
# permintaan dengan skema lama. Migrate di sini menutup jendela itu.
echo "==> [7/12] migrate"
docker compose run --rm app php artisan migrate --force

echo "==> [8/12] start/update containers"
docker compose up -d --remove-orphans

echo "==> [9/12] rebuild caches"
docker compose run --rm app php artisan optimize:clear
docker compose run --rm app php artisan filament:clear-cached-components
docker compose run --rm app php artisan filament:cache-components
docker compose run --rm app php artisan optimize
docker compose run --rm app php artisan view:cache
docker compose restart worker scheduler

echo "==> [10/12] verify schema"
# Migrasi hanya diuji di SQLite, sedangkan produksi memakai PostgreSQL. Kolom
# yang diharapkan didaftarkan di sini supaya kegagalan khusus pgsql tertangkap
# eksplisit, bukan muncul sebagai 500 di halaman yang tidak dicek health check.
EXPECTED_COLUMNS="invoices.status,finance_journals.journal_type,shipments.service_type,shipments.dimension_length,shipments.dimension_width,shipments.dimension_height,invoice_items.basis,invoice_items.real_expense,invoices.shipping_real_expense"

MISSING="$(docker compose exec -T db psql -U "${DB_USERNAME:-atlexpress}" -d "${DB_DATABASE:-atlexpress}" -tAc "
    SELECT coalesce(string_agg(wanted.name, ', '), '')
    FROM unnest(string_to_array('$EXPECTED_COLUMNS', ',')) AS wanted(name)
    WHERE NOT EXISTS (
        SELECT 1 FROM information_schema.columns c
        WHERE c.table_schema = 'public'
          AND c.table_name = split_part(wanted.name, '.', 1)
          AND c.column_name = split_part(wanted.name, '.', 2)
    );")"

DROPPED="$(docker compose exec -T db psql -U "${DB_USERNAME:-atlexpress}" -d "${DB_DATABASE:-atlexpress}" -tAc "
    SELECT count(*) FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'shipments'
      AND column_name = 'tracking_number';")"

if [ -n "$MISSING" ] || [ "$DROPPED" != "0" ]; then
    echo "ERROR: skema tidak sesuai harapan, bagian yang bermasalah:"
    echo "  kolom hilang: ${MISSING:-tidak ada}"
    echo "  shipments.tracking_number masih ada: $DROPPED (harusnya 0)"
    echo "  Kode yang sudah live memakai skema lama. Rollback:"
    echo "    docker compose exec -T db psql -U ${DB_USERNAME:-atlexpress} -d postgres \\"
    echo "      -c 'DROP DATABASE ${DB_DATABASE:-atlexpress} WITH (FORCE);' \\"
    echo "      -c 'CREATE DATABASE ${DB_DATABASE:-atlexpress} OWNER ${DB_USERNAME:-atlexpress};'"
    echo "    docker compose exec -T db psql -U ${DB_USERNAME:-atlexpress} -d ${DB_DATABASE:-atlexpress} < $BACKUP_FILE"
    echo "    git reset --hard <commit-sebelumnya>"
    exit 1
fi
echo "schema OK"

echo "==> [11/12] warm SEO endpoints"
# Uji lewat loopback (--resolve ke 127.0.0.1) agar tidak bergantung hairpin NAT:
# banyak VPS tidak bisa mengakses IP publiknya sendiri dari dalam.
APP_HOST="${APP_URL#*://}"; APP_HOST="${APP_HOST%%/*}"
curl -fsS --resolve "$APP_HOST:443:127.0.0.1" "$APP_URL/sitemap.xml" -o /dev/null && echo "sitemap OK"
curl -fsS --resolve "$APP_HOST:443:127.0.0.1" "$APP_URL/robots.txt" -o /dev/null && echo "robots OK"

echo "==> [12/12] health check"
curl -fsSI --resolve "$APP_HOST:443:127.0.0.1" "$APP_URL" >/dev/null && echo "OK (loopback): $APP_URL"
# Probe eksternal non-fatal: gagal di sini normal bila hairpin tidak didukung.
curl -fsSI "$APP_URL" >/dev/null 2>&1 && echo "OK (external): $APP_URL" \
    || echo "catatan: akses dari dalam VPS lewat IP publik gagal (hairpin) — normal"
