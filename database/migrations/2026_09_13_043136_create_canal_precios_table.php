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
    Schema::create('canal_precios', function (Blueprint $table) {
        $table->id();
        // referencias lógicas al catálogo (otra conexión/servidor), sin FK real
        $table->unsignedBigInteger('producto_id');
        $table->unsignedBigInteger('producto_variante_id')->nullable();
        $table->enum('canal', ['normal', 'mayorista']);
        $table->decimal('precio_compra', 12, 2)->default(0);
        $table->decimal('precio_venta', 12, 2);
        $table->decimal('descuento', 12, 2)->default(0);
        $table->decimal('utilidad', 12, 2)->default(0);
        $table->timestamps();

        $table->index(['producto_id', 'producto_variante_id', 'canal']);
    });
}

public function down(): void
{
    Schema::dropIfExists('canal_precios');
}
};
