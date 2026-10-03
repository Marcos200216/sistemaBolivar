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
    Schema::create('facturas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sucursal_id')->constrained('sucursales');
        $table->foreignId('cliente_id')->constrained('clientes');
        $table->string('estado')->default('activa'); // activa | anulada
        $table->unsignedInteger('plazo')->default(0); // días de crédito, 0 = contado
        $table->decimal('montototal', 12, 2)->default(0);
        $table->decimal('impuesto', 12, 2)->default(0);
        $table->decimal('descuento', 12, 2)->default(0);
        $table->decimal('flete', 12, 2)->default(0);
        $table->decimal('total', 12, 2)->default(0);
        $table->decimal('pago', 12, 2)->default(0);
        $table->decimal('vuelto', 12, 2)->default(0);
        $table->unsignedInteger('factura_id_legacy')->nullable()->index();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('facturas');
}
};
