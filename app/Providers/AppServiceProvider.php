<?php

namespace App\Providers;

use App\Builder\Rendering\PageRenderer;
use App\Builder\SectionRegistry;
use App\Builder\SectionValidator;
use App\Models\User;
use App\Policies\RolePolicy;
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

        View::composer(['layouts.public', 'components.public.*', 'builder.*', 'public.*'], function ($view) {
            $view->with('settings', $this->app->make(SettingsRepository::class));
        });
    }
}
