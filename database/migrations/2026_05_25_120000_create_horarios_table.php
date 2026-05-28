<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Horario esperado por trabajador: qué días de la semana trabaja y a qué hora entra.
 *
 * Esto permite saber que "el miércoles a las 21:00 se espera a X" y calcular el
 * retraso aunque el trabajador todavía no haya marcado. NO es un planificador de
 * turnos rotativos — es un horario semanal fijo por persona.
 *
 * Aditiva: el contrato conserva hora_entrada_pactada/tolerancia_min como fallback
 * para trabajadores sin horario configurado (compatibilidad con lo ya existente).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained();
            $table->foreignId('trabajador_id')->constrained('trabajadores')->cascadeOnDelete();
            // ISO-8601: 1 = lunes ... 7 = domingo (coincide con Carbon::dayOfWeekIso)
            $table->unsignedTinyInteger('dia_semana');
            $table->time('hora_entrada');
            // tolerancia propia del día; si es NULL, se usa la del contrato vigente
            $table->unsignedSmallInteger('tolerancia_min')->nullable();
            $table->timestamps();

            // un solo horario por (trabajador, día)
            $table->unique(['trabajador_id', 'dia_semana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
