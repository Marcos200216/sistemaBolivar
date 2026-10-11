<?php
// database/migrations/2026_10_10_000001_create_ajustes_saldo_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ajustes_saldo')) {
            return;
        }

        Schema::create('ajustes_saldo', function (Blueprint $table) {
            $table->id();

            // SIN llaves foráneas a propósito: el registro de auditoría tiene que
            // sobrevivir aunque después se borre el cliente, la factura o el usuario.
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('sucursal_id')->nullable();
            $table->unsignedBigInteger('cliente_id')->nullable()->index();
            $table->string('cliente_nombre');
            $table->unsignedBigInteger('factura_id')->nullable()->index();
            $table->unsignedBigInteger('operacion_id')->nullable();

            // bajar_total | bajar_factura | subir_factura | anular_factura | anular_todas | eliminar_cliente
            $table->string('accion', 40);

            // monto_*: lo que se ajustó (saldo total o pendiente de la factura). saldo_cliente_*: saldo del cliente.
            $table->decimal('monto_antes', 14, 2)->nullable();
            $table->decimal('monto_despues', 14, 2)->nullable();
            $table->decimal('saldo_cliente_antes', 14, 2)->nullable();
            $table->decimal('saldo_cliente_despues', 14, 2)->nullable();

            $table->json('detalle')->nullable();
            $table->string('motivo', 500);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes_saldo');
    }
};