# ISP B2B Marketing Website + Visual CMS

Website pemasaran layanan internet & solusi jaringan untuk pelanggan B2B, dengan **CMS berbasis database**,
**visual page builder drag-and-drop**, **CRM lead** sederhana, dan **RBAC**.

| Komponen | Versi |
|---|---|
| PHP | 8.3+ |
| Laravel | 13.x |
| Filament (admin `/admin`) | 5.x (Livewire 4) |
| Spatie Laravel Permission | 8.x |
| Database | MySQL 8 / MariaDB 10.6+ (SQLite untuk test) |
| Frontend | Blade + Tailwind CSS 4 + Alpine.js, SortableJS (builder) |

## Fitur utama
- **Website publik**: homepage & halaman perusahaan dari builder, `/layanan` + `/layanan/{slug}` (produk dinamis),
  `/solusi/{slug}`, `/studi-kasus/{slug}`, form konsultasi/RFQ, tombol WhatsApp terlacak, SEO (meta, OG, JSON-LD,
  sitemap.xml, robots.txt, breadcrumb), responsif 375–1920 px.
- **Visual Page Builder** (`Pages → Builder`): library 23 komponen, struktur halaman drag-and-drop (plus tombol & Alt+↑/↓),
  canvas live dengan preview Desktop/Tablet/Mobile, property editor per komponen (konten, desain, responsif, visibilitas),
  inline edit teks, draft otomatis, preview, publish/unpublish, riwayat revisi & restore, optimistic locking.
- **CMS**: produk (+spesifikasi, galeri, FAQ, kategori), kampanye, banner, promosi, testimoni, studi kasus, solusi
  industri, mitra, statistik, FAQ, media library aman, menu navigasi, global sections, website settings (branding, kontak,
  header/announcement, footer, SEO, analytics, custom script berizin khusus).
- **CRM**: lead dari form/WhatsApp/manual, status pipeline (New → Won/Lost), assign sales, catatan & riwayat follow-up,
  export Excel/CSV, laporan konversi, notifikasi.
- **Keamanan**: policy per resource, scoping lead per sales (anti-IDOR), sanitasi HTML allowlist, whitelist nilai desain,
  upload tervalidasi isi file, rate limit + honeypot, audit log, security headers.

## Mulai cepat (development)
```bash
composer install
npm ci && npm run build
cp .env.example .env && php artisan key:generate
# isi DB_* (atau DB_CONNECTION=sqlite untuk percobaan lokal)
php artisan migrate --seed
php artisan storage:link
php artisan app:create-super-admin
php artisan serve
```
Buka `http://localhost:8000` (website) dan `http://localhost:8000/admin` (CMS).
Seeder **tidak** membuat akun berpassword bawaan dan mengisi kontak dengan placeholder `<...>` — ganti di
**Website Settings**.

## Test
```bash
php artisan test          # 122 test (SQLite in-memory)
vendor/bin/pint --test    # code style
```

## Dokumentasi
- [Instalasi](docs/INSTALLATION.md)
- [Deployment produksi](docs/DEPLOYMENT.md)
- [Backup, restore & rollback](docs/BACKUP_ROLLBACK.md)
- [Arsitektur & skema komponen builder](docs/ARCHITECTURE.md)
- [Checklist keamanan](docs/SECURITY_CHECKLIST.md)
- [Panduan admin](docs/ADMIN_GUIDE.md)
- [Known limitations](docs/KNOWN_LIMITATIONS.md)
