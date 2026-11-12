<?php

namespace App\Http\Controllers\Public;

use App\Builder\Rendering\PageRenderer;
use App\Enums\PageType;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\SeoData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PageController extends Controller
{
    public function __construct(private readonly PageRenderer $renderer) {}

    public function home(): View
    {
        $page = Page::query()->published()->where('type', PageType::Home)
            ->with(['publishedRevision', 'ogImage'])->latest('published_at')->firstOrFail();

        return $this->renderPage($page, true);
    }

    public function show(string $slug): View|RedirectResponse
    {
        $page = Page::query()->published()->where('slug', $slug)
            ->where('type', '!=', PageType::Global)
            ->with(['publishedRevision', 'ogImage'])->firstOrFail();

        if ($page->isHome()) {
            return redirect()->route('home', status: 301);
        }

        return $this->renderPage($page);
    }

    private function renderPage(Page $page, bool $isHome = false): View
    {
        $meta = $page->publishedRevision->snapshot['page'] ?? [];
        $title = $meta['meta_title'] ?? null ?: ($isHome ? (string) app(\App\Support\SettingsRepository::class)->get('general.site_name') : ($meta['title'] ?? $page->title));

        $seo = SeoData::make($title, $meta['meta_description'] ?? null, $page->ogImage);
        $seo->noindex = (bool) ($meta['noindex'] ?? $page->noindex);
        if (! empty($meta['canonical_url'])) {
            $seo->canonical = $meta['canonical_url'];
        }
        if (! $isHome) {
            $seo->withBreadcrumbs([['Beranda', route('home')], [$meta['title'] ?? $page->title, null]]);
        }
        $this->addFaqJsonLd($seo, $page->publishedRevision->sections());

        return view('public.page', [
            'page' => $page,
            'seo' => $seo,
            'html' => $this->renderer->renderPublished($page),
        ]);
    }

    /** Structured data FAQPage dari section FAQ manual. */
    private function addFaqJsonLd(SeoData $seo, array $sections): void
    {
        $items = collect($sections)
            ->filter(fn ($s) => ($s['type'] ?? null) === 'faq' && ($s['is_visible'] ?? true) && ($s['content']['source'] ?? null) === 'manual')
            ->flatMap(fn ($s) => $s['content']['items'] ?? [])
            ->take(30);

        if ($items->isNotEmpty()) {
            $seo->addJsonLd([
                '@type' => 'FAQPage',
                'mainEntity' => $items->map(fn ($i) => [
                    '@type' => 'Question', 'name' => $i['question'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $i['answer']],
                ])->values()->all(),
            ]);
        }
    }
}
