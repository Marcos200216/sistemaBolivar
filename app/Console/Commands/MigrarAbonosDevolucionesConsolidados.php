<?php

namespace App\Console\Commands;

use App\Enums\MotivoDevolucion;
use App\Models\Cliente;
use App\Models\Devolucion;
use App\Models\DevolucionLinea;
use App\Models\Factura;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrarAbonosDevolucionesConsolidados extends Command
{
    protected $signature = 'migrar:abonos-devoluciones';
    protected $description = 'Migra abonos, recibos consolidados y devoluciones de kahely_legacy (re-ejecutable, no duplica)';

    public function handle(): int
    {
        $mapaClientes = Cliente::todasLasSucursales()->pluck('id', 'cliente_id_legacy');
        $mapaFacturas = Factura::todasLasSucursales()->pluck('id', 'factura_id_legacy');

        $this->info('Migrando abonos...');
        $this->migrarAbonos($mapaClientes, $mapaFacturas);

        $this->info('Migrando recibos consolidados...');
        $this->migrarConsolidados($mapaClientes);

        $this->info('Migrando devoluciones...');
        $this->migrarDevoluciones($mapaClientes, $mapaFacturas);

        $this->info('Listo.');
        return self::SUCCESS;
    }

    protected function migrarAbonos($mapaClientes, $mapaFacturas): void
    {
        $yaMigrados = DB::table('abonos')->pluck('abono_id_legacy')->flip();
        $total = 0;

        DB::connection('legacy')->table('abono')
            ->whereIn('clienteid', array_keys($mapaClientes->toArray()))
            ->orderBy('id')
            ->chunkById(1000, function ($abonos) use (&$total, $mapaClientes, $mapaFacturas, $yaMigrados) {
                $filas = [];
                foreach ($abonos as $a) {
                    if (isset($yaMigrados[$a->id])) continue; // ya migrado en una corrida anterior

                    $clienteId = $mapaClientes[$a->clienteid] ?? null;
                    if (!$clienteId) continue;

                    $filas[] = [
                        'cliente_id' => $clienteId,
                        'factura_id' => $mapaFacturas[$a->facturaid] ?? null,
                        'saldo_inicial' => $a->saldoinicial,
                        'saldo_final' => $a->saldofinal,
                        'sinpe' => $a->sinpe,
                        'efectivo' => $a->efectivo,
                        'monto_abono' => $a->abono,
                        'descuento' => $a->descuento ?? 0,
                        'devolucion' => $a->devolucion ?? 0,
                        'es_abono' => $a->isabono === 'Y',
                        'fecha' => date('Y-m-d H:i:s', $a->datecreated),
                        'abono_id_legacy' => $a->id,
                        'created_at' => now(), 'updated_at' => now(),
                    ];
                }
                if ($filas) {
                    DB::table('abonos')->insert($filas);
                    $total += count($filas);
                }
            });

        $this->info("$total abonos nuevos migrados.");
    }

    protected function migrarConsolidados($mapaClientes): void
    {
        $yaMigrados = DB::table('recibos_consolidados')->pluck('consolidado_id_legacy')->flip();
        $total = 0;

        DB::connection('legacy')->table('consolidado')
            ->whereIn('clienteid', array_keys($mapaClientes->toArray()))
            ->orderBy('id')
            ->chunkById(500, function ($consolidados) use (&$total, $mapaClientes, $yaMigrados) {
                $filas = [];
                foreach ($consolidados as $c) {
                    if (isset($yaMigrados[$c->id])) continue;

                    $clienteId = $mapaClientes[$c->clienteid] ?? null;
                    if (!$clienteId) continue;

                    $filas[] = [
                        'cliente_id' => $clienteId,
                        'monto_total' => $c->total ?? 0,
                        'detalle_facturas_legacy' => $c->facturas,
                        'admin_registro' => $c->adminname,
                        'fecha' => date('Y-m-d H:i:s', $c->datecreated),
                        'consolidado_id_legacy' => $c->id,
                        'created_at' => now(), 'updated_at' => now(),
                    ];
                }
                if ($filas) {
                    DB::table('recibos_consolidados')->insert($filas);
                    $total += count($filas);
                }
            });

        $this->info("$total recibos consolidados nuevos migrados.");
    }

    protected function migrarDevoluciones($mapaClientes, $mapaFacturas): void
    {
        $yaMigrados = DB::table('devoluciones')->pluck('devolucion_id_legacy')->flip();
        $total = 0;
        $idsNuevos = []; // legacy devolucionid => nuevo id, para migrar lineadev después

        DB::connection('legacy')->table('devolucion')
            ->join('factura', 'devolucion.facturaid', '=', 'factura.id')
            ->whereIn('factura.clienteid', array_keys($mapaClientes->toArray()))
            ->orderBy('devolucion.id')
            ->select('devolucion.*', 'factura.clienteid as factura_clienteid')
            ->get()
            ->each(function ($d) use (&$total, &$idsNuevos, $mapaClientes, $mapaFacturas, $yaMigrados) {
                if (isset($yaMigrados[$d->id])) return;

                $nueva = Devolucion::create([
                    'cliente_id' => $mapaClientes[$d->factura_clienteid] ?? null,
                    'factura_id' => $mapaFacturas[$d->facturaid] ?? null,
                    'estado_legacy' => $d->estado,
                    'motivo' => MotivoDevolucion::desdeLegacy($d->descripcion)->value,
                    'total_lineas' => $d->totallineas,
                    'monto_total' => 0, 'impuesto' => 0, 'descuento' => 0, 'total' => 0, // se completa abajo con piedev
                    'fecha' => date('Y-m-d H:i:s', $d->fecharegistro),
                    'devolucion_id_legacy' => $d->id,
                ]);

                $pie = DB::connection('legacy')->table('piedev')->where('devolucionid', $d->id)->first();
                if ($pie) {
                    $nueva->update([
                        'monto_total' => $pie->montototal,
                        'impuesto' => $pie->impuesto,
                        'descuento' => $pie->descuento,
                        'total' => $pie->total,
                    ]);
                }

                $idsNuevos[$d->id] = $nueva->id;
                $total++;
            });

        $this->info("$total devoluciones nuevas migradas.");

        // Líneas de detalle de las devoluciones recién creadas
        if ($idsNuevos) {
            $totalLineas = 0;
            DB::connection('legacy')->table('lineadev')
                ->whereIn('devolucionid', array_keys($idsNuevos))
                ->get()
                ->each(function ($l) use ($idsNuevos, &$totalLineas) {
                    DevolucionLinea::create([
                        'devolucion_id' => $idsNuevos[$l->devolucionid],
                        'producto_id' => $l->productoid,
                        'descripcion' => $l->descripcion,
                        'cantidad' => $l->cantidad,
                        'precio_unit' => $l->preciounit,
                        'precio_total' => $l->preciototal,
                        'descuento' => $l->descuento ?? 0,
                        'impuesto' => $l->impuesto,
                        'costo' => $l->costo ?? 0,
                    ]);
                    $totalLineas++;
                });
            $this->info("$totalLineas líneas de devolución migradas.");
        }
    }
}