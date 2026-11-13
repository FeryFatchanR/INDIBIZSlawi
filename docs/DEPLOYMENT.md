# Deployment Produksi

## Arsitektur yang direkomendasikan
`Internet → Nginx (TLS) → PHP-FPM 8.3 → Laravel` · `MySQL 8 (lokal/terkelola)` · `Supervisor → queue:work` · `cron → schedule:run`.

## 1. Nginx
Contoh lengkap: [`deploy/nginx.conf.example`](../deploy/nginx.conf.example). Poin penting:
- `root /var/www/isp-cms/public;` — jangan pernah mengekspos root proyek.
- Tolak akses ke file tersembunyi (`/\.`), `.env`, dan eksekusi PHP di `storage/`.
- `client_max_body_size 60m;` (upload video maks. 50 MB).
- Cache statis untuk `/build/` dan `/storage/media/`.

## 2. HTTPS
```bash
sudo certbot --nginx -d <DOMAIN> -d www.<DOMAIN>
sudo certbot renew --dry-run
```
Setelah HTTPS aktif: `SESSION_SECURE_COOKIE=true`. Aplikasi otomatis mengirim HSTS pada request HTTPS.

## 3. Permission file
```bash
sudo chown -R deploy:www-data /var/www/isp-cms
sudo find /var/www/isp-cms -type d -exec chmod 755 {} \;
sudo find /var/www/isp-cms -type f -exec chmod 644 {} \;
sudo chmod -R ug+rwX /var/www/isp-cms/storage /var/www/isp-cms/bootstrap/cache
sudo chmod 640 /var/www/isp-cms/.env
```
Jangan `chmod 777`.

## 4. Queue worker (wajib untuk export lead & notifikasi)
Contoh: [`deploy/supervisor-queue.conf.example`](../deploy/supervisor-queue.conf.example)
```bash
sudo cp deploy/supervisor-queue.conf.example /etc/supervisor/conf.d/isp-cms-queue.conf
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl status
```

## 5. Scheduler
```cron
* * * * * cd /var/www/isp-cms && php artisan schedule:run >> /dev/null 2>&1
```
Dipakai untuk `queue:prune-batches` dan `queue:prune-failed` (lihat `routes/console.php`).

## 6. Prosedur rilis (zero-surprise)
Skrip: [`deploy/deploy.sh`](../deploy/deploy.sh). Urutan:
1. **Backup** database & `storage/app/public` (lihat [BACKUP_ROLLBACK.md](BACKUP_ROLLBACK.md)). Catat commit saat ini.
2. `php artisan down --retry=15`
3. `git fetch && git checkout <TAG/COMMIT>`
4. `composer install --no-dev --optimize-autoloader`
5. `npm ci && npm run build` (atau unggah `public/build` dari CI)
6. `php artisan migrate --force` — **baca migration baru dulu**; migration destruktif harus direncanakan terpisah.
7. `php artisan filament:assets && php artisan optimize:clear && php artisan optimize && php artisan filament:optimize`
8. `php artisan queue:restart`
9. `php artisan up`
10. Smoke test (bagian 7).

## 7. Smoke test pasca-deploy
| Uji | Kriteria sukses |
|---|---|
| `GET /`, `/layanan`, satu produk, `/kontak` | 200, konten tampil |
| `GET /up` | 200 |
| Login `/admin` | dashboard tampil tanpa error |
| Buka builder satu halaman, ubah teks, Preview | perubahan terlihat di preview, **tidak** di publik |
| Kirim form lead uji | lead tercatat; hapus lead uji setelahnya |
| Export lead | file tersedia di notifikasi (queue berjalan) |
| `tail -n 100 storage/logs/laravel.log` | tidak ada error baru |

## 8. Monitoring yang disarankan
| Objek | Ambang | Aksi |
|---|---|---|
| HTTP `/up` (uptime eksternal) | gagal 2× berturut | eskalasi on-call |
| Error 5xx di log Nginx | > 1% request / 5 menit | cek `laravel.log` |
| Disk (`storage/`, database) | > 80% | bersihkan media tak terpakai / perluas disk |
| Queue (`jobs` table) | > 100 job tertunda / 10 menit | cek Supervisor |
| Sertifikat TLS | < 14 hari | cek certbot timer |
| Login gagal di log | lonjakan | pertimbangkan fail2ban/WAF |

Log: `storage/logs/laravel.log` (atur `LOG_CHANNEL=daily`, `LOG_DAILY_DAYS=14`). Log tidak mencatat password/token;
IP pengunjung disimpan dalam bentuk hash.
