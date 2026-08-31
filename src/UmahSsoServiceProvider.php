<?php

namespace Herihandoko\UmahSso;

use Herihandoko\UmahSso\Http\Controllers\UmahSsoCompleteController;
use Herihandoko\UmahSso\Http\Controllers\UmahSsoController;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class UmahSsoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/umah-sso.php', 'umah-sso');

        $this->app->singleton(UmahSso::class, fn () => new UmahSso());
    }

    public function boot(): void
    {
        EncryptCookies::except(UmahSso::banprovCookieNames());

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'umah-sso');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/umah-sso.php' => config_path('umah-sso.php'),
            ], 'umah-sso-config');
        }

        if (config('umah-sso.register_routes', true)) {
            $this->registerRoutes();
        }
    }

    protected function registerRoutes(): void
    {
        $middleware = (array) config('umah-sso.route_middleware', ['web', 'guest']);

        Route::middleware($middleware)
            ->get(config('umah-sso.route_path', 'sso/umah'), UmahSsoController::class)
            ->name(config('umah-sso.route_name', 'sso.umah'));

        Route::middleware($middleware)
            ->post(config('umah-sso.complete_route_path', 'sso/umah/complete'), UmahSsoCompleteController::class)
            ->name(config('umah-sso.complete_route_name', 'sso.umah.complete'));
    }
}
