<?php

namespace Tests\Feature\Public;

use App\Models\IndustrySolution;
use App\Models\Product;
use App\Support\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsPages;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use BuildsPages, RefreshDatabase;

    public function test_homepage_renders_published_builder_content_with_seo(): void
    {
        $this->homePage('Internet Bisnis Andal');

        $this->get('/')->assertOk()
            ->assertSee('Internet Bisnis Andal')
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('application/ld+json', false)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_product_detail_is_dynamic_and_drafts_are_hidden(): void
    {
        $product = Product::factory()->create(['name' => 'Dedicated Uji', 'slug' => 'dedicated-uji', 'sla' => 'SLA Uji 99']);
        $product->specifications()->create(['label' => 'Media', 'value' => 'Fiber Uji']);
        $draft = Product::factory()->draft()->create(['slug' => 'rahasia']);

        $this->get('/layanan/dedicated-uji')->assertOk()->assertSee('Dedicated Uji')->assertSee('SLA Uji 99')->assertSee('Fiber Uji')
            ->assertSee('"@type":"Service"', false);
        $this->get('/layanan/rahasia')->assertNotFound();
        $this->get('/layanan')->assertOk()->assertSee('Dedicated Uji')->assertDontSee($draft->name);
    }

    public function test_soft_deleted_product_is_not_public(): void
    {
        $product = Product::factory()->create(['slug' => 'dihapus']);
        $product->delete();

        $this->get('/layanan/dihapus')->assertNotFound();
    }

    public function test_industry_solution_pages(): void
    {
        IndustrySolution::create(['name' => 'Perbankan', 'slug' => 'perbankan', 'is_visible' => true, 'short_description' => 'Solusi bank']);
        IndustrySolution::create(['name' => 'Tersembunyi', 'slug' => 'tersembunyi', 'is_visible' => false]);

        $this->get('/solusi')->assertOk()->assertSee('Perbankan')->assertDontSee('Tersembunyi');
        $this->get('/solusi/perbankan')->assertOk()->assertSee('Solusi bank');
        $this->get('/solusi/tersembunyi')->assertNotFound();
    }

    public function test_global_section_has_no_public_url_but_renders_inside_pages(): void
    {
        $global = $this->pageWithSections([$this->section('cta', ['heading' => 'CTA Global Uji'])], ['type' => 'global', 'slug' => 'cta-global'], true);
        $this->pageWithSections([$this->section('global_section', ['page_id' => $global->id])], ['slug' => 'pakai-global'], true);

        $this->get('/cta-global')->assertNotFound();
        $this->get('/pakai-global')->assertOk()->assertSee('CTA Global Uji');
    }

    public function test_hidden_and_device_specific_sections(): void
    {
        $hidden = $this->section('heading', ['heading' => 'Tidak Tampil']);
        $hidden['is_visible'] = false;
        $this->pageWithSections([
            $hidden,
            $this->section('heading', ['heading' => 'Desktop Saja'], [], ['mobile' => ['visible' => false], 'tablet' => ['visible' => false]]),
        ], ['slug' => 'visibilitas'], true);

        $html = $this->get('/visibilitas')->assertOk()->assertDontSee('Tidak Tampil')->getContent();
        $this->assertMatchesRegularExpression('/class="[^"]*hidden md:hidden lg:block[^"]*"[^>]*data-section="heading"/', $html);
    }

    public function test_sitemap_and_robots(): void
    {
        $this->homePage();
        Product::factory()->create(['slug' => 'ada-di-sitemap']);
        Product::factory()->draft()->create(['slug' => 'draft-tidak-ada']);
        $this->pageWithSections([$this->section('heading')], ['slug' => 'halaman-draft']);

        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('/layanan/ada-di-sitemap', false)
            ->assertDontSee('draft-tidak-ada')
            ->assertDontSee('halaman-draft');

        $this->get('/robots.txt')->assertOk()->assertSee('User-agent');
    }

    public function test_whatsapp_redirect_records_click_and_uses_settings_number(): void
    {
        app(SettingsRepository::class)->setGroup('contact', ['whatsapp' => '0812-3456-7890']);
        $product = Product::factory()->create(['name' => 'Astinet', 'slug' => 'astinet']);

        $response = $this->get('/wa?product=astinet');

        $response->assertRedirect();
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $response->headers->get('Location'));
        $this->assertDatabaseHas('whatsapp_clicks', ['product_id' => $product->id]);
    }

    public function test_whatsapp_without_number_falls_back_to_contact(): void
    {
        $this->get('/wa')->assertRedirect('/kontak');
        $this->assertDatabaseCount('whatsapp_clicks', 0);
    }

    public function test_branding_colors_from_settings_are_validated_before_render(): void
    {
        $this->homePage();
        app(SettingsRepository::class)->setGroup('branding', ['primary' => '#ABCDEF', 'accent' => 'red;}</style><script>alert(1)</script>']);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('--brand-primary: #ABCDEF', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('--brand-accent: #00B8D9', $html);
    }
}
