<?php

namespace App\Http\Controllers\Public;

use App\Builder\Rendering\PageRenderer;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageRevision;
use App\Support\SeoData;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Preview draft (atau revision tertentu). Hanya untuk pengguna berizin; noindex & no-store. */
class PreviewController extends Controller
{
    public function __invoke(Request $request, Page $page, PageRenderer $renderer): Response
    {
        $sections = $page->sections()->get()->map->toBuilderArray()->all();
        $label = 'Preview draft';

        if ($request->filled('revision')) {
            $revision = PageRevision::query()->where('page_id', $page->id)->findOrFail((int) $request->query('revision'));
            $sections = $revision->sections();
            $label = "Preview revision #{$revision->version}";
        }

        $seo = SeoData::make($page->title);
        $seo->noindex = true;

        return response()
            ->view('public.page', [
                'page' => $page,
                'seo' => $seo,
                'html' => $renderer->render($sections, editor: $request->boolean('canvas')),
                'previewLabel' => $label,
                'canvas' => $request->boolean('canvas'),
            ])
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
