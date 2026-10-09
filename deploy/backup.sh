#!/usr/bin/env bash
#
# ATL Express - backup script (untuk pindah ke VPS lain)
# Jalankan di VPS awal:  bash /home/ubuntu/atlexpress/deploy/backup.sh
#
# Menghasilkan satu arsip berisi: dump database, file upload, .env, dan
# volume TLS Caddy. Kode TIDAK disertakan (ambil dari git di VPS baru).
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if [ ! -f .env ]; then
    echo "ERROR: .env tidak ditemukan di $ROOT"
    exit 1
fi

# Baca variabel yang dibutuhkan tanpa men-source seluruh .env.
env_get() {
    grep -E "^$1=" .env | tail -n1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
}
DB_USERNAME="$(env_get DB_USERNAME)"; DB_USERNAME="${DB_USERNAME:-atlexpress}"
DB_DATABASE="$(env_get DB_DATABASE)"; DB_DATABASE="${DB_DATABASE:-atlexpress}"
CADDY_VOLUME="${CADDY_VOLUME:-atlexpress_caddy_data}"

TS="$(date +%F_%H%M)"
OUT_DIR="${OUT_DIR:-backups/migration-$TS}"
mkdir -p "$OUT_DIR"
# Docker butuh path absolut untuk bind mount volume.
OUT_DIR="$(cd "$OUT_DIR" && pwd)"
ARCHIVE="$OUT_DIR/atlexpress-backup-$TS.tar.gz"

echo "==> [0/6] maintenance mode"
docker compose exec -T app php artisan down || true
restore_up() {
    echo "==> maintenance mode off"
    docker compose exec -T app php artisan up || true
}
trap restore_up EXIT

echo "==> [1/6] dump database -> $OUT_DIR/atlexpress_db.sql.gz"
docker compose exec -T db pg_dump -U "$DB_USERNAME" -d "$DB_DATABASE" \
    --no-owner --no-privileges | gzip > "$OUT_DIR/atlexpress_db.sql.gz"

echo "==> [2/6] archive uploads -> $OUT_DIR/storage.tar.gz"
tar czf "$OUT_DIR/storage.tar.gz" -C "$ROOT" storage/app/public

echo "==> [3/6] copy .env -> $OUT_DIR/env.production"
cp .env "$OUT_DIR/env.production"

echo "==> [4/6] backup caddy volume ($CADDY_VOLUME) -> $OUT_DIR/caddy_data.tar.gz"
docker run --rm -v "$CADDY_VOLUME":/data -v "$OUT_DIR":/backup alpine \
    tar czf /backup/caddy_data.tar.gz -C /data .

echo "==> [5/6] bundle -> $ARCHIVE"
tar czf "$ARCHIVE" -C "$OUT_DIR" \
    atlexpress_db.sql.gz storage.tar.gz env.production caddy_data.tar.gz

echo "==> [6/6] selesai"
echo
echo "Backup: $ARCHIVE"
echo
echo "Langkah berikutnya (pindah ke VPS baru):"
echo "  scp $ARCHIVE ubuntu@IP_VPS_BARU:~/"
echo "  # lalu di VPS baru (setelah 'git clone' ke /home/ubuntu/atlexpress):"
echo "  bash deploy/restore.sh ~/$(basename "$ARCHIVE")"
