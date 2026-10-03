<?php
// database/migrations/2026_09_27_000002_add_tipo_y_traspaso_cliente_id_to_operaciones_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->string('tipo', 20)->default('normal')->after('no_abono_descripcion');
            $table->unsignedBigInteger('traspaso_cliente_id')->nullable()->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'traspaso_cliente_id']);
        });
    }
};