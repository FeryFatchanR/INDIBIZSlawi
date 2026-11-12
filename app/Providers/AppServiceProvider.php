<?php

namespace App\Providers;

use App\Builder\Rendering\PageRenderer;
use App\Builder\SectionRegistry;
use App\Builder\SectionValidator;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Support\CmsCache;
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
        $this->app->scoped(\App\Support\NavigationRepository::class);
    }

    public function boot(): void
    {
        // Deteksi lazy loading (indikasi N+1) di luar produksi: dicatat ke log, tidak memutus request.
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation) {
            Log::debug('Lazy loading terdeteksi.', ['model' => $model::class, 'relation' => $relation]);
        });

        // Super Admin memiliki seluruh ability.
        Gate::before(fn (User $user) => $user->hasRole(Roles::SUPER_ADMIN) ? true : null);
        Gate::policy(Role::class, RolePolicy::class);

        RateLimiter::for('leads', fn (Request $request) => [
            Limit::perMinute((int) config('cms.leads.rate_limit_per_minute', 5))->by($request->ip()),
            Limit::perDay(50)->by($request->ip()),
        ]);

        // Invalidasi cache konten publik setiap kali konten CMS berubah.
        foreach ([\App\Models\Page::class, \App\Models\Product::class, \App\Models\ProductCategory::class,
            \App\Models\ProductSpecification::class, \App\Models\ProductFaq::class, \App\Models\ProductGallery::class,
            \App\Models\NavigationMenu::class, \App\Models\NavigationMenuItem::class, \App\Models\IndustrySolution::class,
            \App\Models\Banner::class, \App\Models\Promotion::class, \App\Models\Testimonial::class, \App\Models\CaseStudy::class,
            \App\Models\Partner::class, \App\Models\Statistic::class, \App\Models\Faq::class, \App\Models\MediaAsset::class,
            \App\Models\GlobalSetting::class, \App\Models\Campaign::class] as $model) {
            $model::saved(fn () => CmsCache::bump());
            $model::deleted(fn () => CmsCache::bump());
        }

        View::composer(['layouts.public', 'components.public.*', 'builder.*', 'public.*'], function ($view) {
            $view->with('settings', $this->app->make(SettingsRepository::class));
        });
    }
}
