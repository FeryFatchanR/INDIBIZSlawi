<?php

namespace Tests\Feature\Builder;

use App\Actions\Cms\SaveDraft;
use App\Filament\Resources\Pages\Pages\BuildPage;
use App\Models\Page;
use App\Models\PageSection;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Concerns\BuildsPages;
use Tests\TestCase;

class BuilderOperationsTest extends TestCase
{
    use BuildsPages, RefreshDatabase;

    private Page $page;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->actingAsRole(Roles::ADMINISTRATOR);
        $this->page = $this->pageWithSections([
            $this->section('heading', ['heading' => 'Pertama']),
            $this->section('rich_text', ['heading' => 'Kedua']),
        ]);
    }

    private function builder()
    {
        return Livewire::test(BuildPage::class, ['record' => $this->page->getRouteKey()]);
    }

    private function dbSections(): array
    {
        return PageSection::where('page_id', $this->page->id)->orderBy('sort_order')->get()->all();
    }

    public function test_add_section_inserts_at_index_with_new_uuid(): void
    {
        $this->builder()->call('addSection', 'cta', 1)->assertHasNoErrors();

        $sections = $this->dbSections();
        $this->assertCount(3, $sections);
        $this->assertSame(['heading', 'cta', 'rich_text'], array_map(fn ($s) => $s->type, $sections));
        $this->assertSame([0, 1, 2], array_map(fn ($s) => $s->sort_order, $sections));
        $this->assertTrue(Str::isUuid($sections[1]->uuid));
    }

    public function test_invalid_component_type_is_rejected(): void
    {
        $this->builder()->call('addSection', 'php_eval', 0);
        $this->assertCount(2, $this->dbSections());
    }

    public function test_update_properties_through_property_editor(): void
    {
        $uuid = $this->dbSections()[0]->uuid;

        $this->builder()
            ->call('selectSection', $uuid)
            ->set('selectedData.content.heading', 'Judul Diubah')
            ->set('selectedData.design.background', 'primary')
            ->set('selectedData.responsive.mobile.visible', false)
            ->call('applyProperties')
            ->assertHasNoErrors();

        $section = PageSection::where('uuid', $uuid)->first();
        $this->assertSame('Judul Diubah', $section->content['heading']);
        $this->assertSame('primary', $section->design['background']);
        $this->assertFalse($section->responsive['mobile']['visible']);
    }

    public function test_property_editor_rejects_non_whitelisted_values(): void
    {
        $uuid = $this->dbSections()[0]->uuid;

        $this->builder()
            ->call('selectSection', $uuid)
            ->set('selectedData.design.background', 'url(javascript:alert(1))')
            ->call('applyProperties');

        $this->assertSame('none', PageSection::where('uuid', $uuid)->first()->design['background']);
    }

    public function test_duplicate_section_creates_independent_copy(): void
    {
        $original = $this->dbSections()[0];

        $this->builder()->call('duplicateSection', $original->uuid);

        $sections = $this->dbSections();
        $this->assertCount(3, $sections);
        $this->assertSame('heading', $sections[1]->type);
        $this->assertSame($original->content, $sections[1]->content);
        $this->assertNotSame($original->uuid, $sections[1]->uuid);
    }

    public function test_delete_section(): void
    {
        $uuid = $this->dbSections()[0]->uuid;
        $this->builder()->call('deleteSection', $uuid);

        $this->assertDatabaseMissing('page_sections', ['uuid' => $uuid]);
        $this->assertCount(1, $this->dbSections());
    }

    public function test_reorder_sections_keeps_all_configuration(): void
    {
        [$a, $b] = $this->dbSections();

        $this->builder()->call('reorder', [$b->uuid, $a->uuid]);

        $sections = $this->dbSections();
        $this->assertSame([$b->uuid, $a->uuid], array_map(fn ($s) => $s->uuid, $sections));
        $this->assertSame('Kedua', $sections[0]->content['heading']);
        $this->assertSame('Pertama', $sections[1]->content['heading']);
    }

    public function test_reorder_with_tampered_set_does_not_duplicate_or_lose_sections(): void
    {
        [$a, $b] = $this->dbSections();

        $this->builder()->call('reorder', [$a->uuid, $a->uuid]);
        $this->builder()->call('reorder', [$a->uuid]);
        $this->builder()->call('reorder', [$a->uuid, $b->uuid, (string) Str::uuid()]);

        $this->assertSame([$a->uuid, $b->uuid], array_map(fn ($s) => $s->uuid, $this->dbSections()));
    }

    public function test_move_buttons_and_visibility_toggle(): void
    {
        [$a, $b] = $this->dbSections();

        $this->builder()->call('moveSection', $b->uuid, 'up')->call('toggleVisibility', $a->uuid);

        $sections = $this->dbSections();
        $this->assertSame($b->uuid, $sections[0]->uuid);
        $this->assertFalse($sections[1]->is_visible);
    }

    public function test_inline_edit_only_allows_plain_text_schema_fields(): void
    {
        $uuid = $this->dbSections()[0]->uuid;

        $this->builder()
            ->call('inlineEdit', $uuid, 'heading', '<b>Inline</b> Edit')
            ->call('inlineEdit', $uuid, 'level', 'script');

        $section = PageSection::where('uuid', $uuid)->first();
        $this->assertSame('Inline Edit', $section->content['heading']);
        $this->assertSame('h2', $section->content['level']);
    }

    public function test_responsive_configuration_is_stored_per_device(): void
    {
        $this->builder()->call('addSection', 'features', 0);
        $uuid = $this->dbSections()[0]->uuid;

        $this->builder()
            ->call('selectSection', $uuid)
            ->set('selectedData.responsive.desktop.columns', 4)
            ->set('selectedData.responsive.tablet.columns', 2)
            ->set('selectedData.responsive.mobile.columns', 1)
            ->call('applyProperties');

        $responsive = PageSection::where('uuid', $uuid)->first()->responsive;
        $this->assertSame([4, 2, 1], [$responsive['desktop']['columns'], $responsive['tablet']['columns'], $responsive['mobile']['columns']]);

        $html = $this->get(route('pages.preview', $this->page))->getContent();
        $this->assertStringContainsString('grid-cols-1 md:grid-cols-2 lg:grid-cols-4', $html);
    }

    public function test_stale_builder_session_is_blocked(): void
    {
        $component = $this->builder();

        // Pengguna lain menyimpan perubahan setelah builder dibuka.
        app(SaveDraft::class)->execute($this->page, $this->page->sections()->get()->map->toBuilderArray()->all(), $this->page->fresh()->lock_version, null);

        $component->call('addSection', 'cta', 0)->assertSet('stale', true);
        $this->assertCount(2, $this->dbSections());
    }

    public function test_publish_from_builder(): void
    {
        $this->page->update(['slug' => 'dari-builder']);
        $this->builder()->callAction('publish', ['note' => 'tes'])->assertHasNoActionErrors();

        $this->assertTrue($this->page->fresh()->isPublished());
        $this->get('/dari-builder')->assertOk()->assertSee('Pertama');
    }

    public function test_custom_html_component_requires_permission(): void
    {
        $this->builder()->call('addSection', 'custom_html', 0);
        $this->assertCount(2, $this->dbSections());
    }
}
