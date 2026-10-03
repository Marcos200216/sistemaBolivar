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
    Schema::create('proveedores', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sucursal_id')->constrained('sucursales');
        $table->string('nombre');
        $table->string('telefono')->nullable();
        $table->timestamps();

        $table->unique(['sucursal_id', 'nombre']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
