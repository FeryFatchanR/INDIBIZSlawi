<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\IndustrySolution;
use App\Support\SeoData;
use Illuminate\Contracts\View\View;

class SolutionController extends Controller
{
    public function index(): View
    {
        $solutions = IndustrySolution::query()->visible()->with('image')->get();
        $seo = SeoData::make('Solusi Industri', 'Solusi konektivitas untuk pemerintahan, pendidikan, kesehatan, perbankan, dan sektor lainnya.')
            ->withBreadcrumbs([['Beranda', route('home')], ['Solusi Industri', null]]);

        return view('public.solutions.index', compact('solutions', 'seo'));
    }

    public function show(string $slug): View
    {
        $solution = IndustrySolution::query()->where('is_visible', true)->where('slug', $slug)
            ->with(['image', 'products' => fn ($q) => $q->published()->with('featuredImage'),
                'caseStudies' => fn ($q) => $q->visible()->with('image')])
            ->firstOrFail();

        $seo = SeoData::make($solution->meta_title ?: 'Solusi '.$solution->name, $solution->meta_description ?: $solution->short_description, $solution->image)
            ->withBreadcrumbs([['Beranda', route('home')], ['Solusi Industri', route('solutions.index')], [$solution->name, null]]);

        return view('public.solutions.show', compact('solution', 'seo'));
    }
}
