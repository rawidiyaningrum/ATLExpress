# Setup ATL Express di VPS (Ubuntu 24.04)

Deploy dengan **Docker Compose + Caddy**. Path deploy: `/home/ubuntu/atlexpress`.

## Prasyarat

1. **DNS**: pastikan record A `atlexpress.biz.id` → IP VPS sudah aktif (Caddy membuat SSL Let's Encrypt otomatis; tanpa DNS yang mengarah, sertifikat gagal).
2. **Docker** terinstall. Sudah ada? cek: `docker --version && docker compose version`. Jika belum:
   ```bash
   # Install Docker engine + compose plugin (Ubuntu 24.04)
   sudo apt-get update
   sudo apt-get install -y ca-certificates curl
   sudo install -m 0755 -d /etc/apt/keyrings
   sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
   sudo chmod a+r /etc/apt/keyrings/docker.asc
   echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | sudo tee /etc/apt/sources.list.d/docker.list >/dev/null
   sudo apt-get update
   sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
   # agar bisa jalan tanpa sudo:
   sudo usermod -aG docker "$USER"
   # logout-login ulang SSH agar group berlaku
   ```
3. **Firewall**: buka port 22, 80, 443. Docker mem-publish port melewati ufw/iptables host, jadi prioritaskan firewall dari provider (cloud) bila ada.
   ```bash
   sudo ufw allow OpenSSH && sudo ufw allow 80/tcp && sudo ufw allow 443/tcp && sudo ufw enable
   ```

## Setup satu kali

```bash
cd /home/ubuntu/atlexpress

# 1. Pastikan kode terbaru
git pull origin main

# 2. Buat .env produksi
cp deploy/.env.production.example .env
nano .env
#    - set DB_PASSWORD ke password kuat yang sama dibawah
#    - APP_KEY kosong, nanti di-generate
```

Generate `APP_KEY` & seed (setelah `.env` siap). Semua artisan dijalankan lewat container `app`:

```bash
# 3. Deploy pertama (build image, migrate)
bash deploy/deploy.sh

# 4. Generate APP_KEY (isi ke .env lalu jalankan!)
docker compose run --rm app php artisan key:generate --show
#    copy hasilnya ke .env -> APP_KEY=...    lalu:
bash deploy/deploy.sh      # ulang agar cache pakai key baru

# 5. Seed data (HANYA sekali saat setup)
docker compose run --rm app php artisan db:seed --force

# 6. Buat user admin Filament (interaktif: nama, email, password)
docker compose run --rm app php artisan make:filament-user

# 7. Verifikasi
curl -I https://atlexpress.biz.id          # harap 200
# buka https://atlexpress.biz.id/admin  -> login user admin
```

> `make:filament-user` interaktif; toleransi saja walau keluar "Interactive prompt" terlihat aneh di layar — tetap isi sesuai prompt.

## Update kode berikutnya

```bash
cd /home/ubuntu/atlexpress
bash deploy/deploy.sh
```

Script menangani: `git pull` → build asset → `composer install --no-dev` → build image → `up -d` → `migrate --force` → cache → restart worker/scheduler → health-check. Seed **tidak** diulang.

## Operasional

| Perintah | Fungsi |
|---|---|
| `docker compose ps` | status semua service |
| `docker compose logs -f app` | log aplikasi (PHP-FPM) |
| `docker compose logs -f caddy` | log Caddy/SSL |
| `docker compose logs -f worker` | log queue worker |
| `docker compose down` | stop + hapus container (data aman, ada di volume) |
| `docker compose down -v` | stop AND **hapus volume** (DB & data!) — hanya saat mau reset total |
| `docker compose run --rm app php artisan` | jalankan artisan ad-hoc |

Backup DB:
```bash
docker compose exec db pg_dump -U atlexpress -d atlexpress > backup_atlexpress_$(date +%F).sql
```

## Arsitektur ringkas

```
Caddy (80/443, TLS auto)
  └─ php_fastcgi -> app (php-fpm 8.3)
                      └─ db (postgres:16)
worker  -> queue:work database
scheduler -> schedule:work (pengganti cron)
├─ volume dbdata       (Postgres)
├─ volume caddy_data/config
└─ bind .:/var/www/html (kode live dari /home/ubuntu/atlexpress)
```

## Catatan & troubleshooting

- **Kode live via bind mount** `.:/var/www/html` di container app/worker/scheduler/caddy → artefak (`vendor/`, `node_modules/`, `public/build`) tersimpan di host. Gitignore sudah mengabaikan folder-folder tersebut.
- Perubahan kode PHP langsung terlihat tanpa rebuild (restart worker/scheduler via deploy.sh memicu reload).
- Perubahan `Caddyfile`/`docker-compose.yml`/`Dockerfile` → jalankan ulang `bash deploy/deploy.sh` (ada step `docker compose build`).
- File upload Filament tersimpan di `storage/app/public` (host) dan ter-expose via symlink `storage:link` (otomatis oleh entrypoint).
- Queue & cache & session memakai database → `migrate --force` di deploy.sh sudah mencakup tabel terkait.
- Jika sertifikat SSL butuh dihilangkan saat tes (DNS belum siap): pertajuk `deploy/Caddyfile` ganti `atlexpress.biz.id` → `:80 { ... }` dan komentari `header {...}` bila perlu.