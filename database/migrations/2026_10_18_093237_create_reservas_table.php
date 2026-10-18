<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // quién la creó
            $table->date('fecha');
            $table->time('hora'); // solo informativa
            $table->text('motivo')->nullable();
            $table->string('estado', 12)->default('pendiente'); // pendiente | terminada | cancelada
            $table->timestamps();

            $table->index(['sucursal_id', 'estado', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};