# Arsitektur

## Lapisan
```
HTTP publik ─► Controllers/Public (tipis) ─► PageRenderer / Model query ─► Blade (layouts/public, builder/*)
Admin (/admin) ─► Filament Resources & Pages ─► Actions (transaksi + audit) ─► Models
                                               └► Policies (otorisasi backend)
Builder engine (app/Builder) : SectionRegistry · SectionType · SectionValidator · StyleTokens · PageRenderer
```
| Folder | Isi |
|---|---|
| `app/Builder` | Engine builder bebas UI: registry (allowlist tipe), kontrak `SectionType`, 23 tipe section, validator, whitelist gaya, renderer |
| `app/Actions/Cms` | `SaveDraft`, `PublishPage`, `UnpublishPage`, `RestoreRevision` |
| `app/Actions/Crm` | `CreateLead`, `ChangeLeadStatus`, `AssignLead`, `AddLeadNote` |
| `app/Policies` | Satu policy per model → permission `resource.ability` |
| `app/Support` | `HtmlSanitizer`, `UrlGuard`, `SettingsRepository`, `NavigationRepository`, `CmsCache`, `MediaUploader`, `MediaUsageTracker`, `SeoData`, `WhatsApp`, `Roles` |
| `app/Filament` | Resource, halaman (Builder, Settings, Conversion Report, Cache), widget |

## Model data (ringkas)
```
pages 1─* page_sections (DRAFT kerja, uuid unik)
pages 1─* page_revisions (snapshot immutable: draft/publish/restore) ; pages.published_revision_id ─► page_revisions
products *─* product_categories (product_category_pivot) ; products 1─* specifications/galleries/faqs
industry_solutions *─* products ; industry_solutions 1─* case_studies
navigation_menus 1─* navigation_menu_items (self parent; link ke page/product/solution)
media_assets ◄─ FK dari products, banners, partners, ... ; media_usages (pelacak pemakaian, RESTRICT)
leads 1─* lead_notes / lead_activities / lead_assignments ; leads ─► users (assigned_to), products, campaigns
global_settings (group,key unik → JSON) ; activity_logs (audit) ; tabel Spatie Permission
```
JSON hanya untuk konfigurasi fleksibel (`content`/`design`/`responsive` section, snapshot, settings, UTM). Data bisnis
(produk, lead, relasi) tetap relasional dengan FK, unique, dan index (`leads(assigned_to,status)`, `leads(status)`,
`page_sections(page_id,parent_uuid,sort_order)`, dst.).

## Alur versi halaman
1. Setiap aksi di builder → `SaveDraft`: validasi tree → transaksi → `SELECT … FOR UPDATE` pada page → cek
   `lock_version` (beda = `StaleDraftException`, perubahan ditolak) → upsert section berdasarkan UUID → `lock_version+1`.
2. **Simpan Draft** = checkpoint revision `draft` (dipangkas ke 50 terakhir).
3. **Publish** (`pages.publish`) → transaksi: validasi & sanitasi ulang → snapshot revision `publish` →
   `published_revision_id` diarahkan ke snapshot → cache dibersihkan setelah commit. Gagal di tengah = rollback penuh.
4. Publik **hanya** membaca snapshot published (cache per revision). Draft hanya via `/preview/pages/{uuid}`
   (login + policy, `noindex`, `no-store`).
5. **Restore** menyalin snapshot ke draft (revision `restore`); tayang setelah publish ulang.

## Skema komponen builder
```json
{
  "uuid": "9b2e…",                       // stabil, bukan index array
  "type": "product_cards",               // harus terdaftar di SectionRegistry
  "schema_version": 1,                   // dimigrasikan via SectionType::upgrade()
  "parent_uuid": null,                   // nested maks. 2 level (engine); UI saat ini datar
  "content":    { "heading": "…", "source": "featured", "limit": 4, "button_target": "product_page" },
  "design":     { "background": "surface", "padding_y": "lg", "text_align": "center", "card_style": "elevated" },
  "responsive": { "desktop": {"columns": 4, "visible": true}, "tablet": {"columns": 2}, "mobile": {"columns": 1} },
  "is_visible": true,
  "sort_order": 3
}
```
Setiap `SectionType` mendefinisikan: `key`, `label`, `category`, `icon`, `view()` (nama Blade **hard-coded**),
`contentRules()` (validasi Laravel), `designRules()`, `contentFields()/designFields()` (form property editor),
`richTextFields()` (disanitasi), `mediaFields()` (eager load & pelacakan), `prepare()` (query data dinamis),
`permission()` (mis. `custom_html` → `builder.custom_html`).

Tipe tersedia: hero, banner, heading, rich_text, image, gallery, video, product_cards, pricing_cards, features,
statistics, testimonials, logo_carousel, industry_solutions, case_studies, faq, contact_form, cta, map, spacer,
divider, global_section, custom_html.

### Menambah tipe section baru
1. Buat kelas di `app/Builder/Sections/XxxSection.php` (extends `SectionType`).
2. Buat view `resources/views/builder/xxx.blade.php` (escape semua output; pakai `StyleTokens` untuk kelas).
3. Daftarkan di `SectionRegistry::defaultTypes()`.
4. Tambah test validasi & render.

## Keputusan teknis
| Keputusan | Alasan |
|---|---|
| Canvas = iframe preview draft, lebar device asli lalu di-scale | breakpoint Tailwind (`md:`/`lg:`) bekerja nyata; CSS publik tidak bentrok dengan CSS Filament |
| Server otoritatif berbasis UUID; Sortable hanya mengirim urutan UUID | mencegah duplikasi/kehilangan section saat drag-and-drop; himpunan UUID divalidasi |
| Kelas desain dari whitelist `StyleTokens` | tidak ada CSS mentah dari pengguna; warna kustom hanya hex tervalidasi |
| Sanitasi saat simpan **dan** saat render | pertahanan berlapis bila data diubah di luar aplikasi |
| Cache berversi (`CmsCache`) | invalidasi menyeluruh tanpa cache tags (kompatibel driver database/file) |
| Gate::before Super Admin hanya untuk permission `x.y` | invarian policy (super admin terakhir, homepage, media terpakai) tetap berlaku untuk semua |
| Tanpa package activity-log/medialibrary | mengurangi dependensi; kebutuhan audit & media dipenuhi tabel sendiri |
