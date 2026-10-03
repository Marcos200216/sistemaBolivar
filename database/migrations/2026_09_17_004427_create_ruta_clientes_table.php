<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ruta_clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruta_id')->constrained('rutas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->unsignedInteger('orden')->default(0);
            $table->string('estado')->default('pendiente'); // pendiente | finalizado | no_abono | recobro
            $table->string('etiqueta')->nullable();
            $table->timestamps();

            $table->unique(['ruta_id', 'cliente_id']); // un cliente no debería repetirse dentro de la misma ruta
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ruta_clientes');
    }
};