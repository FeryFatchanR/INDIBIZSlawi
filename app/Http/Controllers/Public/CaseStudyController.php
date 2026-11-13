<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CaseStudy;
use App\Support\SeoData;
use Illuminate\Contracts\View\View;

class CaseStudyController extends Controller
{
    public function show(string $slug): View
    {
        $case = CaseStudy::query()->where('is_visible', true)->where('slug', $slug)
            ->with(['image', 'industrySolution'])->firstOrFail();

        $seo = SeoData::make($case->meta_title ?: $case->title, $case->meta_description ?: $case->summary, $case->image)
            ->withBreadcrumbs([['Beranda', route('home')], ['Studi Kasus', null], [$case->title, null]]);
        $seo->type = 'article';

        return view('public.case-studies.show', compact('case', 'seo'));
    }
}
