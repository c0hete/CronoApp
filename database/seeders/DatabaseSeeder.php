<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Siembra base de una instancia: empresa raíz (tenant) + configuración default.
     * El usuario dueño NO se siembra acá: se crea al aprovisionar con
     * `php artisan crono:crear-dueno` (paso posterior).
     */
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            EmpresaSeeder::class,
            ConfiguracionSeeder::class,
        ]);
    }
}
