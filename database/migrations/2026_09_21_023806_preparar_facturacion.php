<?php
// database/migrations/2026_09_20_000001_preparar_facturacion.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cada visita/acción se agrupa en una operación con su número por sucursal
        Schema::create('operaciones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sucursal_id')->constrained('sucursales');
            $t->foreignId('cliente_id')->constrained('clientes');
            $t->foreignId('user_id')->constrained('users');
            // ruta_clientes se borra al administrar/reiniciar rutas; la operación no debe caer con ella
            $t->foreignId('ruta_cliente_id')->nullable()->constrained('ruta_clientes')->nullOnDelete();
            $t->unsignedInteger('numero');
            $t->decimal('saldo_inicial', 12, 2);
            $t->decimal('saldo_final', 12, 2);
            $t->boolean('no_abono')->default(false);
            $t->timestamp('fecha')->useCurrent();
            $t->timestamps();

            $t->unique(['sucursal_id', 'numero']);
        });

        foreach (['abonos', 'facturas', 'devoluciones'] as $tabla) {
            Schema::table($tabla, function (Blueprint $t) {
                $t->foreignId('operacion_id')->nullable()->after('id')
                    ->constrained('operaciones')->nullOnDelete();
            });
        }

        // Los registros nuevos no tienen id legacy (el índice único se conserva; MySQL permite varios NULL)
        Schema::table('abonos', fn (Blueprint $t) => $t->unsignedInteger('abono_id_legacy')->nullable()->change());
        Schema::table('devoluciones', fn (Blueprint $t) => $t->unsignedInteger('devolucion_id_legacy')->nullable()->change());

        // Editar un registro no debe mover su fecha
        DB::statement('ALTER TABLE abonos MODIFY fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        DB::statement('ALTER TABLE devoluciones MODIFY fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');

        // Comprobante de sinpe en abonos
        Schema::table('abonos', function (Blueprint $t) {
            $t->string('comprobante_sinpe')->nullable()->after('sinpe');
        });

        // Ventas de contado: separar efectivo/sinpe, saldo a favor aplicado y comprobante
        Schema::table('facturas', function (Blueprint $t) {
            $t->decimal('efectivo', 12, 2)->default(0)->after('vuelto');
            $t->decimal('sinpe', 12, 2)->default(0)->after('efectivo');
            $t->decimal('saldo_favor_aplicado', 12, 2)->default(0)->after('sinpe');
            $t->string('comprobante_sinpe')->nullable()->after('saldo_favor_aplicado');
        });

        Schema::table('devolucion_lineas', function (Blueprint $t) {
            // Sin FK: la variante vive en el servidor del catálogo
            $t->unsignedBigInteger('producto_variante_id')->nullable()->after('producto_id');
            $t->foreignId('factura_linea_id')->nullable()->after('producto_variante_id')
                ->constrained('factura_lineas')->nullOnDelete();
            $t->boolean('regresa_stock')->default(false)->after('costo');
        });
    }

    public function down(): void
    {
        Schema::table('devolucion_lineas', function (Blueprint $t) {
            $t->dropForeign(['factura_linea_id']);
            $t->dropColumn(['producto_variante_id', 'factura_linea_id', 'regresa_stock']);
        });

        Schema::table('facturas', function (Blueprint $t) {
            $t->dropColumn(['efectivo', 'sinpe', 'saldo_favor_aplicado', 'comprobante_sinpe']);
        });

        Schema::table('abonos', function (Blueprint $t) {
            $t->dropColumn('comprobante_sinpe');
        });

        foreach (['abonos', 'facturas', 'devoluciones'] as $tabla) {
            Schema::table($tabla, function (Blueprint $t) {
                $t->dropForeign(['operacion_id']);
                $t->dropColumn('operacion_id');
            });
        }

        Schema::dropIfExists('operaciones');
        // No se revierte la nulabilidad de los ids legacy ni el ON UPDATE de fecha, a propósito.
    }
};