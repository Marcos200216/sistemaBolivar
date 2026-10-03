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
    Schema::create('compras', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sucursal_id')->constrained('sucursales');
        $table->foreignId('proveedor_id')->constrained('proveedores');
        $table->string('descripcion');
        $table->decimal('monto_total', 12, 2);
        $table->string('tipo', 20);   // contado | credito
        $table->string('estado', 20); // pendiente | pagada
        $table->decimal('saldo_pendiente', 12, 2)->default(0);
        $table->date('fecha');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
