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
        // ..._create_devoluciones_table.php
Schema::create('devoluciones', function (Blueprint $table) {
    $table->id();
    $table->foreignId('cliente_id')->nullable()->constrained('clientes');
    $table->foreignId('factura_id')->nullable()->constrained('facturas');
    $table->string('estado_legacy', 1)->nullable(); // 'C'/'E' crudo, significado no confirmado con código aún
    $table->string('motivo'); // enum MotivoDevolucion
    $table->unsignedInteger('total_lineas')->default(0);
    $table->decimal('monto_total', 12, 2)->default(0);
    $table->decimal('impuesto', 12, 2)->default(0);
    $table->decimal('descuento', 12, 2)->default(0);
    $table->decimal('total', 12, 2)->default(0);
    $table->timestamp('fecha');
    $table->unsignedInteger('devolucion_id_legacy')->unique();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devoluciones');
    }
};
