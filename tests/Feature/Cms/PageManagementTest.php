<?php

namespace Tests\Feature\Cms;

use App\Enums\PageType;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Page;
use App\Support\Roles;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsPages;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    use BuildsPages, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->actingAsRole(Roles::ADMINISTRATOR);
    }

    public function test_create_page_as_draft_and_redirect_to_builder(): void
    {
        Livewire::test(CreatePage::class)
            ->fillForm(['title' => 'Layanan Korporat', 'slug' => 'layanan-korporat', 'type' => 'page'])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $page = Page::where('slug', 'layanan-korporat')->firstOrFail();
        $this->assertSame('draft', $page->status->value);
        $this->assertNotNull($page->uuid);
        $this->assertNotNull($page->created_by);
        $this->get('/layanan-korporat')->assertNotFound();
    }

    public function test_slug_validation_and_reserved_slugs(): void
    {
        Page::factory()->create(['slug' => 'sudah-ada']);

        foreach (['admin', 'layanan', 'Huruf Besar', 'sudah-ada', 'a/b'] as $slug) {
            Livewire::test(CreatePage::class)
                ->fillForm(['title' => 'X', 'slug' => $slug, 'type' => 'page'])
                ->call('create')
                ->assertHasFormErrors(['slug']);
        }
    }

    public function test_only_one_homepage_allowed(): void
    {
        $this->homePage();

        Livewire::test(CreatePage::class)
            ->fillForm(['title' => 'Home 2', 'slug' => 'home-2', 'type' => PageType::Home->value])
            ->call('create')
            ->assertHasFormErrors(['type']);
    }

    public function test_update_page_settings(): void
    {
        $page = $this->pageWithSections([$this->section('heading')]);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['title' => 'Judul Baru', 'meta_description' => 'Deskripsi SEO'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Judul Baru', $page->fresh()->title);
        $this->assertSame('Deskripsi SEO', $page->fresh()->meta_description);
    }

    public function test_delete_page_soft_deletes_and_home_cannot_be_deleted(): void
    {
        $page = $this->pageWithSections([$this->section('heading')], ['slug' => 'hapus-saya'], true);
        $home = $this->homePage();

        Livewire::test(ListPages::class)
            ->callAction(TestAction::make('delete')->table($page))
            ->assertActionHidden(TestAction::make('delete')->table($home));

        $this->assertSoftDeleted($page);
        $this->get('/hapus-saya')->assertNotFound();
    }

    public function test_publish_and_unpublish_from_page_list(): void
    {
        $page = $this->pageWithSections([$this->section('heading', ['heading' => 'Dari Daftar'])], ['slug' => 'dari-daftar']);

        Livewire::test(ListPages::class)->callAction(TestAction::make('publish')->table($page));
        $this->get('/dari-daftar')->assertOk()->assertSee('Dari Daftar');

        Livewire::test(ListPages::class)->callAction(TestAction::make('unpublish')->table($page->fresh()));
        $this->get('/dari-daftar')->assertNotFound();
    }

    public function test_marketing_cannot_publish(): void
    {
        $page = $this->pageWithSections([$this->section('heading')]);
        $marketing = $this->actingAsRole(Roles::MARKETING);

        $this->assertFalse($marketing->can('publish', $page));
        $this->assertFalse($marketing->can('update', $page));
        Livewire::test(ListPages::class)->assertActionHidden(TestAction::make('publish')->table($page));
    }
}
