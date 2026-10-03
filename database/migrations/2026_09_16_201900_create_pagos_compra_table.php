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
    Schema::create('pagos_compra', function (Blueprint $table) {
        $table->id();
        $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
        $table->decimal('monto', 12, 2);
        $table->date('fecha');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_compra');
    }
};
