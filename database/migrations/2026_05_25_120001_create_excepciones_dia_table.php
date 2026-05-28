<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Excepción de día que Luis marca a mano para alguien que no llegó. Dos tipos:
 *   - 'justificado' : avisó / licencia → NO cuenta como falta ni acumula retraso.
 *   - 'ausente'     : falta injustificada → cuenta como ausencia (sin esperar la hora).
 * Apaga el retraso "corriendo" automático de ese día. Deja rastro (quién la registró).
 * Reversible (se borra la fila para deshacer). El marcaje real gana: si el trabajador
 * igual marca, el marcaje se registra normal (no lo bloquea).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excepciones_dia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained();
            $table->foreignId('trabajador_id')->constrained('trabajadores')->cascadeOnDelete();
            $table->date('fecha');
            $table->enum('tipo', ['justificado', 'ausente'])->default('justificado');
            $table->string('motivo')->nullable();       // opcional: "licencia", "avisó", etc.
            $table->foreignId('registrada_por')->nullable()->constrained('users'); // quién la marcó
            $table->timestamps();

            // una excepción por (trabajador, fecha)
            $table->unique(['trabajador_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excepciones_dia');
    }
};
