<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Panel\TrabajadorController;
use Illuminate\Support\Facades\Route;

// Raíz: por ahora redirige al panel (kiosko /marcar llega en el Paso 5).
Route::redirect('/', '/panel');

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
        Route::get('/', fn () => redirect()->route('panel.trabajadores.index'));

        // Enrolamiento (Paso 3): listar, crear, guardar.
        Route::get('trabajadores', [TrabajadorController::class, 'index'])->name('trabajadores.index');
        Route::get('trabajadores/crear', [TrabajadorController::class, 'create'])->name('trabajadores.create');
        Route::post('trabajadores', [TrabajadorController::class, 'store'])->name('trabajadores.store');
    });
