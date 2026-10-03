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
    Schema::create('factura_lineas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('factura_id')->constrained('facturas')->cascadeOnDelete();
        // referencias lógicas al catálogo, sin FK real
        $table->unsignedBigInteger('producto_id');
        $table->unsignedBigInteger('producto_variante_id')->nullable();
        $table->string('descripcion');
        $table->decimal('costo_unit', 12, 2)->default(0);
        $table->decimal('precio_unit', 12, 2);
        $table->unsignedInteger('cantidad');
        $table->decimal('descuento', 12, 2)->default(0);
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('factura_lineas');
}
};
