<?php

namespace Herihandoko\UmahSso;

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
        Route::middleware((array) config('umah-sso.route_middleware', ['web', 'guest']))
            ->get(config('umah-sso.route_path', 'sso/umah'), UmahSsoController::class)
            ->name(config('umah-sso.route_name', 'sso.umah'));
    }
}
