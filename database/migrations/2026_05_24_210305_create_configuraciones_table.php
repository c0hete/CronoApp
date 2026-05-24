<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained();
            $table->string('clave');
            $table->text('valor');
            $table->timestamps();

            $table->unique(['empresa_id', 'clave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones');
    }
};
