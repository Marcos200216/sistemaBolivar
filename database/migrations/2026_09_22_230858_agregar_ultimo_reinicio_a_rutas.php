<?php
// database/migrations/2026_09_22_000002_agregar_ultimo_reinicio_a_rutas.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rutas', function (Blueprint $table) {
            $table->timestamp('ultimo_reinicio')->nullable()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('rutas', function (Blueprint $table) {
            $table->dropColumn('ultimo_reinicio');
        });
    }
};