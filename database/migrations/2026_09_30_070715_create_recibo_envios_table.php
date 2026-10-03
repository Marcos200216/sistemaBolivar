<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recibo_envios', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('operacion_id')->index();
            $t->unsignedBigInteger('cliente_id')->nullable();
            $t->unsignedBigInteger('sucursal_id')->nullable();
            $t->string('telefono', 40)->nullable();      // el del cliente, tal como está guardado
            $t->string('destino', 20)->nullable();       // a dónde se mandó de verdad (difiere en modo prueba)
            $t->string('plantilla', 60)->nullable();
            $t->string('ruta_pdf')->nullable();          // relativa al disco 'comprobantes'
            $t->string('estado', 20)->index();           // enviado | fallido | invalido | sin_telefono
            $t->string('whatsapp_message_id')->nullable();
            $t->text('error')->nullable();
            $t->timestamp('enviado_at')->nullable();
            $t->unsignedBigInteger('reenviado_por')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recibo_envios');
    }
};