# Backup, Restore & Rollback

## Objek yang dicadangkan
| Objek | Lokasi | Metode | Frekuensi |
|---|---|---|---|
| Database (konten, revisi, lead, user) | MySQL | `mysqldump --single-transaction` | harian + sebelum deploy |
| Media upload | `storage/app/public/media` | `rsync`/`tar` | harian |
| Konfigurasi | `.env` | salinan terenkripsi di vault | saat berubah |
| Kode | Git (tag rilis) | — | tiap rilis |

Target awal (sesuaikan dengan kebutuhan bisnis): **RPO 24 jam** (maks. kehilangan data), **RTO 2 jam** (waktu pulih).
Untuk lead yang kritikal, pertimbangkan backup tiap 4–6 jam atau binlog/point-in-time recovery.

## Backup
```bash
TS=$(date +%F-%H%M)
mysqldump --single-transaction --routines --triggers -u <USER> -p <DB> | gzip > /backup/db-$TS.sql.gz
tar -czf /backup/media-$TS.tar.gz -C /var/www/isp-cms/storage/app/public media
sha256sum /backup/*-$TS.* > /backup/SHA256SUMS-$TS
```
- Simpan salinan **di luar server** (object storage/host lain), terenkripsi (mis. `gpg --symmetric` / restic).
- Retensi contoh: harian 14 hari, mingguan 8 minggu, bulanan 6 bulan.
- Backup di server yang sama **bukan** backup yang aman.

## Verifikasi & uji restore (minimal bulanan)
```bash
sha256sum -c /backup/SHA256SUMS-<TS>
mysql -u root -e "CREATE DATABASE restore_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
gunzip -c /backup/db-<TS>.sql.gz | mysql -u root restore_test
mysql -u root restore_test -e "SELECT COUNT(*) FROM leads; SELECT COUNT(*) FROM pages; SELECT COUNT(*) FROM page_revisions;"
```
Backup dianggap valid hanya bila restore berhasil dan jumlah data masuk akal.

## Restore produksi
1. `php artisan down`
2. Restore database: `gunzip -c db-<TS>.sql.gz | mysql -u <USER> -p <DB>`
3. Restore media: `tar -xzf media-<TS>.tar.gz -C storage/app/public`
4. `php artisan optimize:clear && php artisan optimize`
5. Bersihkan cache konten: Admin → **Cache Management** (atau `php artisan cache:clear`)
6. `php artisan up` → smoke test ([DEPLOYMENT.md §7](DEPLOYMENT.md))

## Rollback rilis
| Situasi | Tindakan |
|---|---|
| Bug kode, tanpa migration baru | `git checkout <TAG_LAMA>` → `composer install --no-dev` → build aset → `php artisan optimize` → `queue:restart` |
| Ada migration aditif (kolom/tabel baru) | rollback kode saja biasanya aman; kolom baru diabaikan kode lama |
| Migration mengubah/menghapus data | **restore backup** pra-deploy; `migrate:rollback` tidak mengembalikan data yang hilang |

Pemicu rollback: error 5xx berulang, login admin gagal, form lead gagal, halaman utama tidak tampil.

## Rollback konten (tanpa deploy)
Konten halaman memiliki riwayat revisi: **Pages → Pengaturan → Riwayat Revisi → Pulihkan ke draft → Publish**.
Versi publik tidak berubah sampai dipublish ulang.
