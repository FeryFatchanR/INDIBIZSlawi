<?php

namespace App\Builder\Rendering;

use App\Builder\SectionRegistry;
use App\Builder\Style\StyleTokens;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Support\CmsCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Merender daftar section menjadi HTML. Template diambil dari registry (bukan dari data),
 * section tak dikenal/gagal di-skip agar satu section rusak tidak menjatuhkan halaman.
 */
class PageRenderer
{
    private int $globalDepth = 0;

    public function __construct(private readonly SectionRegistry $registry) {}

    /** Render versi published (di-cache per revision). */
    public function renderPublished(Page $page): string
    {
        $revision = $page->publishedRevision;
        if (! $revision) {
            return '';
        }

        return CmsCache::remember(
            "page:{$page->id}:rev:{$revision->id}",
            fn () => $this->render($revision->sections())
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @param  bool  $editor  mode canvas builder: tampilkan section tersembunyi + atribut seleksi
     */
    public function render(array $sections, bool $editor = false): string
    {
        $sections = collect($sections)
            ->filter(fn ($s) => ($s['parent_uuid'] ?? null) === null)
            ->filter(fn ($s) => $editor || ($s['is_visible'] ?? true))
            ->sortBy('sort_order')
            ->values();

        $media = $this->loadMedia($sections);
        $html = '';

        foreach ($sections as $section) {
            $html .= $this->renderSection($section, $media, $editor);
        }

        return $html;
    }

    /** @param Collection<int, MediaAsset> $media */
    public function renderSection(array $section, Collection $media, bool $editor = false): string
    {
        $typeKey = $section['type'] ?? '';
        if (! $this->registry->has($typeKey)) {
            Log::warning('Section tipe tidak dikenal dilewati.', ['type' => $typeKey, 'uuid' => $section['uuid'] ?? null]);

            return '';
        }

        $type = $this->registry->get($typeKey);
        $content = $section['content'] ?? [];
        $design = array_merge($type->defaults()['design'], $section['design'] ?? []);
        $responsive = $section['responsive'] ?? [];

        try {
            $data = [
                'section' => $section,
                'type' => $type,
                'content' => $content,
                'design' => $design,
                'responsive' => $responsive,
                'media' => $media,
                'editor' => $editor,
                'isDark' => StyleTokens::isDark($design['background'] ?? null),
                'renderer' => $this,
            ] + $type->prepare($content, $design);

            $inner = view($type->view(), $data)->render();

            return view('builder._wrapper', $data + ['inner' => $inner, 'classes' => $this->wrapperClasses($design, $responsive)])->render();
        } catch (Throwable $e) {
            Log::error('Gagal merender section.', ['type' => $typeKey, 'uuid' => $section['uuid'] ?? null, 'error' => $e->getMessage()]);
            report($e);

            return $editor
                ? '<div class="m-4 rounded border border-red-300 bg-red-50 p-4 text-sm text-red-700" data-section-uuid="'.e($section['uuid'] ?? '').'">Section "'.e($type->label()).'" gagal dirender.</div>'
                : '';
        }
    }

    /** Render global section (versi published) dengan proteksi rekursi. */
    public function renderGlobal(?int $pageId): string
    {
        if (! $pageId || $this->globalDepth >= 1) {
            return '';
        }

        $page = Page::query()->with('publishedRevision')->find($pageId);
        if (! $page || ! $page->isPublished()) {
            return '';
        }

        $this->globalDepth++;
        try {
            $sections = array_filter($page->publishedRevision->sections(), fn ($s) => ($s['type'] ?? null) !== 'global_section');

            return $this->render(array_values($sections));
        } finally {
            $this->globalDepth--;
        }
    }

    private function wrapperClasses(array $design, array $responsive): string
    {
        return implode(' ', array_filter([
            'relative',
            StyleTokens::background($design['background'] ?? null),
            StyleTokens::text($design['text_color'] ?? null, $design['background'] ?? null),
            StyleTokens::paddingY($design, $responsive),
            StyleTokens::marginTop($design['margin_top'] ?? null),
            StyleTokens::marginBottom($design['margin_bottom'] ?? null),
            StyleTokens::visibility($responsive),
        ]));
    }

    /** Eager-load seluruh media yang direferensikan section (hindari N+1). */
    private function loadMedia(Collection $sections): Collection
    {
        $ids = [];
        foreach ($sections as $section) {
            if (! $this->registry->has($section['type'] ?? '')) {
                continue;
            }
            foreach ($this->registry->get($section['type'])->mediaFields() as $path) {
                foreach ((array) data_get($section['content'] ?? [], $path) as $id) {
                    if (is_numeric($id)) {
                        $ids[] = (int) $id;
                    }
                }
            }
        }

        return $ids === [] ? collect() : MediaAsset::query()->whereIn('id', array_unique($ids))->get()->keyBy('id');
    }
}
