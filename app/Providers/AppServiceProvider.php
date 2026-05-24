<?php

namespace App\Providers;

use App\Services\BrandingService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BrandingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Branding white-label disponible en todas las vistas como $branding.
        // La UI nunca muestra "Crono": siempre el nombre del negocio (marca_nombre).
        View::composer('*', function ($view) {
            $view->with('branding', app(BrandingService::class));
        });
    }
}
