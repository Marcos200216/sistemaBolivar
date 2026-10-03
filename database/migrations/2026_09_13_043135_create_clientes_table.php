<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('clientes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sucursal_id')->constrained('sucursales');
        $table->string('codigo')->unique();
        $table->string('nombre');
        $table->string('telefono')->nullable();
        $table->string('correo')->nullable();
        $table->string('direccion')->nullable();
        $table->decimal('maximocredito', 12, 2)->default(0);
        $table->decimal('saldo_actual', 12, 2)->default(0);
        $table->string('genero')->nullable();
        $table->unsignedInteger('cliente_id_legacy')->nullable()->index();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('clientes');
}
};
