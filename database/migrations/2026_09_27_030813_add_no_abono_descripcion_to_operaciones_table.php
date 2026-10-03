<?php
// database/migrations/2026_09_27_000001_add_no_abono_descripcion_to_operaciones_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->text('no_abono_descripcion')->nullable()->after('no_abono');
        });
    }

    public function down(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->dropColumn('no_abono_descripcion');
        });
    }
};