<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Quita el unique() simple que tenía la columna codigo
            $table->dropUnique(['codigo']);

            // Códigos ahora pueden repetirse ENTRE sucursales, pero no DENTRO de la misma
            $table->unique(['sucursal_id', 'codigo']);

            // codigo puede quedar vacío si el cliente no tiene teléfono
            $table->string('codigo')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique(['sucursal_id', 'codigo']);
            $table->string('codigo')->nullable(false)->change();
            $table->unique('codigo');
        });
    }
};