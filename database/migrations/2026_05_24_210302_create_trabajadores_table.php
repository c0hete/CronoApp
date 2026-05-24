<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trabajadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained();
            // Enchufe ARCO: el trabajador NO es usuario autenticado (Ley 21.719, fase 2).
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('nombre');
            $table->enum('tipo_id', ['rut', 'pasaporte']);
            $table->string('numero_id');
            $table->string('foto_enrolamiento')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'tipo_id', 'numero_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trabajadores');
    }
};
