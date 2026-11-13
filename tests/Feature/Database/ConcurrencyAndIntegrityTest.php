<?php

namespace Tests\Feature\Database;

use App\Actions\Cms\PublishPage;
use App\Actions\Cms\SaveDraft;
use App\Exceptions\StaleDraftException;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\PageRevision;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\ProductGallery;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsPages;
use Tests\TestCase;

class ConcurrencyAndIntegrityTest extends TestCase
{
    use BuildsPages, RefreshDatabase;

    public function test_concurrent_edit_with_stale_version_is_rejected_without_lost_update(): void
    {
        $page = $this->pageWithSections([$this->section('heading', ['heading' => 'Awal'])]);
        $version = $page->lock_version;
        $sections = $page->sections()->get()->map->toBuilderArray()->all();

        // Admin A menyimpan lebih dulu.
        $a = $sections;
        $a[0]['content']['heading'] = 'Perubahan Admin A';
        app(SaveDraft::class)->execute($page, $a, $version, null);

        // Admin B masih memegang versi lama → ditolak, perubahan A tidak tertimpa.
        $b = $sections;
        $b[0]['content']['heading'] = 'Perubahan Admin B';
        try {
            app(SaveDraft::class)->execute($page, $b, $version, null);
            $this->fail('Seharusnya StaleDraftException.');
        } catch (StaleDraftException $e) {
            $this->assertSame($version, $e->expected);
        }

        $this->assertSame('Perubahan Admin A', PageSection::where('page_id', $page->id)->first()->content['heading']);
        $this->assertSame($version + 1, $page->fresh()->lock_version);
    }

    public function test_publish_with_stale_version_is_rejected(): void
    {
        $page = $this->pageWithSections([$this->section('heading')]);
        $stale = $page->lock_version;
        app(SaveDraft::class)->execute($page, $page->sections()->get()->map->toBuilderArray()->all(), $stale, null);

        $this->expectException(StaleDraftException::class);
        app(PublishPage::class)->execute($page, $stale, null);
    }

    public function test_failed_publish_rolls_back_completely(): void
    {
        $page = $this->pageWithSections([$this->section('heading', ['heading' => 'Valid'])], ['slug' => 'atomik'], true);
        $publishedRevisionId = $page->published_revision_id;
        $revisionCount = PageRevision::count();

        // Simulasikan data rusak di draft (mis. diubah langsung di database).
        PageSection::where('page_id', $page->id)->update(['type' => 'tipe_tidak_dikenal']);

        try {
            app(PublishPage::class)->execute($page->fresh(), null, null);
            $this->fail('Publish seharusnya gagal.');
        } catch (ValidationException) {
        }

        $this->assertSame($revisionCount, PageRevision::count());
        $this->assertSame($publishedRevisionId, $page->fresh()->published_revision_id);
        $this->get('/atomik')->assertOk()->assertSee('Valid');
    }

    public function test_failed_save_draft_keeps_previous_draft(): void
    {
        $page = $this->pageWithSections([$this->section('heading', ['heading' => 'Tetap'])]);

        try {
            app(SaveDraft::class)->execute($page, [['uuid' => 'x', 'type' => 'hero']], $page->lock_version, null);
        } catch (ValidationException) {
        }

        $this->assertSame(1, PageSection::where('page_id', $page->id)->count());
        $this->assertSame('Tetap', PageSection::where('page_id', $page->id)->first()->content['heading']);
    }

    public function test_section_uuid_cannot_be_hijacked_from_another_page(): void
    {
        $a = $this->pageWithSections([$this->section('heading', ['heading' => 'Milik A'])]);
        $b = $this->pageWithSections([$this->section('heading')]);
        $stolen = $a->sections()->first()->toBuilderArray();

        $this->expectException(ValidationException::class);
        app(SaveDraft::class)->execute($b, [$stolen], $b->lock_version, null);
    }

    public function test_unique_constraints(): void
    {
        Product::factory()->create(['slug' => 'astinet']);
        $this->expectException(QueryException::class);
        Product::factory()->create(['slug' => 'astinet']);
    }

    public function test_revision_version_is_unique_per_page(): void
    {
        $page = $this->pageWithSections([$this->section('heading')], [], true);

        $this->expectException(QueryException::class);
        DB::table('page_revisions')->insert(['page_id' => $page->id, 'version' => 1, 'kind' => 'draft', 'snapshot' => '{}', 'created_at' => now()]);
    }

    public function test_foreign_keys_restrict_and_cascade(): void
    {
        $media = MediaAsset::factory()->create();
        $product = Product::factory()->create();
        $gallery = new ProductGallery(['product_id' => $product->id, 'media_asset_id' => $media->id]);
        $gallery->save();

        // Media yang dipakai galeri tidak bisa dihapus permanen (RESTRICT).
        try {
            DB::table('media_assets')->where('id', $media->id)->delete();
            $this->fail('FK restrict seharusnya menolak.');
        } catch (QueryException) {
            $this->assertDatabaseHas('media_assets', ['id' => $media->id]);
        }

        // Hapus permanen produk → galeri ikut terhapus (CASCADE).
        $product->forceDelete();
        $this->assertDatabaseMissing('product_galleries', ['id' => $gallery->id]);

        // Hapus permanen halaman → section & revision ikut terhapus.
        $page = $this->pageWithSections([$this->section('heading')], [], true);
        $page->forceDelete();
        $this->assertSame(0, PageSection::where('page_id', $page->id)->count());
        $this->assertSame(0, PageRevision::where('page_id', $page->id)->count());
    }

    public function test_invalid_foreign_key_is_rejected(): void
    {
        $this->expectException(QueryException::class);
        DB::table('page_sections')->insert(['uuid' => (string) Str::uuid(), 'page_id' => 999999, 'type' => 'heading', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_page_slug_is_unique(): void
    {
        Page::factory()->create(['slug' => 'kontak']);
        $this->expectException(QueryException::class);
        Page::factory()->create(['slug' => 'kontak']);
    }
}
