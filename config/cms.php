<?php

/*
| Konfigurasi CMS. Nilai `settings_defaults` hanya fallback; nilai aktual diatur admin
| di menu Website Settings (tabel global_settings).
*/

return [

    'media' => [
        'disk' => env('MEDIA_DISK', 'public'),
        // SVG sengaja tidak diizinkan (dapat membawa script → XSS).
        'image_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'video_mimes' => ['video/mp4', 'video/webm'],
        'document_mimes' => ['application/pdf'],
        'max_image_kb' => 5120,
        'max_video_kb' => 51200,
        'max_document_kb' => 10240,
        'variant_widths' => [480, 960, 1440, 1920],
    ],

    'builder' => [
        'max_sections' => 100,
        'max_depth' => 2,
        'max_payload_bytes' => 262_144,
        'max_draft_revisions' => 50,
    ],

    'leads' => [
        'rate_limit_per_minute' => (int) env('LEAD_RATE_LIMIT', 5),
        'min_fill_seconds' => 3,
    ],

    // Slug yang tidak boleh dipakai halaman builder (bentrok dengan route aplikasi).
    'reserved_slugs' => [
        'admin', 'layanan', 'solusi', 'studi-kasus', 'leads', 'wa', 'preview', 'sitemap.xml', 'robots.txt',
        'storage', 'livewire', 'filament', 'up', 'build', 'login', 'logout', 'api', 'css', 'js', 'fonts',
    ],

    // Font yang boleh dipilih admin (dimuat dari fonts.bunny.net).
    'fonts' => [
        'Inter' => 'inter:400,500,600,700',
        'Plus Jakarta Sans' => 'plus-jakarta-sans:400,500,600,700',
        'Poppins' => 'poppins:400,500,600,700',
        'Roboto' => 'roboto:400,500,700',
        'Open Sans' => 'open-sans:400,600,700',
        'Montserrat' => 'montserrat:400,500,600,700',
    ],

    'settings_defaults' => [
        'general' => [
            'site_name' => 'Nama Perusahaan ISP',
            'tagline' => 'Solusi Internet & Jaringan untuk Bisnis',
            'logo_id' => null,
            'favicon_id' => null,
        ],
        'branding' => [
            'primary' => '#102C57',
            'secondary' => '#1769AA',
            'accent' => '#00B8D9',
            'background' => '#FFFFFF',
            'surface' => '#F5F7FA',
            'text' => '#172B4D',
            'font_heading' => 'Plus Jakarta Sans',
            'font_body' => 'Inter',
        ],
        'contact' => [
            'phone' => '<NOMOR_TELEPON>',
            'email' => 'sales@example.com',
            'whatsapp' => '',
            'whatsapp_default_message' => 'Halo, saya ingin konsultasi layanan internet untuk bisnis.',
            'address' => '<ALAMAT_KANTOR>',
            'maps_embed_url' => '',
            'business_hours' => 'Senin–Jumat 08.00–17.00 WIB',
        ],
        'social' => [
            'facebook' => '', 'instagram' => '', 'linkedin' => '', 'youtube' => '', 'tiktok' => '', 'x' => '',
        ],
        'seo' => [
            'title_suffix' => ' | Nama Perusahaan ISP',
            'default_description' => 'Layanan internet dedicated, broadband bisnis, VPN, dan solusi jaringan untuk perusahaan, instansi, dan UMKM.',
            'default_og_image_id' => null,
            'organization_type' => 'Organization',
        ],
        'analytics' => [
            'ga4_id' => '',
            'gtm_id' => '',
        ],
        'announcement' => [
            'enabled' => false,
            'text' => '',
            'link_label' => '',
            'link_url' => '',
            'style' => 'accent',
        ],
        'header' => [
            'cta_label' => 'Konsultasi Gratis',
            'cta_url' => '/kontak',
            'sticky' => true,
        ],
        'footer' => [
            'about' => 'Penyedia solusi konektivitas dan jaringan untuk kebutuhan bisnis Anda.',
            'copyright' => '© {year} Nama Perusahaan ISP. Seluruh hak cipta dilindungi.',
        ],
        'scripts' => [
            'head' => '',
            'body_end' => '',
        ],
    ],
];
