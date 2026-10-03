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
        // ..._create_devolucion_lineas_table.php
Schema::create('devolucion_lineas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('devolucion_id')->constrained('devoluciones')->cascadeOnDelete();
    $table->unsignedBigInteger('producto_id'); // informativo, sin FK real
    $table->string('descripcion');
    $table->decimal('cantidad', 10, 3);
    $table->decimal('precio_unit', 12, 2);
    $table->decimal('precio_total', 12, 2);
    $table->decimal('descuento', 12, 2)->default(0);
    $table->decimal('impuesto', 12, 2)->default(0);
    $table->decimal('costo', 12, 2)->default(0);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devolucion_lineas');
    }
};
