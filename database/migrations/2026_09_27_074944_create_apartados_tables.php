<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apartados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('apartado_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartado_id')->constrained('apartados')->cascadeOnDelete();
            // FK lógica (no real) a la BD 'catalogo', igual que factura_lineas
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('producto_variante_id')->nullable();
            $table->string('nombre'); // snapshot: si el producto cambia de nombre o se borra, la fila conserva el dato
            $table->unsignedInteger('cantidad');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartado_lineas');
        Schema::dropIfExists('apartados');
    }
};