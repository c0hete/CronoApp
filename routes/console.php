<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// --- Tareas programadas (Paso 9). El contenedor 'scheduler' corre schedule:work. ---

// Purga de fotos por retención (nunca borra marcajes). Diario, madrugada Chile.
Schedule::command('fotos:purgar')->dailyAt('03:30')->timezone('America/Santiago');

// Monitoreo de disco (avisa, no borra). Diario.
Schedule::command('disco:monitor')->dailyAt('07:00')->timezone('America/Santiago');
