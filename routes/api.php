<?php

use App\Http\Controllers\Api\MarcajeController;
use Illuminate\Support\Facades\Route;

/*
 * API del kiosko (tablet). Sin login: la tablet no tiene sesión.
 * Seguridad = validación fuerte + idempotencia + sync unidireccional.
 */
Route::post('/marcar', [MarcajeController::class, 'store'])->name('api.marcar');
