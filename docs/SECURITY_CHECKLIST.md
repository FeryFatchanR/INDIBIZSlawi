# Checklist Keamanan

Status: ✅ diimplementasikan & diuji otomatis · ⚙️ diimplementasikan, perlu konfigurasi server · 📋 tanggung jawab operasional

## Aplikasi
| Kontrol | Status | Bukti/lokasi |
|---|---|---|
| CSRF pada form publik & admin | ✅ | `SecurityTest::test_csrf_token_is_required_for_public_forms` |
| Output escaping Blade; sanitasi rich text/custom HTML (allowlist) saat simpan & render | ✅ | `HtmlSanitizer`, `HtmlSanitizerTest`, `SecurityTest` (stored XSS, snapshot dimanipulasi) |
| URL dibatasi http(s)/mailto/tel/relatif | ✅ | `UrlGuard`, `SafeUrl`, `UrlGuardTest` |
| Nilai desain/responsif whitelist, tanpa CSS mentah | ✅ | `StyleTokens`, `SectionValidatorTest` |
| Template section dari registry, bukan dari input | ✅ | `SectionRegistry`, test tipe tidak dikenal |
| Batas payload, jumlah section, kedalaman nested | ✅ | `config/cms.php`, `SectionValidatorTest` |
| Custom HTML & custom script hanya permission khusus | ✅ | `builder.custom_html`, `settings.custom_scripts`, test terkait |
| Otorisasi backend via Policy (bukan sekadar menyembunyikan menu) | ✅ | `app/Policies`, `AuthenticationTest` (matriks role) |
| Anti-IDOR lead (scoping query + policy) | ✅ | `Lead::scopeVisibleTo`, `LeadManagementTest` |
| Preview draft wajib login + izin; `noindex`, `no-store` | ✅ | `BuilderAuthorizationTest` |
| Anti eskalasi role; minimal 1 super admin aktif | ✅ | `ManagesRoles`, `UserPolicy`, `SecurityTest` |
| Mass assignment (status, role, is_active, lock_version tidak fillable) | ✅ | `SecurityTest::test_mass_assignment_protection_on_sensitive_columns` |
| Optimistic locking & transaksi publish | ✅ | `ConcurrencyAndIntegrityTest` |
| Upload: MIME dari isi file, ekstensi dari server, nama acak, re-encode gambar, SVG ditolak, batas ukuran | ✅ | `MediaUploader`, `SecurityTest` |
| Rate limit + honeypot + token waktu pada form lead | ✅ | `LeadCaptureTest` |
| Password hashing (bcrypt), password admin min. 12 karakter | ✅ | cast `hashed`, `UserResource` |
| Audit log aktivitas penting; IP disimpan sebagai HMAC | ✅ | `ActivityLog`, `LogsActivity` |
| Security headers (nosniff, SAMEORIGIN, Referrer-Policy, Permissions-Policy, HSTS saat HTTPS) | ✅ | `SecurityHeaders` |
| Query lewat Eloquent/parameter binding | ✅ | tidak ada SQL dari input mentah |

## Server & operasional
| Kontrol | Status |
|---|---|
| `APP_DEBUG=false`, `APP_ENV=production` | ⚙️ |
| HTTPS + `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true` | ⚙️ |
| `.env` permission 640, tidak di-commit | ⚙️ |
| Document root hanya `public/`; PHP tidak dieksekusi di `storage/` | ⚙️ (`deploy/nginx.conf.example`) |
| User database khusus aplikasi (bukan root) | ⚙️ |
| Firewall: hanya 80/443 (+SSH dari IP tepercaya); MySQL tidak terbuka ke internet | 📋 |
| Pembatasan `/admin` (IP allowlist/VPN/WAF) bila memungkinkan | 📋 |
| Backup terenkripsi di luar server + uji restore berkala | 📋 (`BACKUP_ROLLBACK.md`) |
| `composer audit` & update keamanan berkala | 📋 |
| Rotasi password & pencabutan akses karyawan keluar | 📋 |

## Risiko residual
- Pemegang `settings.custom_scripts` dapat menyisipkan JavaScript ke semua halaman (by design). Batasi ke Super Admin.
- CSP penuh belum diterapkan (lihat Known Limitations).
- Login admin memakai rate limit bawaan Filament; MFA belum diaktifkan.
