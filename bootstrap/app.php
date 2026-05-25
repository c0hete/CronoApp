<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Producción: la app corre detrás de NPM (nginx) que termina el TLS.
        // Confiar en el proxy para que Laravel detecte https y la IP real del cliente.
        // Con esto + APP_URL/ASSET_URL https en .env NO hace falta URL::forceScheme
        // (lección 29 del hub: forceScheme peleaba con el framework).
        $middleware->trustProxies(at: '*');

        // Alias de Spatie para proteger rutas por rol/permiso.
        $middleware->alias([
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
