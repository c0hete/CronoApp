<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Kiosko\MarcarController;
use App\Http\Controllers\Panel\MarcajeController;
use App\Http\Controllers\Panel\TrabajadorController;
use Illuminate\Support\Facades\Route;

// Raíz: redirige al panel (el dueño). La tablet usa /marcar directo.
Route::redirect('/', '/panel');

// --- Kiosko de marcaje (tablet). SIN login: solo ID + cámara. ---
Route::get('/marcar', [MarcarController::class, 'index'])->name('kiosko.marcar');

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
        Route::get('/', fn () => redirect()->route('panel.marcajes.index'));

        // Marcaciones (Paso 7): listar + servir foto-evidencia autorizada.
        Route::get('marcajes', [MarcajeController::class, 'index'])->name('marcajes.index');
        Route::get('marcajes/{marcaje}/foto', [MarcajeController::class, 'foto'])->name('marcajes.foto');

        // Enrolamiento (Paso 3): listar, crear, guardar.
        Route::get('trabajadores', [TrabajadorController::class, 'index'])->name('trabajadores.index');
        Route::get('trabajadores/crear', [TrabajadorController::class, 'create'])->name('trabajadores.create');
        Route::post('trabajadores', [TrabajadorController::class, 'store'])->name('trabajadores.store');
    });
