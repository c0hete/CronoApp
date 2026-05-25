<?php

use App\Support\Rut;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Normaliza el numero_id de los trabajadores con tipo_id='rut' al formato canónico
 * (sin puntos/guion, K mayúscula). Antes se guardaba tal cual se tecleaba
 * (ej. "25768863-1"), lo que rompía el match con el kiosko (que teclea sin guión).
 *
 * Data migration idempotente: normalizar() sobre un valor ya canónico no lo cambia.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('trabajadores')
            ->where('tipo_id', 'rut')
            ->orderBy('id')
            ->each(function ($t) {
                $canonico = Rut::normalizar($t->numero_id);
                if ($canonico !== $t->numero_id) {
                    DB::table('trabajadores')->where('id', $t->id)->update(['numero_id' => $canonico]);
                }
            });
    }

    public function down(): void
    {
        // No reversible: no guardamos el formato original. Normalizar es el estado correcto.
    }
};
