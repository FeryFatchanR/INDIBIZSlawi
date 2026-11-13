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
    private const PLACEHOLDER = '<!--cms-dynamic:%s-->';

    private int $globalDepth = 0;

    /**
     * Section dinamis yang ditunda saat merender versi cache (uuid => section). null = mode normal.
     *
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $deferred = null;

    public function __construct(private readonly SectionRegistry $registry) {}

    /**
     * Render versi published. Bagian statis di-cache per revision; section dinamis (mis. form lead dengan token
     * CSRF, token waktu anti-bot, pesan sukses/error) disimpan sebagai placeholder lalu dirender ulang per request.
     */
    public function renderPublished(Page $page): string
    {
        $revision = $page->publishedRevision;
        if (! $revision) {
            return '';
        }

        $cached = CmsCache::remember("page:{$page->id}:rev:{$revision->id}:v2", function () use ($revision) {
            $this->deferred = [];
            try {
                $html = $this->render($revision->sections());

                return ['html' => $html, 'deferred' => $this->deferred];
            } finally {
                $this->deferred = null;
            }
        });

        return $this->fillDynamic((string) ($cached['html'] ?? ''), (array) ($cached['deferred'] ?? []));
    }

    /** Ganti placeholder dengan hasil render section dinamis untuk request saat ini. */
    private function fillDynamic(string $html, array $deferred): string
    {
        if ($deferred === []) {
            return $html;
        }

        $media = $this->loadMedia(collect($deferred));
        $replacements = [];
        foreach ($deferred as $uuid => $section) {
            $replacements[sprintf(self::PLACEHOLDER, $uuid)] = $this->renderSection($section, $media);
        }

        return strtr($html, $replacements);
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

        if ($this->deferred !== null && ! $editor && $type->isDynamic() && isset($section['uuid'])) {
            $this->deferred[$section['uuid']] = $section;

            return sprintf(self::PLACEHOLDER, $section['uuid']);
        }

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
            if (! $editor && trim($inner) === '') {
                return ''; // section tanpa data (mis. belum ada testimoni) tidak menyisakan ruang kosong
            }

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
