<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained();
            // tabla en español: Laravel pluralizaría a 'trabajadors', hay que indicarla.
            $table->foreignId('trabajador_id')->constrained('trabajadores');
            // Al menos uno de los dos sueldos es obligatorio (validado en la capa de app).
            $table->decimal('sueldo_bruto', 12, 2)->nullable();
            $table->decimal('sueldo_liquido', 12, 2)->nullable();
            $table->decimal('horas_semanales', 5, 2);
            $table->time('hora_entrada_pactada');
            $table->unsignedSmallInteger('tolerancia_min')->default(0);
            $table->date('vigente_desde');
            // Contrato vigente = vigente_hasta NULL. Para cambiar sueldo/horario:
            // cerrar el vigente y crear uno nuevo (no editar, preserva reportes pasados).
            $table->date('vigente_hasta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
