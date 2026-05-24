<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Empresa de esta instancia (tenant)
    |--------------------------------------------------------------------------
    | En el modelo instancia-por-cliente, cada despliegue sirve a una empresa.
    | Su id (sembrado como 1) ancla el scope BelongsToEmpresa.
    */
    'empresa_id' => env('CRONO_EMPRESA_ID', 1),

    /*
    |--------------------------------------------------------------------------
    | Cálculo de atraso (defaults; lo fino vive en tabla configuraciones)
    |--------------------------------------------------------------------------
    */
    'corte_semana'  => env('CRONO_CORTE_SEMANA', 'lunes'),
    'base_calculo'  => env('CRONO_BASE_CALCULO', 'bruto'),

    /*
    |--------------------------------------------------------------------------
    | Fotos (evidencia visual de presencia, NO biometría)
    |--------------------------------------------------------------------------
    */
    'fotos' => [
        'retencion_dias' => (int) env('CRONO_FOTOS_RETENCION_DIAS', 60),
        'disk'           => env('CRONO_FOTOS_DISK', 'local'),
    ],

];
