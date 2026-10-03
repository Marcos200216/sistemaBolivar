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
       // ..._create_recibos_consolidados_table.php
Schema::create('recibos_consolidados', function (Blueprint $table) {
    $table->id();
    $table->foreignId('cliente_id')->constrained('clientes');
    $table->decimal('monto_total', 12, 2)->default(0);
    $table->text('detalle_facturas_legacy'); // texto crudo tal cual venía, formato inconsistente en origen, solo informativo
    $table->string('admin_registro')->nullable();
    $table->timestamp('fecha');
    $table->unsignedInteger('consolidado_id_legacy')->unique();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recibos_consolidados');
    }
};
