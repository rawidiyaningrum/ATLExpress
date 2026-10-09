#!/usr/bin/env bash
#
# ATL Express - restore script (pindah dari VPS lama)
# Jalankan di VPS baru:  bash /home/ubuntu/atlexpress/deploy/restore.sh <arsip.tar.gz>
#
# Prasyarat: repo sudah di-clone ke /home/ubuntu/atlexpress (kode diambil dari git).
# Script mengembalikan: database, file upload, .env (APP_KEY dipertahankan), dan
# volume TLS Caddy, lalu menjalankan deploy penuh.
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

ARCHIVE="${1:-}"
if [ -z "$ARCHIVE" ] || [ ! -f "$ARCHIVE" ]; then
    echo "Usage: bash deploy/restore.sh <atlexpress-backup-*.tar.gz>"
    exit 1
fi

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

echo "==> [1/8] extract archive"
tar xzf "$ARCHIVE" -C "$WORK"
for f in atlexpress_db.sql.gz storage.tar.gz env.production caddy_data.tar.gz; do
    if [ ! -f "$WORK/$f" ]; then
        echo "ERROR: $f tidak ada di dalam arsip"
        exit 1
    fi
done

echo "==> [2/8] siapkan .env"
if [ ! -f .env ]; then
    cp "$WORK/env.production" .env
    echo "    .env dibuat dari backup (APP_KEY & password dipertahankan)."
else
    echo "    .env sudah ada -> dipakai apa adanya (tidak ditimpa)."
    echo "    PASTIKAN APP_KEY sama dengan backup, jika tidak data terenkripsi/sesi rusak."
fi

# Baca variabel yang dibutuhkan tanpa men-source seluruh .env.
env_get() {
    grep -E "^$1=" .env | tail -n1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
}
DB_USERNAME="$(env_get DB_USERNAME)"; DB_USERNAME="${DB_USERNAME:-atlexpress}"
DB_DATABASE="$(env_get DB_DATABASE)"; DB_DATABASE="${DB_DATABASE:-atlexpress}"
DB_PASSWORD="$(env_get DB_PASSWORD)"
CADDY_VOLUME="${CADDY_VOLUME:-atlexpress_caddy_data}"

echo "==> [3/8] start db & sinkronkan password role"
docker compose up -d db
echo -n "    menunggu db siap"
DB_READY=""
for _ in $(seq 1 30); do
    if docker compose exec -T db pg_isready -U "$DB_USERNAME" -d "$DB_DATABASE" >/dev/null 2>&1; then
        DB_READY="1"
        break
    fi
    echo -n "."
    sleep 2
done
echo
if [ -z "$DB_READY" ]; then
    echo "ERROR: database tidak siap dalam batas waktu."
    exit 1
fi

# Volume Postgres hanya memakai POSTGRES_PASSWORD saat pertama dibuat, dan dump
# tidak membawa password role. Selaraskan password role dengan .env agar app
# bisa authenticate walau volume sudah pernah di-init dengan password lain.
# Lewat unix socket (local auth biasanya trust); -w agar tidak menggantung.
if docker compose exec -T db psql -w -U "$DB_USERNAME" -d "$DB_DATABASE" \
        -v pw="$DB_PASSWORD" -c "ALTER ROLE \"$DB_USERNAME\" WITH PASSWORD :'pw';" >/dev/null 2>&1; then
    echo "    password role disinkronkan dengan .env."
else
    echo "    PERINGATAN: gagal sinkronkan password (local auth bukan trust)."
    echo "    Jika migrate gagal auth, reset volume: docker compose down && docker volume rm atlexpress_dbdata, lalu restore ulang."
fi

echo "==> [4/8] restore database"
gunzip -c "$WORK/atlexpress_db.sql.gz" | docker compose exec -T db psql -U "$DB_USERNAME" -d "$DB_DATABASE"

echo "==> [5/8] restore uploads"
tar xzf "$WORK/storage.tar.gz" -C "$ROOT"

echo "==> [6/8] restore caddy volume ($CADDY_VOLUME)"
docker volume create "$CADDY_VOLUME" >/dev/null
docker run --rm -v "$CADDY_VOLUME":/data -v "$WORK":/backup alpine \
    sh -c "tar xzf /backup/caddy_data.tar.gz -C /data"

echo "==> [7/8] permissions (www-data)"
docker compose run --rm app chown -R www-data:www-data storage bootstrap/cache

echo "==> [8/8] deploy penuh"
bash deploy/deploy.sh

echo
echo "Restore selesai. Langkah akhir:"
echo "  1. Arahkan DNS atlexpress.biz.id ke IP VPS baru ini."
echo "  2. Verifikasi https://atlexpress.biz.id dan halaman /admin."
echo "  3. Setelah yakin, matikan service di VPS lama."
