# Instalasi

## 1. Prasyarat
| Kebutuhan | Keterangan |
|---|---|
| PHP 8.3+ | ekstensi: `intl`, `mbstring`, `pdo_mysql`, `gd` (dengan WebP & JPEG), `fileinfo`, `zip`, `xml`, `curl`, `bcmath` |
| Composer 2 | |
| Node.js 20+ & npm | hanya untuk build aset (bisa di mesin build/CI) |
| MySQL 8 / MariaDB 10.6+ | charset `utf8mb4`, collation `utf8mb4_unicode_ci` |
| Web server | Nginx/Apache dengan document root ke `public/` |
| Process manager | Supervisor/systemd untuk queue worker |

Cek cepat:
```bash
php -v && php -m | grep -Ei 'intl|mbstring|pdo_mysql|gd|fileinfo|zip'
php -r 'var_dump(gd_info()["WebP Support"]);'
composer -V && node -v
```

## 2. Database
```sql
CREATE DATABASE isp_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'isp_cms'@'localhost' IDENTIFIED BY '<PASSWORD_KUAT>';
GRANT ALL PRIVILEGES ON isp_cms.* TO 'isp_cms'@'localhost';
FLUSH PRIVILEGES;
```
Gunakan user khusus aplikasi (bukan root), hanya pada database aplikasi.

## 3. Aplikasi
```bash
git clone <URL_REPO> /var/www/isp-cms && cd /var/www/isp-cms
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env
php artisan key:generate
```
Isi `.env` (jangan commit file ini):

| Variabel | Nilai produksi |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://<DOMAIN>` (dipakai URL media, sitemap, canonical) |
| `DB_*` | kredensial database di atas |
| `SESSION_ENCRYPT` | `true` |
| `SESSION_SECURE_COOKIE` | `true` (wajib HTTPS) |
| `QUEUE_CONNECTION` | `database` (export lead & notifikasi) |
| `MEDIA_DISK` | `public` |
| `MAIL_*` | opsional (notifikasi saat ini via database/bell admin) |

## 4. Inisialisasi
```bash
php artisan migrate --force
php artisan db:seed --force        # role/permission + konten awal (dilewati bila konten sudah ada)
php artisan storage:link
php artisan app:create-super-admin # password minimal 12 karakter
php artisan filament:assets
php artisan optimize && php artisan filament:optimize
```

## 5. Setelah login pertama
1. **Website Settings**: nama perusahaan, logo, warna brand, kontak, nomor WhatsApp, alamat, embed Google Maps, SEO.
2. **Products**: lengkapi deskripsi, spesifikasi, gambar.
3. **Testimonials / Case Studies / Partners**: konten contoh disembunyikan — isi dengan data asli (dengan izin pelanggan).
4. **Pages → Builder**: sesuaikan homepage & halaman perusahaan, lalu **Publish**.
5. **Users**: buat akun Administrator/Marketing/Sales; jangan berbagi akun Super Admin.

## 6. Verifikasi
| Cek | Hasil yang diharapkan |
|---|---|
| `curl -I https://<DOMAIN>/` | `200`, header `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff` |
| `/admin` tanpa login | redirect ke `/admin/login` |
| `/sitemap.xml` | XML berisi halaman & produk published |
| Kirim form di `/kontak` | lead muncul di **Leads** dengan status New |
| `php artisan queue:work --once` | job export/notifikasi diproses |
