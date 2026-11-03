# TODO - Optimasi SEO

Hasil inspeksi menyeluruh atas implementasi SEO di project ATL Express
(Laravel 11 + Filament 3, deploy Docker + Caddy).

> Progres: **P0, P1 (kecuali optimasi gambar upload), dan P2 selesai.**
> Sisa item yang butuh aksi manual: pipeline gambar upload, `twitter:site`,
> dan verifikasi Search Console/Bing + analytics.

## Status saat ini (sudah bagus)

- `robots.txt` & `sitemap.xml` dinamis via `RobotsController` / `SitemapController`.
- Meta lengkap di `resources/views/layouts/app.blade.php`: title, description,
  keywords, robots, canonical, Open Graph, Twitter Card, JSON-LD.
- Structured data: `Organization` (semua halaman), `LocalBusiness` + `WebPage`
  (home), `BreadcrumbList`, `Service`, `NewsArticle`.
- Meta title/description/keywords per Post & Service bisa diisi manual dari Filament.
- Canonical pagination halaman berita sudah benar (`?page=N`).
- Gambar konten sudah `loading="lazy" decoding="async"` + `width`/`height`.
- Bahasa `lang="id"`, `APP_LOCALE=id`.
- Sisi server (Caddy): gzip/zstd, cache aset, redirect `www` → non-www,
  trailing slash 301, `X-Robots-Tag: noindex` untuk `/admin`, HSTS + security headers.
- Font Inter di-self-host (tanpa request ke Google Fonts), hero home memakai
  gambar lokal + preload, icon set lengkap + `site.webmanifest`.
- `FAQPage` schema di `/cek-resi` & `/cek-tarif`, sitemap sudah memakai `lastmod`.

## Temuan & Tasklist

### P0 - Perbaikan bug / wajib - SELESAI

- [x] **Bug `SeoService::description()`** (`app/Services/SeoService.php`).
      `mb_strrpos(...)` mengembalikan `false` saat 160 karakter pertama tanpa spasi,
      sehingga deskripsi menjadi `"..."`. Kini: guard `false` (potong langsung di
      batas panjang), `html_entity_decode` sebelum dipotong, dan pemotongan tetap
      di word boundary. Regression test di `tests/Unit/SeoServiceTest.php`.
- [x] **Hapus `resources/views/welcome.blade.php`** (view default Laravel, tidak
      dipakai rute apa pun).
- [x] **OG image 1200x630 branded** dibuat di `public/images/og-default.png`
      (dipakai sebagai default bila setting `og_image` kosong). Dibuat secara
      programatik karena kuota image generator habis.
- [x] **`og:image:width` & `og:image:height`** ditambahkan di layout; hanya
      dirender saat memakai OG default (1200x630) agar gambar custom tidak
      dipaksa dimensinya.
- [x] Default `image` di `HomeController`, `PageController`, `PostController`
      diarahkan ke `images/og-default.png`.
- [x] `deploy/SETUP.md` diselaraskan (referensi `logo.jpg` -> `og-default.png`).
- [x] Verifikasi: `php artisan test --filter="PagesTest|SeoServiceTest"` (13 passed),
      `php artisan view:cache` sukses, `pint` bersih.

### P1 - Dampak SEO tinggi - SELESAI (kecuali gambar upload)

- [x] **Halaman tipis `/cek-resi` & `/cek-tarif` diperkaya** (`PageController`).
      Dipilih strategi "tambah konten unik" (bukan `noindex`) karena keduanya
      halaman utama yang dicari brand. Ditambah: panduan langkah demi langkah,
      FAQ (4 pertanyaan per halaman), `BreadcrumbList`, dan `FAQPage` schema.
      Konten FAQ didefinisikan di `trackingFaq()` / `tariffFaq()`.
- [x] **`<lastmod>` untuk halaman statis** (`SitemapController`). Halaman statis
      kini memakai timestamp update terbaru dari layanan/artikel/setting.
- [x] **Structured data dirapikan** (`app/Services/SeoService.php`):
      - `Organization` punya `@id` (`#organization`) dan direferensikan dari
        `Service.provider`, `NewsArticle.author`, `NewsArticle.publisher`,
        serta `LocalBusiness.parentOrganization`;
      - `LocalBusiness` ditambah `@id`, `image`, `areaServed`;
      - `NewsArticle` ditambah `articleSection` (dari kategori) dan
        `mainEntityOfPage` berupa `WebPage` + `@id`;
      - helper baru `SeoService::faqPage()` dan `SeoService::organizationId()`.
      Catatan: `addressLocality`/`openingHours`/`priceRange` belum diisi karena
      data tersebut belum tersedia di tabel `settings` (butuh input admin).
- [x] **Icon set lengkap**: `favicon-16x16.png`, `favicon-32x32.png`,
      `apple-touch-icon.png` (180), `android-chrome-192x192.png`,
      `android-chrome-512x512.png`, `site.webmanifest`, dan `theme-color`
      `#0D2053`. Semua ditautkan di layout.
- [x] **LCP hero diperbaiki** (`resources/views/home.blade.php`). Gambar Pixabay
      eksternal diganti `public/images/hero.jpg` (lokal, 154 KB), dirender sebagai
      `<img>` dengan `fetchpriority="high"` + `decoding="async"`, dan di-preload
      di head. Test memastikan tidak ada lagi referensi `pixabay.com`.
- [x] **Font Inter di-self-host** (subset latin, 5 bobot: 400-800) di
      `public/fonts/`, `@font-face` ditambahkan ke `resources/css/app.css`,
      preload `inter-400.woff2`, dan link `fonts.googleapis.com`/`gstatic.com`
      dihapus dari layout. Caddy juga di-set cache untuk `/fonts/*`.
- [x] **Bonus - logo diperkecil**: `logo.png` 2000x2000 / 656 KB dipakai di
      header ukuran 40x40. Dibuat `logo-192.png` (21 KB) dan dipakai di
      header/footer. Turunan logo juga dipakai untuk ikon.
- [ ] **Optimasi gambar upload (WebP/AVIF + srcset)** - DITUNDA. Butuh keputusan
      soal pipeline gambar. Rencana yang disarankan: route media
      `/media/{width}/{path}` yang me-resize + cache ke `storage/app/public/derived`
      (atau pakai paket seperti `intervention/image` + Spatie media library),
      lalu tambahkan `srcset`/`sizes` di kartu layanan & artikel.

### P2 - Peningkatan - SELESAI (2 item manual)

- [x] **Batasi panjang meta title** - helper `SeoService::title()` (default 60,
      potong di word boundary) dipakai di layout dengan menyisakan ruang untuk
      suffix " - ATL Express". Regression test di `SeoServiceTest`.
- [x] **`rel="prev"` / `rel="next"`** pada daftar berita berhalaman
      (`posts/index.blade.php` via `@push('head')` + `@stack('head')` di layout).
      Regression test di `SeoTest`.
- [x] **Custom 404** (`resources/views/errors/404.blade.php`) - branded, ada
      internal link ke beranda/layanan/kontak, dan `noindex, follow`.
      Layout dibuat tahan bila DB tidak tersedia. Regression test di `SeoTest`.
- [x] **`APP_TIMEZONE=Asia/Jakarta`** di `.env`, `.env.example`, dan default
      `config/app.php`.
- [x] **Cache query footer** - `layouts/app.blade.php` memakai
      `Cache::remember('footer_services', 1 jam)`; cache di-forget otomatis saat
      Service disimpan/dihapus (`Service::saved`/`deleted`).
- [x] **Bersihkan HTML prototipe** `dash.html`, `login.html`, `reg.html`,
      `admin-after-login.html` (file tracked di git, bisa dikembalikan via git).
- [ ] **`twitter:site` / `twitter:creator`** - belum diisi karena belum ada akun
      X/Twitter resmi. Tinggal tambahkan meta bila akun sudah ada.
- [ ] **Verifikasi Search Console / Bing Webmaster + analytics** - butuh akses
      akun (manual, di luar kode).

## Verifikasi setelah perbaikan

- [x] Test suite: `php artisan test` -> 196 passed. 2 kegagalan pre-existing di
      `ShipmentTrackingTest` (bukan SEO), jadi tidak dihitung.
- [ ] `curl -I https://atlexpress.biz.id/robots.txt` dan `/sitemap.xml` → 200.
- [ ] Validasi JSON-LD di https://validator.schema.org & Rich Results Test.
- [ ] Cek preview OG (Facebook Debugger / LinkedIn Post Inspector).
- [ ] Lighthouse audit home, detail layanan, dan artikel (SEO + Performance).
