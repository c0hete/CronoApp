<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Kiosko\MarcarController;
use App\Http\Controllers\Panel\BrandingController;
use App\Http\Controllers\Panel\MarcajeController;
use App\Http\Controllers\Panel\ReporteController;
use App\Http\Controllers\Panel\TrabajadorController;
use Illuminate\Support\Facades\Route;

// Raíz: redirige al panel (el dueño). La tablet usa /marcar directo.
Route::redirect('/', '/panel');

// --- Kiosko de marcaje (tablet). SIN login: solo ID + cámara. ---
Route::get('/marcar', [MarcarController::class, 'index'])->name('kiosko.marcar');
// Logo del negocio para el kiosko (público; marca pública del cliente, no dato sensible).
Route::get('/marcar/logo', [MarcarController::class, 'logo'])->name('kiosko.logo');

// --- Autenticación (sin registro público) ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// --- Panel del dueño (autenticado + rol dueno o admin) ---
Route::prefix('panel')
    ->name('panel.')
    ->middleware(['auth', 'role:dueno|admin'])
    ->group(function () {
        Route::get('/', fn () => redirect()->route('panel.reportes.index'));

        // Reportes (Paso 7 / sección 10): dashboard semanal/mensual por trabajador.
        Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');

        // Marcaciones (Paso 7): listar + servir foto-evidencia autorizada.
        Route::get('marcajes', [MarcajeController::class, 'index'])->name('marcajes.index');
        Route::get('marcajes/{marcaje}/foto', [MarcajeController::class, 'foto'])->name('marcajes.foto');

        // Enrolamiento (Paso 3): listar, crear, guardar.
        Route::get('trabajadores', [TrabajadorController::class, 'index'])->name('trabajadores.index');
        Route::get('trabajadores/crear', [TrabajadorController::class, 'create'])->name('trabajadores.create');
        Route::post('trabajadores', [TrabajadorController::class, 'store'])->name('trabajadores.store');

        // Branding (Paso 8): personalización del negocio en autoservicio.
        Route::get('personalizacion', [BrandingController::class, 'edit'])->name('branding.edit');
        Route::post('personalizacion', [BrandingController::class, 'update'])->name('branding.update');
        Route::get('personalizacion/logo', [BrandingController::class, 'logo'])->name('branding.logo');
    });
