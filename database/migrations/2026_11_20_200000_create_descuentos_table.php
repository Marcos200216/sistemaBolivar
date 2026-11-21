<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descuentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operacion_id')->constrained('operaciones');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('factura_id')->nullable()->constrained('facturas');
            $table->decimal('saldo_inicial', 12, 2);
            $table->decimal('saldo_final', 12, 2);
            $table->decimal('monto', 12, 2);
            $table->text('motivo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descuentos');
    }
};