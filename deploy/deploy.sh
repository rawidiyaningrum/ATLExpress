#!/usr/bin/env bash
#
# ATL Express - deploy script (Docker Compose + Caddy)
# Jalankan di VPS:  bash /home/ubuntu/atlexpress/deploy/deploy.sh
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

APP_URL="${APP_URL:-https://atlexpress.biz.id}"
UPDATE_DEPS="${UPDATE_DEPS:-false}"

for arg in "$@"; do
    case "$arg" in
        --update-deps|--update)
            UPDATE_DEPS=true
            ;;
    esac
done

if [ ! -f .env ]; then
    echo "ERROR: .env belum ada. Buat dulu:"
    echo "  cp deploy/.env.production.example .env"
    echo "  lalu edit .env (isi APP_KEY via 'php artisan key:generate' atau atur DB_PASSWORD)."
    exit 1
fi

# ── P0 Security gate: tolak deploy bila APP_DEBUG aktif ───────────────────────
# Baca langsung dari .env (bukan env proses) supaya tidak terpengaruh nilai
# yang sudah di-export sebelumnya.
_DEBUG_VAL="$(grep -E '^APP_DEBUG=' .env | cut -d= -f2 | tr -d '[:space:]' | tr '[:upper:]' '[:lower:]')"
if [ "${_DEBUG_VAL}" = "true" ]; then
    echo "ERROR [P0-security]: APP_DEBUG=true terdeteksi di .env."
    echo "  Set APP_DEBUG=false dan APP_ENV=production di .env produksi sebelum deploy."
    exit 1
fi
echo "security-gate: APP_DEBUG=${_DEBUG_VAL} — OK"
# ──────────────────────────────────────────────────────────────────────────────

echo "==> [1/13] git pull"
git pull origin main

# Backup DB sebelum migrate, dan sedekat mungkin dengan git pull supaya
# tidak ada langkah panjang di mana kode sudah berubah tapi backup belum ada.
echo "==> [2/13] backup DB (sebelum migrate)"
BACKUP_DIR="${BACKUP_DIR:-backups}"
mkdir -p "$BACKUP_DIR"
BACKUP_FILE="$BACKUP_DIR/atlexpress_$(date +%F_%H%M).sql"
docker compose exec -T db pg_dump -U "${DB_USERNAME:-atlexpress}" -d "${DB_DATABASE:-atlexpress}" > "$BACKUP_FILE"
echo "backup disimpan di $BACKUP_FILE"

echo "==> [3/13] build frontend assets (npm)"
docker compose --profile tools run --rm assets sh -c "npm ci --no-audit --no-fund && npm run build"

# Pastikan public, storage, dan cache dapat ditulis sebelum composer & filament dijalankan
echo "==> [3b] set write permissions for build & assets"
docker compose run --rm --user root app sh -c "chmod -R 777 public storage bootstrap/cache 2>/dev/null || true"

if [ "$UPDATE_DEPS" = "true" ]; then
    echo "==> [4/13] composer update (--no-dev, resolve paket terbaru)"
    docker compose run --rm --user root -e COMPOSER_ALLOW_SUPERUSER=1 app composer update --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader
else
    echo "==> [4/13] composer install (--no-dev)"
    docker compose run --rm --user root -e COMPOSER_ALLOW_SUPERUSER=1 app composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader
fi

# ── P0 Security gate: composer audit ──────────────────────────────────────────
# Dijalankan setelah install supaya vendor/ selalu sinkron dengan lock file.
# Kegagalan audit (ada advisory) menghentikan deploy; tambahkan --no-dev supaya
# advisory package dev (yang tidak masuk produksi) tidak memblokir.
# Idempoten: jalankan tiap deploy; tidak menulis state ke disk.
echo "==> [4b] composer audit (security gate)"
docker compose run --rm app composer audit --no-dev --no-interaction
# ──────────────────────────────────────────────────────────────────────────────

echo "==> [5/13] permissions (www-data)"
docker compose run --rm --user root app sh -c "chown -R www-data:www-data storage bootstrap/cache public"

echo "==> [6/13] build image"
docker compose build app worker scheduler

# Migrate sebelum container di-restart. Kode di-bind mount ke host, jadi begitu
# git pull selesai kodenya sudah live, dan container lama masih melayani
# permintaan dengan skema lama. Migrate di sini menutup jendela itu.
echo "==> [7/13] migrate"
docker compose run --rm app php artisan migrate --force

# Seed artikel idempoten (updateOrCreate per slug) dijalankan tiap deploy supaya
# artikel SEO baru ikut masuk tanpa langkah manual. Hanya kelas ini — bukan
# db:seed penuh — karena PricelistSeeder menghapus seluruh tarif (merusak edit
# manual admin) dan InquirySeeder masih memakai create() polos.
echo "==> [8/13] seed artikel project cargo"
docker compose run --rm app php artisan db:seed --class=ArticleProjectCargoSeeder --force

echo "==> [9/13] start/update containers"
docker compose up -d --remove-orphans

echo "==> [10/13] rebuild caches"
docker compose run --rm app php artisan optimize:clear
docker compose run --rm app php artisan filament:clear-cached-components
docker compose run --rm app php artisan filament:cache-components
docker compose run --rm app php artisan optimize
docker compose run --rm app php artisan view:cache
docker compose restart worker scheduler

echo "==> [11/13] verify schema"
# Migrasi hanya diuji di SQLite, sedangkan produksi memakai PostgreSQL. Kolom
# yang diharapkan didaftarkan di sini supaya kegagalan khusus pgsql tertangkap
# eksplisit, bukan muncul sebagai 500 di halaman yang tidak dicek health check.
EXPECTED_COLUMNS="invoices.status,finance_journals.journal_type,shipments.service_type,shipments.dimension_length,shipments.dimension_width,shipments.dimension_height,invoice_items.basis,invoice_items.real_expense,invoices.shipping_real_expense,posts.meta_keywords,ai_usages.model,ai_usages.prompt_tokens,ai_usages.output_tokens,ai_usages.estimated_cost_usd,ai_usages.status,article_generation_batches.uuid,article_generation_batches.status,article_generation_batches.generated,article_generation_batches.fallback,article_generation_batches.actual_cost_usd,users.invite_token,users.invite_expires_at"

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

echo "==> [12/13] warm SEO endpoints"
# Uji lewat loopback (--resolve ke 127.0.0.1) agar tidak bergantung hairpin NAT:
# banyak VPS tidak bisa mengakses IP publiknya sendiri dari dalam.
APP_HOST="${APP_URL#*://}"; APP_HOST="${APP_HOST%%/*}"
curl -fsS --resolve "$APP_HOST:443:127.0.0.1" "$APP_URL/sitemap.xml" -o /dev/null && echo "sitemap OK"
curl -fsS --resolve "$APP_HOST:443:127.0.0.1" "$APP_URL/robots.txt" -o /dev/null && echo "robots OK"

echo "==> [13/13] health check"
curl -fsSI --resolve "$APP_HOST:443:127.0.0.1" "$APP_URL" >/dev/null && echo "OK (loopback): $APP_URL"
# Probe eksternal non-fatal: gagal di sini normal bila hairpin tidak didukung.
curl -fsSI "$APP_URL" >/dev/null 2>&1 && echo "OK (external): $APP_URL" \
    || echo "catatan: akses dari dalam VPS lewat IP publik gagal (hairpin) — normal"

if [ "$UPDATE_DEPS" = "true" ] && ! git diff --quiet composer.lock; then
    echo ""
    echo "======================================================================"
    echo "PEMBERITAHUAN: composer.lock telah diperbarui oleh --update-deps."
    echo "Jangan lupa commit & push lockfile ini kembali ke repositori Git:"
    echo "  git commit -am 'fix(deps): update composer.lock' && git push origin main"
    echo "======================================================================"
fi
