<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\SeoData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(): View
    {
        $categories = ProductCategory::query()->visible()
            ->with(['products' => fn ($q) => $q->published()->with('featuredImage')])
            ->get()->filter(fn ($c) => $c->products->isNotEmpty());

        $uncategorized = Product::query()->published()->doesntHave('categories')->with('featuredImage')->get();

        $seo = SeoData::make('Layanan', 'Daftar layanan internet dan solusi jaringan untuk bisnis.')
            ->withBreadcrumbs([['Beranda', route('home')], ['Layanan', null]]);

        return view('public.products.index', compact('categories', 'uncategorized', 'seo'));
    }

    public function show(string $slug): View
    {
        $product = Product::query()->published()->where('slug', $slug)
            ->with(['featuredImage', 'ogImage', 'specifications', 'galleries.media', 'faqs', 'categories', 'industrySolutions'])
            ->firstOrFail();

        $related = Product::query()->published()->whereKeyNot($product->id)
            ->whereHas('categories', fn ($q) => $q->whereIn('product_categories.id', $product->categories->pluck('id')))
            ->with('featuredImage')->limit(3)->get();

        $seo = SeoData::make($product->meta_title ?: $product->name, $product->meta_description ?: $product->short_description, $product->ogImage ?? $product->featuredImage)
            ->withBreadcrumbs([['Beranda', route('home')], ['Layanan', route('products.index')], [$product->name, null]])
            ->addJsonLd(array_filter([
                '@type' => 'Service',
                'name' => $product->name,
                'description' => Str::limit(strip_tags((string) ($product->short_description ?: $product->description)), 300),
                'serviceType' => 'Internet & Network Service',
                'provider' => ['@type' => 'Organization', 'name' => app(\App\Support\SettingsRepository::class)->get('general.site_name'), 'url' => url('/')],
                'image' => $product->featuredImage?->url(1440),
                'url' => $product->publicUrl(),
            ]));

        if ($product->faqs->isNotEmpty()) {
            $seo->addJsonLd([
                '@type' => 'FAQPage',
                'mainEntity' => $product->faqs->map(fn ($f) => ['@type' => 'Question', 'name' => $f->question,
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f->answer]])->all(),
            ]);
        }

        return view('public.products.show', compact('product', 'related', 'seo'));
    }
}
