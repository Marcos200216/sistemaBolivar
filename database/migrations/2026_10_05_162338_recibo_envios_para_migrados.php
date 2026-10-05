<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibo_envios', function (Blueprint $t) {
            $t->unsignedBigInteger('operacion_id')->nullable()->change();
            $t->unsignedBigInteger('abono_id')->nullable()->index()->after('operacion_id');
            $t->unsignedBigInteger('factura_id')->nullable()->index()->after('abono_id');
        });
    }

    public function down(): void
    {
        Schema::table('recibo_envios', function (Blueprint $t) {
            $t->dropColumn(['abono_id', 'factura_id']);
            // operacion_id queda nullable a propósito: volver a NOT NULL falla si ya hay filas migradas.
        });
    }
};