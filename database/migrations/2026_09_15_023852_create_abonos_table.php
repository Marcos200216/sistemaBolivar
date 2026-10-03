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
        // ..._create_abonos_table.php
Schema::create('abonos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('cliente_id')->constrained('clientes');
    $table->foreignId('factura_id')->nullable()->constrained('facturas');
    $table->decimal('saldo_inicial', 12, 2)->default(0);
    $table->decimal('saldo_final', 12, 2)->default(0);
    $table->decimal('sinpe', 12, 2)->default(0);
    $table->decimal('efectivo', 12, 2)->default(0);
    $table->decimal('monto_abono', 12, 2)->default(0);
    $table->decimal('descuento', 12, 2)->default(0);
    $table->decimal('devolucion', 12, 2)->default(0);
    $table->boolean('es_abono')->default(false); // de isabono Y/N
    $table->timestamp('fecha');
    $table->unsignedInteger('abono_id_legacy')->unique(); // clave para re-sincronizar sin duplicar
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abonos');
    }
};
