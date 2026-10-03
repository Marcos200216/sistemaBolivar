<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            // Fecha real de la venta. En las migradas viene de legacy (fecharegistro);
            // en las nuevas queda NULL y se usa la fecha de la operación.
            $table->dateTime('fecha')->nullable()->after('plazo');
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn('fecha');
        });
    }
};