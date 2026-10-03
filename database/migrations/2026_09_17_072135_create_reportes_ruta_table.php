<?php
// ..._create_reportes_ruta_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_ruta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->foreignId('ruta_id')->constrained('rutas');
            $table->string('tipo');
            $table->string('filtro');
            $table->string('nombre_ruta')->nullable();
            $table->timestamp('generado_en');
            $table->json('datos');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_ruta');
    }
};