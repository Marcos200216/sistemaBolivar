<?php
// ..._update_rutas_estructura_fija.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('rutas', function (Blueprint $table) {
        $table->unique(['sucursal_id', 'tipo', 'filtro']);
    });
}

    public function down(): void
    {
        Schema::table('rutas', function (Blueprint $table) {
            $table->dropUnique(['sucursal_id', 'tipo', 'filtro']);
            $table->string('estado')->default('en_progreso');
            $table->date('fecha')->nullable();
        });
    }
};