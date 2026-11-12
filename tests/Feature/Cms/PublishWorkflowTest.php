<?php

namespace Tests\Feature\Cms;

use App\Actions\Cms\PublishPage;
use App\Actions\Cms\RestoreRevision;
use App\Actions\Cms\SaveDraft;
use App\Actions\Cms\UnpublishPage;
use App\Enums\PageStatus;
use App\Enums\RevisionKind;
use App\Models\PageRevision;
use App\Models\PageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsPages;
use Tests\TestCase;

class PublishWorkflowTest extends TestCase
{
    use BuildsPages, RefreshDatabase;

    public function test_saving_draft_does_not_change_public_version(): void
    {
        $page = $this->pageWithSections([$this->section('heading', ['heading' => 'Versi Publik'])], ['slug' => 'tentang'], true);
        $this->get('/tentang')->assertOk()->assertSee('Versi Publik');

        $sections = $page->sections()->get()->map->toBuilderArray()->all();
        $sections[0]['content']['heading'] = 'Versi Draft';
        $page = app(SaveDraft::class)->execute($page, $sections, $page->lock_version, null);

        $this->assertDatabaseHas('page_sections', ['page_id' => $page->id]);
        $this->assertSame('Versi Draft', PageSection::where('page_id', $page->id)->first()->content['heading']);
        $this->get('/tentang')->assertOk()->assertSee('Versi Publik')->assertDontSee('Versi Draft');
        $this->assertTrue($page->fresh()->hasUnpublishedChanges());
    }

    public function test_publish_creates_snapshot_and_updates_public_page(): void
    {
        $page = $this->pageWithSections([$this->section('heading', ['heading' => 'Satu'])], ['slug' => 'layanan-khusus'], true);
        $sections = $page->sections()->get()->map->toBuilderArray()->all();
        $sections[0]['content']['heading'] = 'Dua';
        $page = app(SaveDraft::class)->execute($page, $sections, $page->lock_version, null);

        $page = app(PublishPage::class)->execute($page, $page->lock_version, null, 'rilis kedua');

        $this->assertSame(PageStatus::Published, $page->status);
        $this->assertSame(2, PageRevision::where('page_id', $page->id)->where('kind', RevisionKind::Publish)->count());
        $this->assertSame('Dua', $page->publishedRevision->sections()[0]['content']['heading']);
        $this->assertNotNull($page->published_at);
        $this->get('/layanan-khusus')->assertOk()->assertSee('Dua')->assertDontSee('>Satu<', false);
        $this->assertFalse($page->hasUnpublishedChanges());
    }

    public function test_unpublish_hides_page_from_public(): void
    {
        $page = $this->pageWithSections([$this->section('heading', ['heading' => 'Rahasia'])], ['slug' => 'promo'], true);
        $this->get('/promo')->assertOk();

        app(UnpublishPage::class)->execute($page, null);

        $this->assertSame(PageStatus::Unpublished, $page->fresh()->status);
        $this->get('/promo')->assertNotFound();
    }

    public function test_never_published_draft_is_not_public(): void
    {
        $this->pageWithSections([$this->section('heading', ['heading' => 'Draft Saja'])], ['slug' => 'draft-saja']);

        $this->get('/draft-saja')->assertNotFound();
    }

    public function test_restore_revision_replaces_draft_but_not_public_version(): void
    {
        $page = $this->pageWithSections([$this->section('heading', ['heading' => 'Lama'])], ['slug' => 'riwayat'], true);
        $oldRevision = $page->publishedRevision;

        $sections = $page->sections()->get()->map->toBuilderArray()->all();
        $sections[0]['content']['heading'] = 'Baru';
        $page = app(SaveDraft::class)->execute($page, $sections, $page->lock_version, null);
        $page = app(PublishPage::class)->execute($page, $page->lock_version, null);

        $page = app(RestoreRevision::class)->execute($page, $oldRevision, $page->lock_version, null);

        $this->assertSame('Lama', $page->sections()->first()->content['heading']);
        $this->assertDatabaseHas('page_revisions', ['page_id' => $page->id, 'kind' => 'restore']);
        $this->get('/riwayat')->assertSee('Baru'); // publik tetap versi terakhir yang dipublish
        $page = app(PublishPage::class)->execute($page, $page->lock_version, null);
        $this->get('/riwayat')->assertSee('Lama');
    }

    public function test_cannot_restore_revision_of_another_page(): void
    {
        $a = $this->pageWithSections([$this->section('heading')], [], true);
        $b = $this->pageWithSections([$this->section('heading')], [], true);

        $this->expectException(ValidationException::class);
        app(RestoreRevision::class)->execute($a, $b->publishedRevision, null, null);
    }

    public function test_revisions_are_immutable(): void
    {
        $page = $this->pageWithSections([$this->section('heading', ['heading' => 'Asli'])], [], true);
        $revision = $page->publishedRevision;

        $revision->snapshot = ['sections' => []];
        $this->assertFalse($revision->save());
        $this->assertSame('Asli', $revision->fresh()->sections()[0]['content']['heading']);
    }

    public function test_homepage_cannot_be_unpublished(): void
    {
        $home = $this->homePage();

        $this->expectException(ValidationException::class);
        app(UnpublishPage::class)->execute($home, null);
    }

    public function test_draft_checkpoints_are_pruned_but_publish_revisions_kept(): void
    {
        config(['cms.builder.max_draft_revisions' => 2]);
        $page = $this->pageWithSections([$this->section('heading')], [], true);
        $sections = $page->sections()->get()->map->toBuilderArray()->all();

        for ($i = 0; $i < 5; $i++) {
            $page = app(SaveDraft::class)->execute($page, $sections, $page->lock_version, null, true, "cp {$i}");
        }

        $this->assertSame(2, PageRevision::where('page_id', $page->id)->where('kind', 'draft')->count());
        $this->assertSame(1, PageRevision::where('page_id', $page->id)->where('kind', 'publish')->count());
    }
}
