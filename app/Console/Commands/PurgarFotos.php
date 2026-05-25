<?php

namespace App\Console\Commands;

use App\Models\Configuracion;
use App\Models\Marcaje;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Purga fotos-evidencia con antigüedad mayor a `retencion_fotos_dias`.
 *
 * REGLA INNEGOCIABLE: borra SOLO la foto (archivo + referencia), NUNCA el marcaje.
 * El registro permanece como dato de gestión aunque pierda la imagen.
 *
 *   php artisan fotos:purgar [--dias=N] [--dry-run]
 */
class PurgarFotos extends Command
{
    protected $signature = 'fotos:purgar {--dias= : Override de la retención configurada} {--dry-run : Solo informa, no borra}';
    protected $description = 'Borra fotos-evidencia más antiguas que la retención (nunca borra el marcaje).';

    public function handle(): int
    {
        $dias = (int) ($this->option('dias') ?? Configuracion::valor('retencion_fotos_dias', '60'));
        $seco = (bool) $this->option('dry-run');
        $corte = now()->subDays($dias);
        $disk = Storage::disk('fotos');

        $purgadas = 0;

        Marcaje::whereNotNull('foto_evidencia')
            ->where('ts_servidor', '<', $corte)
            ->each(function (Marcaje $m) use ($disk, $seco, &$purgadas) {
                $ruta = $m->foto_evidencia;

                if ($seco) {
                    $this->line("  [dry-run] purgaría foto del marcaje #{$m->id} ({$ruta})");
                    $purgadas++;
                    return;
                }

                if ($disk->exists($ruta)) {
                    $disk->delete($ruta);
                }
                // El marcaje PERMANECE; solo pierde la imagen.
                $m->update(['foto_evidencia' => null]);
                $purgadas++;
            });

        $this->info($seco
            ? "Dry-run: {$purgadas} foto(s) se purgarían (retención {$dias} días)."
            : "Purgadas {$purgadas} foto(s) con más de {$dias} días. Los marcajes se conservan.");

        return self::SUCCESS;
    }
}
