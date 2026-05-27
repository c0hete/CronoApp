<?php

namespace Database\Seeders;

use App\Models\Empresa;
use Illuminate\Database\Seeder;

/**
 * Siembra la empresa de ESTA instancia (id = CRONO_EMPRESA_ID, normalmente 1).
 *
 * Nada específico de cliente se hardcodea: el nombre real del negocio se setea
 * al aprovisionar (config `marca_nombre`). Acá solo dejamos un registro genérico
 * para que el scope de tenant tenga su empresa raíz.
 */
class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        $id = (int) config('crono.empresa_id', 1);

        Empresa::query()->updateOrCreate(
            ['id' => $id],
            [
                'nombre' => 'Empresa', // placeholder; el nombre visible es marca_nombre (config)
                'rut_empresa' => null,
                'activa' => true,
            ],
        );
    }
}
