<?php

namespace Tests\Feature\Public;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Tests\Concerns\BuildsPages;
use Tests\TestCase;

/**
 * Regresi: HTML halaman builder di-cache, tetapi form lead (token CSRF, token waktu anti-bot,
 * pesan sukses/error) harus selalu dirender per request.
 */
class LeadFormCachingTest extends TestCase
{
    use BuildsPages, RefreshDatabase;

    private function contactPage(): void
    {
        $this->pageWithSections([
            $this->section('heading', ['heading' => 'Hubungi Kami']),
            $this->section('contact_form', ['heading' => 'Form Konsultasi']),
        ], ['slug' => 'kontak'], true);
    }

    private function tokenFrom(string $html, string $name): ?string
    {
        return preg_match('/name="'.$name.'" value="([^"]+)"/', $html, $m) ? html_entity_decode($m[1]) : null;
    }

    private function validPayload(string $html): array
    {
        return [
            '_token' => $this->tokenFrom($html, '_token'),
            '_ts' => Crypt::encryptString((string) (time() - 10)),
            'company_name' => 'PT Uji Cache', 'contact_name' => 'Rina', 'phone' => '081234567890',
            'type' => 'consultation', 'consent' => '1',
        ];
    }

    public function test_each_session_gets_its_own_csrf_token_on_cached_builder_page(): void
    {
        $this->contactPage();

        $first = $this->get('/kontak')->assertOk()->getContent();
        $tokenA = $this->tokenFrom($first, '_token');

        // Sesi baru (pengunjung lain) setelah cache halaman terisi.
        Session::flush();
        Session::regenerateToken();
        $second = $this->get('/kontak')->assertOk()->getContent();
        $tokenB = $this->tokenFrom($second, '_token');

        $this->assertNotNull($tokenA);
        $this->assertNotSame($tokenA, $tokenB);
        $this->assertSame(session()->token(), $tokenB);
        $this->assertStringContainsString('Hubungi Kami', $second); // bagian statis tetap dari cache
    }

    public function test_bot_timer_token_is_issued_per_request_not_at_cache_time(): void
    {
        $this->contactPage();
        $this->get('/kontak')->assertOk(); // isi cache

        $this->travel(2)->days();
        $html = $this->get('/kontak')->assertOk()->getContent();
        $issuedAt = (int) Crypt::decryptString($this->tokenFrom($html, '_ts'));

        $this->assertEqualsWithDelta(now()->timestamp, $issuedAt, 5);
    }

    public function test_success_message_appears_on_cached_builder_page_after_submit(): void
    {
        $this->contactPage();
        $html = $this->get('/kontak')->getContent(); // cache terisi tanpa pesan sukses

        $this->from('/kontak')->post('/leads', $this->validPayload($html))->assertRedirect();
        $this->assertDatabaseHas('leads', ['company_name' => 'PT Uji Cache']);

        $this->get('/kontak')->assertOk()->assertSee('Terima kasih! Permintaan Anda sudah kami terima.');
        // Request berikutnya kembali menampilkan form (flash tidak ikut tersimpan di cache).
        $this->get('/kontak')->assertOk()->assertDontSee('Terima kasih!')->assertSee('name="company_name"', false);
    }

    public function test_validation_errors_appear_in_indonesian_on_cached_builder_page(): void
    {
        $this->contactPage();
        $html = $this->get('/kontak')->getContent();

        $payload = array_merge($this->validPayload($html), ['contact_name' => '']);
        $this->from('/kontak')->post('/leads', $payload)->assertRedirect('/kontak');

        $this->get('/kontak')->assertOk()
            ->assertSee('Nama kontak wajib diisi.')
            ->assertSee('Mohon periksa kembali isian yang ditandai.');
    }

    public function test_contact_form_inside_global_section_is_rendered_live(): void
    {
        $global = $this->pageWithSections([$this->section('contact_form')], ['type' => 'global', 'slug' => 'form-global'], true);
        $this->pageWithSections([$this->section('global_section', ['page_id' => $global->id])], ['slug' => 'pakai-form'], true);

        $tokenA = $this->tokenFrom($this->get('/pakai-form')->getContent(), '_token');
        Session::flush();
        Session::regenerateToken();
        $tokenB = $this->tokenFrom($this->get('/pakai-form')->getContent(), '_token');

        $this->assertNotNull($tokenA);
        $this->assertNotSame($tokenA, $tokenB);
    }

    public function test_static_sections_are_still_served_from_cache(): void
    {
        $page = $this->pageWithSections([$this->section('heading', ['heading' => 'Judul Asli'])], ['slug' => 'statis'], true);
        $this->get('/statis')->assertSee('Judul Asli');

        // Ubah snapshot langsung di DB tanpa bump cache: halaman tetap menyajikan versi cache.
        $snapshot = $page->publishedRevision->snapshot;
        $snapshot['sections'][0]['content']['heading'] = 'Judul Diubah';
        DB::table('page_revisions')->where('id', $page->published_revision_id)->update(['snapshot' => json_encode($snapshot)]);

        $this->get('/statis')->assertSee('Judul Asli')->assertDontSee('Judul Diubah');
    }

    public function test_no_product_is_preselected_unless_requested(): void
    {
        Product::factory()->create(['name' => 'Astinet', 'slug' => 'astinet']);
        Product::factory()->create(['name' => 'IndiBiz', 'slug' => 'indibiz']);
        $this->contactPage();

        $html = $this->get('/kontak')->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression('/<option value="\d+"\s+selected/', $html);

        $astinet = Product::where('slug', 'astinet')->value('id');
        $html = $this->get('/kontak?produk=astinet')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<option value="'.$astinet.'"\s+selected/', $html);

        $html = $this->get('/kontak?produk=tidak-ada')->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression('/<option value="\d+"\s+selected/', $html);
    }

    public function test_validation_messages_are_indonesian(): void
    {
        $this->post('/leads', ['type' => 'contact'])
            ->assertSessionHasErrors([
                'company_name' => 'Nama perusahaan wajib diisi.',
                'consent' => 'Anda perlu menyetujui agar kami dapat menghubungi Anda.',
            ]);
    }
}
