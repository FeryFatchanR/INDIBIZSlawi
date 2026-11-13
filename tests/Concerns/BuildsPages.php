<?php

namespace Tests\Concerns;

use App\Actions\Cms\PublishPage;
use App\Actions\Cms\SaveDraft;
use App\Builder\SectionRegistry;
use App\Enums\PageType;
use App\Models\Page;
use Illuminate\Support\Str;

trait BuildsPages
{
    protected function section(string $type, array $content = [], array $design = [], array $responsive = []): array
    {
        $defaults = app(SectionRegistry::class)->get($type)->defaults();

        return [
            'uuid' => (string) Str::uuid(),
            'type' => $type,
            'content' => array_replace_recursive($defaults['content'], $content),
            'design' => array_replace($defaults['design'], $design),
            'responsive' => array_replace_recursive($defaults['responsive'], $responsive),
            'is_visible' => true,
        ];
    }

    protected function pageWithSections(array $sections, array $attributes = [], bool $publish = false): Page
    {
        $page = Page::factory()->create($attributes);
        $page = app(SaveDraft::class)->execute($page, $sections, $page->lock_version, null);

        return $publish ? app(PublishPage::class)->execute($page, $page->lock_version, null) : $page;
    }

    protected function homePage(string $heading = 'Judul Beranda Publik'): Page
    {
        return $this->pageWithSections([$this->section('hero', ['heading' => $heading])], ['type' => PageType::Home, 'slug' => 'beranda'], true);
    }
}
