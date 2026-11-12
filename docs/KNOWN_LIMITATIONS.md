# Known Limitations

| Area | Keterbatasan | Mitigasi / rencana |
|---|---|---|
| Builder – nested blocks | Engine & validator mendukung `parent_uuid` (maks. 2 level), tetapi UI builder saat ini menyusun section secara datar. | Tambahkan tipe kontainer (kolom/grid) bila dibutuhkan. |
| Builder – drag ke canvas | Drop ke area canvas menambahkan section setelah section terpilih (atau di akhir), bukan di posisi kursor tepat. | Atur posisi akhir via Struktur halaman. |
| Builder – inline edit | Hanya teks polos tertentu (heading, subheading, subtitle, eyebrow, title, description). Rich text diedit di panel properti. | — |
| Builder – skala canvas | Pada layar sempit, preview desktop ditampilkan diperkecil (mis. 40%). | Ciutkan sidebar Filament atau pakai layar ≥1440 px. |
| Media | Varian gambar dibuat dengan GD (tanpa AVIF). GIF disimpan apa adanya (tanpa varian). Tidak ada crop/fokus di UI. | Tambahkan Intervention Image/Imagick jika perlu. |
| CSP | Content-Security-Policy penuh belum diterapkan (Filament/Livewire memakai inline script/style). Header keamanan dasar sudah aktif. | Terapkan CSP berbasis nonce khusus halaman publik. |
| MFA | Login admin belum memakai MFA. | Aktifkan fitur MFA Filament / SSO. |
| Notifikasi | Notifikasi lead hanya di bell admin (database), belum email/WhatsApp API. | Tambah channel `mail` bila SMTP tersedia. |
| Global Sections | Dikelola sebagai halaman bertipe *Global Section* (menu Website → Global Sections memfilter daftar Pages). | — |
| Multi-bahasa | Belum ada; konten satu bahasa (Indonesia). | — |
| Slug halaman | Perubahan slug langsung berlaku di URL publik (tidak menunggu publish) dan tidak membuat redirect otomatis. | Tambah tabel redirect bila sering diubah. |
| Font | Dimuat dari fonts.bunny.net; bila diblokir, fallback ke font sistem. | Self-host font bila diperlukan. |
| Data contoh | Testimoni & studi kasus contoh sengaja disembunyikan; angka statistik contoh hanya jumlah layanan/sektor di seed. | Ganti dengan data terverifikasi. |
| Verifikasi | Test otomatis dijalankan pada SQLite & MariaDB 10.11. Belum diuji pada MySQL 8 (CI menyiapkan job MySQL 8). Belum ada uji beban/performa. | Jalankan CI & uji beban sebelum go-live. |
