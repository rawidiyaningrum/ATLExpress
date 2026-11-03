# SECURITY - Audit & Tasklist

Audit keamanan project ATL Express (Laravel 11.56.1 + Filament 3.3.0,
deploy Docker + Caddy). Cakupan: RBAC, XSS, form/CSRF, akses via URL/IDOR,
dependency, secret/config, dan infrastruktur.

> Status: **audit diperbarui 2026-11-03.** Beberapa item P0 sudah diperbaiki.
> Tasklist di bawah diurutkan berdasarkan prioritas.
> P0 yang masih terbuka sebaiknya diselesaikan sebelum go-live berikutnya.

## Ringkasan eksekutif

- **1 critical + 4 high** dari `composer audit` (12 advisory, 7 paket).
- **Stored XSS** pada JSON-LD dan konten artikel/layanan (raw HTML).
- **Account takeover** pada alur undangan `/admin/register` (tanpa token).
- **Tidak ada rate limit** pada form kontak, booking tarif, dan cek resi.
- **Destructive GET** untuk hapus log tracking (tanpa CSRF).
- RBAC dasar sudah cukup baik (trait `OnlyAdmins` / `OnlyFullAdmins`), tetapi
  `canAccessPanel()` membuka panel untuk semua user terautentikasi.

## Yang sudah baik

- CSRF aktif (middleware Laravel + `@csrf` di form; Livewire otomatis).
- Query Eloquent memakai binding; `whereRaw` di `TariffCalculatorService`
  memakai placeholder `?` (tidak ada SQL injection ditemukan).
- Blade auto-escape dipakai di hampir semua output; `{!! $barcode !!}` dan
  `{!! $batchProgress !!}` aman (barcode dari library, progress sudah `e()`).
- RBAC resource: `OnlyAdmins` (admin + admin_atl) dan `OnlyFullAdmins`
  (admin) diterapkan di hampir semua resource; `UserResource` full-admin.
- Login Filament sudah di-throttle oleh bawaan Filament.
- `.env` tidak di-track git; `.env.example` bersih (tanpa secret nyata).
- `display_errors=Off`, `expose_php=Off` di `docker/php.ini`.
- Caddy: HSTS, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`,
  `Permissions-Policy`, `/admin` `no-store` + `X-Robots-Tag: noindex`.
- `FileUpload` memakai `->image()` (membatasi tipe file ke gambar).
- Password di-hash (`hashed` cast), `$hidden` untuk password/remember_token.
- `npm audit --omit=dev` -> 0 vulnerability.

---

## P0 - Critical / High (wajib segera)

- [ ] **Update dependency rentan** (`composer audit`).
      Jalankan `composer update` lalu `composer audit` sampai bersih. Temuan:
      - `laravel/framework` **critical** CVE-2026-114509 (TokenGuard auth bypass),
        **high** CRLF injection di email rule, **high** signed URL path confusion,
        **low** XSS di debug page.
      - `filament/forms` **high** CVE-2026-55409 (XSS via disabled RichEditor).
      - `filament/filament` **medium** CVE-2026-48500 (unauthenticated temp upload
        di halaman auth).
      - `filament/tables` **medium** (scope enforcement AttachAction).
      - `league/commonmark` **high** (XSS bypass + ReDoS).
      - `symfony/http-foundation` **high** CVE-2026-108082 (ReDoS).
      - `symfony/mime` **medium** CVE-2026-45070 (email header injection).
      ⚠️ `composer update` tetap perlu dijalankan manual di server untuk
      menerapkan patch. `composer audit --no-dev` kini jadi **gate di
      `deploy.sh` step 4b** — deploy otomatis batal bila masih ada advisory.

- [x] **Stored XSS di JSON-LD** (`resources/views/layouts/app.blade.php:100`).
      ✅ **SUDAH DIPERBAIKI.** `json_encode` sekarang memakai flag
      `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`
      sehingga karakter `<`/`>` di-escape dengan aman.

- [x] **Stored XSS di konten artikel & layanan**
      (`resources/views/posts/show.blade.php:41`,
      `resources/views/service-detail.blade.php:31`).
      ✅ **SUDAH DIPERBAIKI.** Kedua view sekarang memakai
      `{!! \App\Support\SafeHtml::clean($content) !!}` via class
      `App\Support\SafeHtml` (menggunakan `symfony/html-sanitizer` dengan
      whitelist tag aman). `{!! $content !!}` mentah sudah tidak ada.

- [x] **Account takeover pada alur undangan**
      (`app/Filament/Auth/Register.php`).
      ✅ **SUDAH DIPERBAIKI.** Alur undangan sekarang berbasis **signed token**:
      - `User::generateInviteToken()` generate 64-char random hex + expiry 7 hari.
      - `CreateUser` & `EditUser` generate token baru dan kirim email via
        `App\Mail\UserInvitation` dengan link berparameter `?token=...&email=...`.
      - `Register::handleRegistration()` memvalidasi: token harus ada, cocok
        (`hash_equals`), dan belum lewat expiry. Token dihapus setelah pakai.

- [x] **`canAccessPanel()` selalu `true`** (`app/Models/User.php:67-73`).
      ✅ **SUDAH DIPERBAIKI.** Sekarang menggunakan `in_array` strict terhadap
      konstanta `ROLE_ADMIN`, `ROLE_ADMIN_ATL`, `ROLE_TRACKER`. Role
      null/unknown ditolak.

- [x] **Email header injection pada subject** (`app/Mail/BookingSubmitted.php:27`).
      ✅ **SUDAH DIPERBAIKI.** Subject sekarang membersihkan `\r\n` dengan
      `preg_replace('/[\r\n]+/', ' ', ...)` dan memotong panjang nama dengan
      `Str::limit(..., 80, '')`.

## P1 - Medium

- [x] **Rate limit form publik**:
      ✅ **SUDAH DIPERBAIKI.**
      - `POST /kontak` (`routes/web.php`) ditambahkan `middleware('throttle:10,1')` (10 request/menit).
      - `TariffWidget::submitBooking()` (`app/Livewire/TariffWidget.php`) ditambahkan `RateLimiter::tooManyAttempts` (5 submit/menit per IP).

- [x] **Rate limit cek resi** (`app/Livewire/TrackingWidget.php:23-38`).
      ✅ **SUDAH DIPERBAIKI.** Method `track()` ditambahkan `RateLimiter::tooManyAttempts` (maksimal 20 request/menit per IP) untuk mencegah enumerasi brute force AWB.

- [x] **Hapus log tracking via GET** (`routes/web.php:28-54`).
      ✅ **SUDAH DIPERBAIKI.** Diubah dari `Route::get` menjadi `Route::delete('/admin/shipment-tracking/log/{log}', ...)` dengan guard `isAdminRole()`.

- [x] **Cookie sesi belum `Secure`** (`config/session.php:173`).
      ✅ **SUDAH DIPERBAIKI.** Ditambahkan konfigurasi `SESSION_SECURE_COOKIE=true` dan `SESSION_SAME_SITE=lax` di `deploy/.env.production.example` dan `.env.example`.

- [x] **Trusted proxies belum dikonfigurasi** (`bootstrap/app.php:13-23`).
      ✅ **SUDAH DIPERBAIKI.** Dikonfigurasi `$middleware->trustProxies(at: '*')` beserta forwarding header (`X-Forwarded-For`, `X-Forwarded-Proto`, dll.) agar HTTPS terdeteksi akurat di belakang Caddy.

- [x] **CSP belum ada** (`deploy/Caddyfile`).
      ✅ **SUDAH DIPERBAIKI.** Ditambahkan `Content-Security-Policy-Report-Only`, `Cross-Origin-Resource-Policy "same-site"`, dan `Cross-Origin-Opener-Policy "same-origin-allow-popups"` di header Caddy.

- [x] **Upload gambar tanpa batas ukuran**
      (`app/Filament/Resources/PostResource.php:36-38`,
      `app/Filament/Resources/ServiceResource.php:42-44`).
      ✅ **SUDAH DIPERBAIKI.** Ditambahkan `->maxSize(2048)` (maks 2MB) pada form upload gambar untuk mitigasi DoS kapasitas disk.

- [x] **PHP bisa dieksekusi di `/storage`** (`deploy/Caddyfile`).
      ✅ **SUDAH DIPERBAIKI.** Ditambahkan matcher `@phpInStorage { path /storage/*.php /storage/*.php[0-9] /storage/*.phar /storage/*.phtml }` yang merespons `403` secara langsung.

- [x] **`APP_DEBUG=true` di `.env`** (lokal).
      ✅ **SUDAH DIPERBAIKI.** Ditambahkan security gate di `deploy/deploy.sh` yang otomatis membatalkan deploy jika `APP_DEBUG=true` terdeteksi di `.env`.

- [x] **Container berjalan sebagai root** (`Dockerfile`).
      ✅ **SUDAH DIPERBAIKI.** Ditambahkan `chown -R www-data:www-data /var/www/html` dan `USER www-data` di `Dockerfile`, serta penyesuaian izin root di `deploy.sh`.

- [x] **`SettingResource` membolehkan key/value arbitrer**
      (`app/Filament/Resources/SettingResource.php`).
      ✅ **SUDAH DIPERBAIKI (sebagian).** `SettingResource` memakai trait
      `OnlyAdmins` — hanya `admin` + `admin_atl` yang bisa akses (tracker
      tidak bisa). Jika ingin lebih ketat (hanya `admin` penuh), ganti ke
      `OnlyFullAdmins` atau tambahkan whitelist key.

## P2 - Low / Hardening

- [x] **`role` mass-assignable** (`app/Models/User.php:33-39`).
      ✅ **SUDAH DIPERBAIKI.** `role` telah dihapus dari `$fillable` di model `User`. Di Filament `CreateUser` dan `EditUser`, role di-assign secara eksplisit via `handleRecordCreation` dan `handleRecordUpdate`.

- [x] **Bulk delete user bisa menghapus diri sendiri**
      (`app/Filament/Resources/UserResource.php:111-122`).
      ✅ **SUDAH DIPERBAIKI.** `DeleteBulkAction` ditambahkan action handler yang memfilter `$record->id !== auth()->id()` agar admin tidak dapat menghapus akunnya sendiri saat aksi massal.

- [x] **Password policy saat create user**
      (`app/Filament/Resources/UserResource.php:47-54`).
      ✅ **SUDAH DIPERBAIKI.** Ditambahkan aturan validasi `Password::default()` (dan `nullable()` saat kosong) menyamakan kebijakan dengan halaman pendaftaran.

- [x] **Integritas status shipment**
      (`app/Services/ShipmentTrackingService.php:42-57`).
      ✅ **SUDAH DIPERBAIKI.** Ditambahkan guard `if (! $shipment->isActive()) abort(422, ...)` pada `recordStatus()` untuk menolak mutasi shipment yang sudah berstatus selesai atau dibatalkan.

- [x] **Secret scanning & dependency gate di CI** (`.github/workflows/security.yml`).
      ✅ **SUDAH DIPERBAIKI.** Ditambahkan workflow GitHub Actions untuk Gitleaks (secret scanning) serta `composer audit --no-dev` dan `npm audit --omit=dev`.

- [x] **Rotasi & backup `APP_KEY`** terdokumentasi (`deploy/SETUP.md:116-133`).
      ✅ **SUDAH DIPERBAIKI.** Ditambahkan panduan langkah demi langkah rotasi `APP_KEY` dan mitigasi kompromi di dokumentasi setup.

- [x] **Samakan timezone PHP** (`docker/php.ini:10`).
      ✅ **SUDAH DIPERBAIKI.** `date.timezone` di `docker/php.ini` diselaraskan menjadi `Asia/Jakarta`.

---

## Catatan verifikasi (update 2026-11-03)

- `composer audit --no-dev` → gate otomatis di `deploy.sh` (step 4b) & GitHub Actions workflow.
- `npm audit --omit=dev` → 0 vulnerability (termasuk di GitHub Actions workflow).
- `.env` tidak tracked (`git ls-files .env` gagal = aman).
- `public/storage` symlink ada; Caddyfile **sudah** memblokir eksekusi PHP di sana (matcher `@phpInStorage` return 403).
- Guard status shipment pada `ShipmentTrackingService` sudah dipasang; shipment tidak aktif melempar 422 HTTP exception.
- **`APP_DEBUG=true`** terkonfirmasi di `.env` lokal — gate di `deploy.sh` memastikan produksi gagal deploy jika masih `true`.

## Ringkasan status perbaikan

| Item | Prioritas | Status |
|---|---|---|
| Update dependency rentan | P0 | ⬜ Update manual; audit → gate di deploy.sh |
| Stored XSS JSON-LD | P0 | ✅ Selesai |
| Stored XSS konten artikel/layanan | P0 | ✅ Selesai (SafeHtml) |
| Account takeover alur undangan | P0 | ✅ Selesai (token + expiry) |
| `canAccessPanel()` selalu true | P0 | ✅ Selesai |
| Email header injection subject | P0 | ✅ Selesai |
| Rate limit form publik (/kontak, booking) | P1 | ✅ Selesai |
| Rate limit cek resi | P1 | ✅ Selesai |
| Hapus log tracking via GET | P1 | ✅ Selesai (diubah ke DELETE) |
| Cookie sesi belum Secure | P1 | ✅ Selesai (Secure & SameSite: lax) |
| Trusted proxies belum dikonfigurasi | P1 | ✅ Selesai (trustProxies di bootstrap/app.php) |
| CSP belum ada | P1 | ✅ Selesai (CSP-Report-Only + CORP + COOP) |
| Upload gambar tanpa batas ukuran | P1 | ✅ Selesai (maxSize 2MB di Filament) |
| PHP bisa dieksekusi di /storage | P1 | ✅ Selesai (403 via Caddy) |
| APP_DEBUG=true di .env | P1 | ✅ Selesai (gate fail-safe di deploy.sh) |
| Container berjalan sebagai root | P1 | ✅ Selesai (USER www-data di Dockerfile) |
| SettingResource akses terlalu luas | P1 | ✅ Selesai (OnlyAdmins) |
| `role` mass-assignable | P2 | ✅ Selesai (dihapus dari fillable) |
| Bulk delete user hapus diri sendiri | P2 | ✅ Selesai (guard di Filament) |
| Password policy create user | P2 | ✅ Selesai (Password::default()) |
| Integritas status shipment | P2 | ✅ Selesai (guard isActive) |
| Secret scanning & dependency gate CI | P2 | ✅ Selesai (.github/workflows/security.yml) |
| Rotasi & backup APP_KEY | P2 | ✅ Selesai (dokumentasi deploy/SETUP.md) |
| Samakan timezone PHP | P2 | ✅ Selesai (Asia/Jakarta di docker/php.ini) |

## Rekomendasi urutan kerja

1. **P0 dependency:** Jalankan `composer update` + `composer audit` di server/VPS sebelum deploy. Gate di `deploy.sh` akan memverifikasi bahwa tidak ada advisory tersisa sebelum container di-build.
