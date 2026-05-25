<?php

namespace App\Console\Commands;

use App\Models\Marcaje;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Repara rutas de foto-evidencia mal formadas por el bug viejo del FotoService,
 * que metía '.jpg' en la carpeta del mes (ej. "1/2026/05.jpg/uuid.jpg" en vez de
 * "1/2026/05/uuid.jpg"). Mueve el archivo físico a la ruta correcta y actualiza
 * el registro. Idempotente: si ya está bien, no hace nada.
 *
 *   php artisan crono:sanear-fotos
 */
class SanearRutasFotos extends Command
{
    protected $signature = 'crono:sanear-fotos';
    protected $description = 'Corrige rutas de foto-evidencia mal formadas (bug 05.jpg/ del mes).';

    public function handle(): int
    {
        $disk = Storage::disk('fotos');
        $reparados = 0;

        Marcaje::whereNotNull('foto_evidencia')
            ->where('foto_evidencia', 'like', '%.jpg/%')   // patrón del bug: extensión en la carpeta
            ->each(function (Marcaje $m) use ($disk, &$reparados) {
                $vieja = $m->foto_evidencia;
                // quitar el '.jpg' que quedó pegado a la carpeta del mes
                $nueva = preg_replace('#(/\d{2})\.jpg/#', '$1/', $vieja);

                if ($nueva === $vieja) {
                    return;
                }

                if ($disk->exists($vieja)) {
                    // crear carpeta destino moviendo el archivo
                    $disk->move($vieja, $nueva);
                    $this->info("  archivo: {$vieja} → {$nueva}");
                } else {
                    $this->warn("  archivo no estaba en disco ({$vieja}); solo actualizo el registro");
                }

                $m->update(['foto_evidencia' => $nueva]);
                $reparados++;
            });

        $this->info($reparados > 0 ? "Reparados {$reparados} marcaje(s)." : 'Nada que reparar.');

        return self::SUCCESS;
    }
}
