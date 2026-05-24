<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Roles de la instancia. Solo dueño y admin son usuarios autenticados (Spatie).
 * El trabajador NO es usuario: es entidad de datos (ver Trabajador).
 *
 *  - dueno: gestiona su negocio (enrolamiento, reportes, branding) desde /panel.
 *  - admin: config técnica (retención, monitoreo, purgas) desde /admin. Delegable al dueño.
 */
class RolSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['dueno', 'admin'] as $rol) {
            Role::findOrCreate($rol, 'web');
        }
    }
}
