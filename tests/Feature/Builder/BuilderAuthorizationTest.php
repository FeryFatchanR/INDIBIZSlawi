<?php

namespace Tests\Feature\Builder;

use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Pages\Pages\BuildPage;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsPages;
use Tests\TestCase;

class BuilderAuthorizationTest extends TestCase
{
    use BuildsPages, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_guest_cannot_open_builder(): void
    {
        $page = $this->pageWithSections([$this->section('heading')]);
        $this->get(PageResource::getUrl('build', ['record' => $page]))->assertRedirect('/admin/login');
    }

    public function test_marketing_and_sales_cannot_open_builder(): void
    {
        $page = $this->pageWithSections([$this->section('heading')]);

        foreach ([Roles::MARKETING, Roles::SALES] as $role) {
            $this->actingAsRole($role);
            $this->get(PageResource::getUrl('build', ['record' => $page]))->assertForbidden();
        }
    }

    public function test_unauthorized_user_cannot_call_builder_actions_directly(): void
    {
        $page = $this->pageWithSections([$this->section('heading')]);
        $this->actingAsRole(Roles::MARKETING);

        try {
            Livewire::test(BuildPage::class, ['record' => $page->getRouteKey()])->call('addSection', 'cta', 0);
        } catch (\Throwable) {
        }

        $this->assertSame(1, $page->sections()->count());
    }

    public function test_administrator_can_open_builder(): void
    {
        $page = $this->pageWithSections([$this->section('heading', ['heading' => 'Bisa Dibuka'])]);
        $this->actingAsRole(Roles::ADMINISTRATOR);

        $this->get(PageResource::getUrl('build', ['record' => $page]))->assertOk()->assertSee('Struktur halaman');
    }

    public function test_draft_preview_requires_authorized_user(): void
    {
        $page = $this->pageWithSections([$this->section('heading', ['heading' => 'Isi Draft Rahasia'])]);
        $url = route('pages.preview', $page);

        $this->get($url)->assertRedirect(route('filament.admin.auth.login'));

        $this->actingAsRole(Roles::SALES);
        $this->get($url)->assertForbidden();

        $this->actingAsRole(Roles::ADMINISTRATOR);
        $this->get($url)->assertOk()->assertSee('Isi Draft Rahasia')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringContainsString('no-store', $this->get($url)->headers->get('Cache-Control'));
    }
}
