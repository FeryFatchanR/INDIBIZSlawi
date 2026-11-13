<?php

namespace App\Http\Controllers\Public;

use App\Enums\PageType;
use App\Http\Controllers\Controller;
use App\Models\CaseStudy;
use App\Models\IndustrySolution;
use App\Models\Page;
use App\Models\Product;
use App\Support\CmsCache;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $xml = CmsCache::remember('sitemap', function () {
            $urls = [['loc' => route('home'), 'lastmod' => now()->toAtomString(), 'priority' => '1.0']];

            Page::query()->published()->where('type', PageType::Page)->where('noindex', false)->get(['slug', 'published_at'])
                ->each(function ($p) use (&$urls) {
                    $urls[] = ['loc' => route('page.show', $p->slug), 'lastmod' => $p->published_at?->toAtomString(), 'priority' => '0.7'];
                });
            $urls[] = ['loc' => route('products.index'), 'lastmod' => null, 'priority' => '0.9'];
            Product::query()->published()->get(['slug', 'updated_at'])->each(function ($p) use (&$urls) {
                $urls[] = ['loc' => route('products.show', $p->slug), 'lastmod' => $p->updated_at?->toAtomString(), 'priority' => '0.9'];
            });
            $urls[] = ['loc' => route('solutions.index'), 'lastmod' => null, 'priority' => '0.8'];
            IndustrySolution::query()->where('is_visible', true)->get(['slug', 'updated_at'])->each(function ($s) use (&$urls) {
                $urls[] = ['loc' => route('solutions.show', $s->slug), 'lastmod' => $s->updated_at?->toAtomString(), 'priority' => '0.7'];
            });
            CaseStudy::query()->where('is_visible', true)->get(['slug', 'updated_at'])->each(function ($c) use (&$urls) {
                $urls[] = ['loc' => route('case-studies.show', $c->slug), 'lastmod' => $c->updated_at?->toAtomString(), 'priority' => '0.6'];
            });

            return view('public.sitemap', ['urls' => $urls])->render();
        }, 3600);

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = app()->isProduction()
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /preview', 'Disallow: /livewire', 'Allow: /', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
