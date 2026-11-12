<?php

use App\Http\Controllers\Public\CaseStudyController;
use App\Http\Controllers\Public\LeadController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PreviewController;
use App\Http\Controllers\Public\ProductController;
use App\Http\Controllers\Public\SeoController;
use App\Http\Controllers\Public\SolutionController;
use App\Http\Controllers\Public\WhatsAppController;
use Illuminate\Support\Facades\Route;

/*
| Website publik. Admin panel Filament otomatis tersedia di /admin.
| Urutan penting: catch-all halaman builder (/{slug}) harus paling akhir.
*/

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// Preview draft: wajib login + policy, tidak pernah di-cache.
Route::get('/preview/pages/{page:uuid}', PreviewController::class)
    ->middleware(['auth', 'can:preview,page'])
    ->name('pages.preview');

Route::middleware('attribution')->group(function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/layanan', [ProductController::class, 'index'])->name('products.index');
    Route::get('/layanan/{slug}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/solusi', [SolutionController::class, 'index'])->name('solutions.index');
    Route::get('/solusi/{slug}', [SolutionController::class, 'show'])->name('solutions.show');
    Route::get('/studi-kasus/{slug}', [CaseStudyController::class, 'show'])->name('case-studies.show');
    Route::get('/wa', WhatsAppController::class)->middleware('throttle:30,1')->name('whatsapp');

    Route::post('/leads', [LeadController::class, 'store'])->middleware('throttle:leads')->name('leads.store');

    Route::get('/{slug}', [PageController::class, 'show'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->name('page.show');
});
