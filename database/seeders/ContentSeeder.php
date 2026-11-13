<?php

namespace Database\Seeders;

use App\Actions\Cms\PublishPage;
use App\Actions\Cms\SaveDraft;
use App\Builder\SectionRegistry;
use App\Enums\PageType;
use App\Enums\ProductStatus;
use App\Models\CaseStudy;
use App\Models\Faq;
use App\Models\IndustrySolution;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Statistic;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Konten awal yang SELURUHNYA dapat diubah melalui CMS. Teks bersifat netral; data kontak
 * berupa placeholder (<...>) yang wajib diganti di Website Settings. Dilewati bila konten sudah ada.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        if (Page::query()->exists() || Product::query()->exists()) {
            $this->command?->info('Konten sudah ada — ContentSeeder dilewati.');

            return;
        }

        $categories = $this->categories();
        $products = $this->products($categories);
        $solutions = $this->industrySolutions($products);
        $this->marketing($solutions);
        $pages = $this->pages();
        $this->navigation($pages, $products);
    }

    /** @return array<string, ProductCategory> */
    private function categories(): array
    {
        $rows = ['internet' => 'Internet Bisnis', 'network' => 'Jaringan & Keamanan', 'smart' => 'Smart Solutions'];
        $out = [];
        foreach ($rows as $slug => $name) {
            $out[$slug] = ProductCategory::create(['name' => $name, 'slug' => $slug, 'is_visible' => true]);
        }

        return $out;
    }

    /** @return array<string, Product> */
    private function products(array $categories): array
    {
        $data = [
            ['Astinet', 'astinet', 'globe-alt', 'internet', 'Internet dedicated korporat dengan bandwidth simetris dan jaminan layanan.',
                ['Bandwidth dedicated 1:1', 'IP publik', 'Monitoring jaringan', 'Dukungan teknis 24/7'], ['Perusahaan', 'Instansi pemerintah', 'Perbankan'], true],
            ['IndiBiz', 'indibiz', 'wifi', 'internet', 'Internet broadband untuk UMKM dan kantor dengan pilihan paket fleksibel.',
                ['Pilihan paket fleksibel', 'Instalasi cepat', 'Cocok untuk kantor & toko'], ['UMKM', 'Retail', 'Kantor cabang'], true],
            ['Dedicated Internet', 'dedicated-internet', 'server-stack', 'internet', 'Koneksi internet khusus tanpa berbagi bandwidth untuk aplikasi kritikal bisnis.',
                ['Tanpa berbagi bandwidth', 'Latensi rendah', 'Skalabel'], ['Enterprise', 'Data center', 'Industri'], true],
            ['Business Broadband', 'business-broadband', 'signal', 'internet', 'Internet broadband bisnis yang hemat biaya untuk operasional harian.',
                ['Biaya efisien', 'Kecepatan tinggi', 'Dukungan pelanggan bisnis'], ['UMKM', 'Hotel', 'Sekolah'], false],
            ['VPN', 'vpn', 'lock-closed', 'network', 'Jaringan privat aman untuk menghubungkan kantor pusat dan cabang.',
                ['Koneksi antar-cabang', 'Terenkripsi', 'Manajemen terpusat'], ['Perusahaan multi-cabang', 'Perbankan', 'Pemerintahan'], true],
            ['Managed Wi-Fi', 'managed-wifi', 'arrows-right-left', 'network', 'Wi-Fi terkelola untuk area publik, hotel, kampus, dan perkantoran.',
                ['Hotspot terkelola', 'Portal login', 'Laporan pemakaian'], ['Hotel', 'Kampus', 'Rumah sakit'], false],
            ['CCTV dan Smart Solutions', 'cctv-smart-solutions', 'video-camera', 'smart', 'Solusi CCTV dan perangkat pintar terintegrasi untuk keamanan dan efisiensi.',
                ['Pemantauan jarak jauh', 'Penyimpanan rekaman', 'Integrasi perangkat'], ['Kawasan industri', 'Retail', 'Perkantoran'], false],
            ['Other Network Solutions', 'network-solutions', 'cog-6-tooth', 'network', 'Konsultasi, desain, dan implementasi jaringan sesuai kebutuhan khusus.',
                ['Desain topologi', 'Instalasi perangkat', 'Pemeliharaan'], ['Semua sektor'], false],
        ];

        $out = [];
        foreach ($data as $i => [$name, $slug, $icon, $cat, $short, $benefits, $targets, $featured]) {
            $product = Product::create([
                'name' => $name,
                'slug' => $slug,
                'icon' => $icon,
                'short_description' => $short,
                'description' => '<p>'.e($short).' Hubungi tim kami untuk konsultasi kebutuhan, survei lokasi, dan penawaran yang sesuai.</p>',
                'bandwidth_label' => 'Sesuai kebutuhan',
                'sla' => 'Sesuai kontrak layanan',
                'benefits' => $benefits,
                'target_customers' => $targets,
                'price_label' => 'Hubungi kami',
                'show_price' => false,
                'cta_label' => 'Minta Penawaran',
                'cta_url' => '/kontak',
                'status' => ProductStatus::Published,
                'is_featured' => $featured,
                'sort_order' => $i + 1,
                'meta_description' => $short,
            ]);
            $product->categories()->attach($categories[$cat]->id);
            $product->specifications()->createMany([
                ['label' => 'Bandwidth', 'value' => 'Sesuai paket yang dipilih', 'sort_order' => 1],
                ['label' => 'Media akses', 'value' => 'Fiber optik (sesuai ketersediaan)', 'sort_order' => 2],
                ['label' => 'Dukungan', 'value' => 'Tim teknis & account manager', 'sort_order' => 3],
            ]);
            $product->faqs()->createMany([
                ['question' => "Bagaimana cara berlangganan {$name}?", 'answer' => 'Isi formulir konsultasi atau hubungi WhatsApp kami. Tim akan melakukan survei dan memberikan penawaran.', 'sort_order' => 1],
                ['question' => 'Apakah tersedia di lokasi saya?', 'answer' => 'Ketersediaan bergantung pada jangkauan jaringan. Tim kami akan memeriksa lokasi Anda.', 'sort_order' => 2],
            ]);
            $out[$slug] = $product;
        }

        return $out;
    }

    /** @return array<string, IndustrySolution> */
    private function industrySolutions(array $products): array
    {
        $rows = [
            ['Government', 'pemerintahan', 'building-library', 'Konektivitas aman dan andal untuk layanan publik.', ['astinet', 'vpn']],
            ['Education', 'pendidikan', 'academic-cap', 'Internet dan Wi-Fi kampus untuk pembelajaran digital.', ['dedicated-internet', 'managed-wifi']],
            ['Healthcare', 'kesehatan', 'heart', 'Jaringan stabil untuk sistem informasi rumah sakit.', ['astinet', 'vpn', 'managed-wifi']],
            ['Banking', 'perbankan', 'banknotes', 'Koneksi antar-cabang yang aman dan redundan.', ['astinet', 'vpn']],
            ['Hospitality', 'perhotelan', 'home-modern', 'Wi-Fi tamu terkelola dan internet operasional hotel.', ['managed-wifi', 'business-broadband']],
            ['Manufacturing', 'manufaktur', 'wrench-screwdriver', 'Jaringan pabrik, CCTV, dan konektivitas kawasan industri.', ['dedicated-internet', 'cctv-smart-solutions']],
            ['Retail', 'retail', 'shopping-bag', 'Internet toko dan konektivitas sistem kasir multi-outlet.', ['indibiz', 'vpn']],
            ['Enterprise', 'enterprise', 'briefcase', 'Solusi end-to-end untuk korporasi dengan banyak kantor.', ['astinet', 'dedicated-internet', 'vpn']],
        ];

        $out = [];
        foreach ($rows as $i => [$name, $slug, $icon, $desc, $productSlugs]) {
            $solution = IndustrySolution::create([
                'name' => $name, 'slug' => $slug, 'icon' => $icon, 'short_description' => $desc,
                'description' => '<p>'.e($desc).'</p>', 'sort_order' => $i + 1, 'is_visible' => true,
            ]);
            $solution->products()->attach(collect($productSlugs)->map(fn ($s) => $products[$s]->id)->all());
            $out[$slug] = $solution;
        }

        return $out;
    }

    private function marketing(array $solutions): void
    {
        // Angka contoh yang aman (jumlah layanan/sektor di seed). Ganti dengan data perusahaan yang terverifikasi.
        foreach ([['8', null, 'Layanan bisnis'], ['8', null, 'Sektor industri'], ['24/7', null, 'Konsultasi online']] as $i => [$value, $suffix, $label]) {
            Statistic::create(['value' => $value, 'suffix' => $suffix, 'label' => $label, 'sort_order' => $i + 1, 'is_visible' => true]);
        }

        // Contoh — WAJIB diganti dengan testimoni asli (dengan izin pelanggan) atau disembunyikan.
        Testimonial::create(['name' => 'Contoh Testimoni', 'position' => 'Ganti melalui CMS', 'company' => 'Nama Perusahaan Pelanggan',
            'quote' => 'Testimoni contoh. Ganti dengan testimoni pelanggan asli melalui menu Marketing → Testimonials.', 'rating' => 5, 'is_visible' => false]);

        CaseStudy::create([
            'title' => 'Contoh Studi Kasus: Konektivitas Multi-Cabang', 'slug' => 'contoh-studi-kasus', 'client_name' => 'Contoh Klien',
            'industry_solution_id' => $solutions['enterprise']->id,
            'summary' => 'Contoh struktur studi kasus. Ganti dengan proyek nyata atau sembunyikan.',
            'challenge' => '<p>Tantangan pelanggan.</p>', 'solution' => '<p>Solusi yang diterapkan.</p>', 'result' => '<p>Hasil yang dicapai.</p>',
            'is_visible' => false,
        ]);

        $faqs = [
            ['Berapa lama proses instalasi?', 'Waktu instalasi bergantung pada hasil survei lokasi dan ketersediaan jaringan. Tim kami akan menginformasikan estimasinya.'],
            ['Apakah tersedia layanan untuk banyak cabang?', 'Ya. Kami menyediakan solusi VPN dan internet untuk perusahaan dengan banyak lokasi.'],
            ['Bagaimana cara mendapatkan penawaran?', 'Isi formulir konsultasi atau hubungi kami melalui WhatsApp. Penawaran disesuaikan dengan kebutuhan Anda.'],
            ['Apakah ada dukungan teknis 24 jam?', 'Layanan dukungan mengikuti ketentuan pada kontrak layanan masing-masing produk.'],
        ];
        foreach ($faqs as $i => [$q, $a]) {
            Faq::create(['group' => 'general', 'question' => $q, 'answer' => $a, 'sort_order' => $i + 1, 'is_visible' => true]);
        }
    }

    /** @return array<string, Page> */
    private function pages(): array
    {
        $registry = app(SectionRegistry::class);
        $section = function (string $type, array $content = [], array $design = [], array $responsive = []) use ($registry) {
            $defaults = $registry->get($type)->defaults();

            return [
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'content' => array_replace_recursive($defaults['content'], $content),
                'design' => array_replace($defaults['design'], $design),
                'responsive' => array_replace_recursive($defaults['responsive'], $responsive),
                'is_visible' => true,
            ];
        };

        $global = $this->createPage('CTA Konsultasi', 'global-cta-konsultasi', PageType::Global, [
            $section('cta'),
        ]);

        $definitions = [
            'home' => ['Beranda', 'beranda', PageType::Home, [
                $section('hero'),
                $section('logo_carousel'),
                $section('statistics'),
                $section('product_cards', ['heading' => 'Produk Unggulan', 'subheading' => 'Layanan konektivitas untuk berbagai kebutuhan bisnis.', 'source' => 'featured', 'limit' => 4], [], ['desktop' => ['columns' => 4]]),
                $section('features', ['eyebrow' => 'Solusi Bisnis', 'heading' => 'Solusi Lengkap untuk Bisnis Anda', 'items' => [
                    ['icon' => 'globe-alt', 'title' => 'Internet Dedicated', 'description' => 'Bandwidth khusus untuk aplikasi kritikal.'],
                    ['icon' => 'lock-closed', 'title' => 'Jaringan Privat (VPN)', 'description' => 'Hubungkan kantor pusat dan cabang dengan aman.'],
                    ['icon' => 'wifi', 'title' => 'Managed Wi-Fi', 'description' => 'Wi-Fi terkelola untuk tamu dan karyawan.'],
                    ['icon' => 'video-camera', 'title' => 'CCTV & Smart Solutions', 'description' => 'Pemantauan dan otomasi terintegrasi.'],
                ]], ['background' => 'white'], ['desktop' => ['columns' => 4]]),
                $section('features'),
                $section('rich_text', ['heading' => 'Jangkauan Jaringan', 'body' => '<p>Kami melayani area <strong>&lt;WILAYAH_LAYANAN&gt;</strong> dan sekitarnya. Ketersediaan layanan di lokasi Anda dapat dicek melalui tim kami.</p><p><a href="/kontak">Cek ketersediaan jaringan di lokasi Anda</a></p>'], ['background' => 'surface', 'text_align' => 'center']),
                $section('industry_solutions'),
                $section('testimonials'),
                $section('case_studies'),
                $section('faq'),
                $section('global_section', ['page_id' => $global->id]),
            ]],
            'about' => ['Tentang Kami', 'tentang-kami', PageType::Page, [
                $section('heading', ['heading' => 'Tentang Kami', 'level' => 'h1', 'subheading' => 'Mitra konektivitas untuk bisnis dan institusi.'], ['background' => 'surface']),
                $section('rich_text', ['body' => '<p>Ganti dengan profil perusahaan Anda melalui Visual Page Builder.</p>']),
                $section('global_section', ['page_id' => $global->id]),
            ]],
            'vision' => ['Visi dan Misi', 'visi-misi', PageType::Page, [
                $section('heading', ['heading' => 'Visi dan Misi', 'level' => 'h1'], ['background' => 'surface']),
                $section('rich_text', ['body' => '<h2>Visi</h2><p>Tuliskan visi perusahaan.</p><h2>Misi</h2><ul><li>Misi pertama</li><li>Misi kedua</li></ul>']),
            ]],
            'advantages' => ['Keunggulan', 'keunggulan', PageType::Page, [
                $section('heading', ['heading' => 'Keunggulan Kami', 'level' => 'h1'], ['background' => 'surface']),
                $section('features'),
                $section('statistics'),
            ]],
            'infrastructure' => ['Infrastruktur', 'infrastruktur', PageType::Page, [
                $section('heading', ['heading' => 'Infrastruktur Jaringan', 'level' => 'h1'], ['background' => 'surface']),
                $section('rich_text', ['body' => '<p>Jelaskan infrastruktur jaringan, NOC, dan backbone Anda.</p>']),
            ]],
            'partners' => ['Mitra', 'mitra', PageType::Page, [
                $section('heading', ['heading' => 'Mitra Kami', 'level' => 'h1'], ['background' => 'surface']),
                $section('logo_carousel', ['heading' => null], ['animate' => false]),
            ]],
            'contact' => ['Kontak', 'kontak', PageType::Page, [
                $section('heading', ['heading' => 'Hubungi Kami', 'level' => 'h1', 'subheading' => 'Konsultasikan kebutuhan jaringan bisnis Anda.'], ['background' => 'surface']),
                $section('contact_form', [], ['background' => 'white']),
                $section('map'),
            ]],
        ];

        $pages = ['global' => $global];
        foreach ($definitions as $key => [$title, $slug, $type, $sections]) {
            $pages[$key] = $this->createPage($title, $slug, $type, $sections);
        }

        return $pages;
    }

    private function createPage(string $title, string $slug, PageType $type, array $sections): Page
    {
        $page = Page::create(['title' => $title, 'slug' => $slug, 'type' => $type]);
        $page = app(SaveDraft::class)->execute($page, $sections, $page->lock_version, null);

        return app(PublishPage::class)->execute($page, $page->lock_version, null, 'Konten awal');
    }

    private function navigation(array $pages, array $products): void
    {
        $header = NavigationMenu::create(['key' => 'header', 'name' => 'Menu Utama']);
        $header->items()->create(['label' => 'Beranda', 'link_type' => 'url', 'url' => '/', 'sort_order' => 1]);
        $layanan = $header->items()->create(['label' => 'Layanan', 'link_type' => 'url', 'url' => '/layanan', 'sort_order' => 2]);
        foreach (array_values($products) as $i => $product) {
            $header->items()->create(['parent_id' => $layanan->id, 'label' => $product->name, 'link_type' => 'product', 'product_id' => $product->id, 'sort_order' => $i + 1]);
        }
        $header->items()->create(['label' => 'Solusi Industri', 'link_type' => 'url', 'url' => '/solusi', 'sort_order' => 3]);
        $about = $header->items()->create(['label' => 'Perusahaan', 'link_type' => 'page', 'page_id' => $pages['about']->id, 'sort_order' => 4]);
        foreach (['about', 'vision', 'advantages', 'infrastructure', 'partners'] as $i => $key) {
            $header->items()->create(['parent_id' => $about->id, 'label' => $pages[$key]->title, 'link_type' => 'page', 'page_id' => $pages[$key]->id, 'sort_order' => $i + 1]);
        }
        $header->items()->create(['label' => 'Kontak', 'link_type' => 'page', 'page_id' => $pages['contact']->id, 'sort_order' => 5]);

        $footerProducts = NavigationMenu::create(['key' => 'footer_products', 'name' => 'Footer — Layanan']);
        foreach (array_values($products) as $i => $product) {
            $footerProducts->items()->create(['label' => $product->name, 'link_type' => 'product', 'product_id' => $product->id, 'sort_order' => $i + 1]);
        }

        $footerCompany = NavigationMenu::create(['key' => 'footer_company', 'name' => 'Footer — Perusahaan']);
        foreach (['about', 'vision', 'advantages', 'infrastructure', 'partners', 'contact'] as $i => $key) {
            $footerCompany->items()->create(['label' => $pages[$key]->title, 'link_type' => 'page', 'page_id' => $pages[$key]->id, 'sort_order' => $i + 1]);
        }
    }
}
