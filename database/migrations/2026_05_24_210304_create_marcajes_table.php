<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marcajes', function (Blueprint $table) {
            $table->id();
            // Generado en la tablet → idempotencia ante reintentos de sync offline.
            $table->uuid('uuid')->unique();
            $table->foreignId('empresa_id')->constrained();
            // tabla en español: indicar nombre para no buscar 'trabajadors'.
            $table->foreignId('trabajador_id')->constrained('trabajadores');
            $table->enum('tipo', ['entrada', 'salida']);
            $table->timestamp('ts_dispositivo');            // hora de la tablet
            $table->timestamp('ts_servidor')->nullable();   // hora al sincronizar
            $table->string('foto_evidencia')->nullable();
            $table->integer('minutos_atraso')->default(0);  // solo entrada
            $table->decimal('costo_atraso', 12, 2)->default(0);
            $table->boolean('reloj_sospechoso')->default(false);
            $table->timestamps();

            $table->index(['empresa_id', 'trabajador_id', 'ts_dispositivo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marcajes');
    }
};
