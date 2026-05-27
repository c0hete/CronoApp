<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use Illuminate\Database\Seeder;

/**
 * Configuración por defecto de la instancia (sección 5 de implementacion.md).
 * Todo lo que diferencia a un cliente de otro vive acá, NUNCA en código.
 * El dueño ajusta luego cálculo, fotos y branding desde el panel.
 */
class ConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        $empresaId = (int) config('crono.empresa_id', 1);

        $defaults = [
            // -- cálculo --
            'base_calculo' => config('crono.base_calculo', 'bruto'),
            'inicio_semana' => config('crono.corte_semana', 'lunes'),
            // -- fotos (evidencia visual, NO biometría) --
            'retencion_fotos_dias' => (string) config('crono.fotos.retencion_dias', 60),
            'foto_rotacion' => '0',
            'foto_ancho_px' => '640',
            'foto_calidad' => '70',
            // -- operación --
            'umbral_disco_alerta' => '90',
            'reloj_tolerancia_min' => '5',
            // -- branding (white-label, editable por el dueño) --
            'marca_nombre' => '',          // nombre del negocio en UI (ej. el cliente)
            'marca_logo' => '',          // ruta del logo; vacío = fallback a texto
            'marca_color_primario' => '#2E75B6',   // HEX único; la paleta se deriva en frontend
        ];

        foreach ($defaults as $clave => $valor) {
            Configuracion::query()->updateOrCreate(
                ['empresa_id' => $empresaId, 'clave' => $clave],
                ['valor' => $valor],
            );
        }
    }
}
