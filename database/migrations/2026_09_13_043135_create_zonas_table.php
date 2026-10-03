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
    Schema::create('zonas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sucursal_id')->constrained('sucursales');
        $table->string('nombre');
        $table->unsignedInteger('zona_id_legacy')->nullable()->index();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('zonas');
}
};
