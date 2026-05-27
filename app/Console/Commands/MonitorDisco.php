<?php

namespace App\Console\Commands;

use App\Models\Configuracion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Monitorea el uso de disco. Si supera `umbral_disco_alerta` (%), AVISA.
 *
 * REGLA INNEGOCIABLE: monitoreo proactivo, NUNCA reactivo-destructivo. Este comando
 * jamás borra nada — solo informa/notifica. La purga de fotos es decisión de retención
 * (fotos:purgar), no una reacción a disco lleno.
 *
 *   php artisan disco:monitor
 */
class MonitorDisco extends Command
{
    protected $signature = 'disco:monitor';

    protected $description = 'Reporta uso de disco y avisa si supera el umbral. No borra nada.';

    public function handle(): int
    {
        $total = (float) @disk_total_space('/');
        $libre = (float) @disk_free_space('/');

        if ($total <= 0) {
            $this->warn('No se pudo leer el uso de disco.');

            return self::SUCCESS;
        }

        $umbral = (int) Configuracion::valor('umbral_disco_alerta', '90');
        $usadoPct = self::porcentajeUsado($total, $libre);

        $msg = sprintf(
            'Disco: %.1f%% usado (%.1f GB libres de %.1f GB). Umbral de alerta: %d%%.',
            $usadoPct, $libre / 1e9, $total / 1e9, $umbral
        );

        if ($usadoPct >= $umbral) {
            // AVISAR, no borrar. (Fase 2: push PWA / Telegram. Por ahora, log + salida.)
            $this->error("⚠ ALERTA: {$msg} Revisar y liberar espacio manualmente — NO se borra nada automáticamente.");
            Log::warning("[disco:monitor] umbral superado — {$msg}");
        } else {
            $this->info($msg);
        }

        return self::SUCCESS;
    }

    /**
     * Porcentaje usado, redondeado a 1 decimal. Pura, testeable.
     */
    public static function porcentajeUsado(float $total, float $libre): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($total - $libre) / $total * 100, 1);
    }
}
