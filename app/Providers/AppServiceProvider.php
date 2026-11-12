<?php

namespace App\Providers;

use App\Builder\Rendering\PageRenderer;
use App\Builder\SectionRegistry;
use App\Builder\SectionValidator;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\CaseStudy;
use App\Models\Faq;
use App\Models\GlobalSetting;
use App\Models\IndustrySolution;
use App\Models\MediaAsset;
use App\Models\NavigationMenu;
use App\Models\NavigationMenuItem;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductFaq;
use App\Models\ProductGallery;
use App\Models\ProductSpecification;
use App\Models\Promotion;
use App\Models\Statistic;
use App\Models\Testimonial;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Support\CmsCache;
use App\Support\NavigationRepository;
use App\Support\Roles;
use App\Support\SettingsRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SectionRegistry::class, fn () => new SectionRegistry(SectionRegistry::defaultTypes()));
        $this->app->singleton(SectionValidator::class);
        $this->app->scoped(PageRenderer::class);
        $this->app->scoped(SettingsRepository::class);
        $this->app->scoped(NavigationRepository::class);
    }

    public function boot(): void
    {
        // Deteksi lazy loading (indikasi N+1) di luar produksi: dicatat ke log, tidak memutus request.
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation) {
            Log::debug('Lazy loading terdeteksi.', ['model' => $model::class, 'relation' => $relation]);
        });

        // Super Admin otomatis lolos untuk permission "resource.ability". Method policy (view, delete, ...)
        // TETAP dijalankan agar invarian berlaku untuk semua orang: tidak bisa menghapus diri sendiri /
        // super admin terakhir, homepage tidak bisa dihapus, media terpakai tidak bisa dihapus, dst.
        Gate::before(fn (User $user, string $ability) => str_contains($ability, '.') && $user->hasRole(Roles::SUPER_ADMIN) ? true : null);
        Gate::policy(Role::class, RolePolicy::class);

        RateLimiter::for('leads', fn (Request $request) => [
            Limit::perMinute((int) config('cms.leads.rate_limit_per_minute', 5))->by($request->ip()),
            Limit::perDay(50)->by($request->ip()),
        ]);

        // Invalidasi cache konten publik setiap kali konten CMS berubah.
        foreach ([Page::class, Product::class, ProductCategory::class,
            ProductSpecification::class, ProductFaq::class, ProductGallery::class,
            NavigationMenu::class, NavigationMenuItem::class, IndustrySolution::class,
            Banner::class, Promotion::class, Testimonial::class, CaseStudy::class,
            Partner::class, Statistic::class, Faq::class, MediaAsset::class,
            GlobalSetting::class, Campaign::class] as $model) {
            $model::saved(fn () => CmsCache::bump());
            $model::deleted(fn () => CmsCache::bump());
        }

        View::composer(['layouts.public', 'components.public.*', 'builder.*', 'public.*'], function ($view) {
            $view->with('settings', $this->app->make(SettingsRepository::class));
        });
    }
}
