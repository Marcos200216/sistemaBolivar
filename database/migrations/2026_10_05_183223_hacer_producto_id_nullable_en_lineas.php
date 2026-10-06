<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factura_lineas', function (Blueprint $table) {
            $table->unsignedBigInteger('producto_id')->nullable()->change();
        });

        if (Schema::hasTable('devolucion_lineas') && Schema::hasColumn('devolucion_lineas', 'producto_id')) {
            Schema::table('devolucion_lineas', function (Blueprint $table) {
                $table->unsignedBigInteger('producto_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // No se revierte: habría líneas libres con producto_id nulo.
    }
};