<?php

namespace App\Console\Commands;

use App\Enums\EstadoFactura;
use App\Models\Abono;
use App\Models\Devolucion;
use App\Models\DevolucionLinea;
use App\Models\Factura;
use App\Models\FacturaLinea;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerificarMigracionCompleta extends Command
{
    protected $signature = 'verificar:migracion-completa
        {--limite=40 : cuántas diferencias mostrar en pantalla}
        {--cliente= : revisar solo este cliente (id de sistema_nuevo)}
        {--csv= : ruta de un CSV con todas las diferencias}';

    protected $description = 'SOLO LECTURA. Compara, cliente por cliente, facturas, líneas, abonos, devoluciones y consolidados migrados contra el sistema viejo.';

    private const TIENDAS = [5, 10, 11, 12, 13];

    /** Métricas comparadas. Las que terminan en "_dif", "_faltan" y "_sobran" esperan 0 en ambos lados. */
    private const METRICAS = [
        'fact_n' => 'Facturas (cantidad)',
        'fact_total' => 'Facturas (suma del total)',
        'fact_faltan' => 'Facturas del viejo que NO están en el nuevo',
        'fact_sobran' => 'Facturas del nuevo que NO están en el viejo',
        'fact_total_dif' => 'Facturas con total distinto',
        'fact_estado_dif' => 'Facturas con estado distinto',
        'fact_fecha_dif' => 'Facturas con fecha distinta',
        'lin_n' => 'Líneas de factura (cantidad)',
        'lin_monto' => 'Líneas de factura (cantidad x precio)',
        'abo_n' => 'Abonos (cantidad)',
        'abo_monto' => 'Abonos (suma del monto)',
        'abo_efectivo' => 'Abonos (suma efectivo)',
        'abo_sinpe' => 'Abonos (suma sinpe)',
        'abo_desc' => 'Abonos (suma descuento)',
        'abo_dev' => 'Abonos (suma devolución)',
        'dev_n' => 'Devoluciones (cantidad)',
        'dev_total' => 'Devoluciones (suma del total)',
        'devlin_n' => 'Líneas de devolución (cantidad)',
        'devlin_monto' => 'Líneas de devolución (suma)',
        'con_n' => 'Recibos consolidados (cantidad)',
        'con_total' => 'Recibos consolidados (suma)',
    ];

    private const CONTEOS = [
        'fact_n', 'fact_faltan', 'fact_sobran', 'fact_total_dif', 'fact_estado_dif', 'fact_fecha_dif',
        'lin_n', 'abo_n', 'dev_n', 'devlin_n', 'con_n',
    ];

    private int $piesDuplicados = 0;
    private int $piedevDuplicados = 0;

    public function handle(): int
    {
        $q = DB::table('clientes')->whereNotNull('cliente_id_legacy');
        if ($solo = $this->option('cliente')) {
            $q->where('id', (int) $solo);
        }
        $clientes = $q->orderBy('id')->get(['id', 'codigo', 'nombre', 'sucursal_id', 'cliente_id_legacy']);

        if ($clientes->isEmpty()) {
            $this->error('No hay clientes migrados para revisar.');
            return self::FAILURE;
        }

        $legacy = DB::connection('legacy');
        $viejo = [];
        $nuevo = [];

        $this->info('Comparando ' . $clientes->count() . ' clientes contra el sistema viejo (puede tardar un poco)...');
        foreach ($clientes->chunk(300) as $grupo) {
            $this->compararLote($legacy, $grupo, $viejo, $nuevo);
            $this->output->write('.');
        }
        $this->newLine(2);

        // ---- Resultado por métrica y por cliente ----
        $resumen = [];
        foreach (self::METRICAS as $k => $_) {
            $resumen[$k] = ['viejo' => 0.0, 'nuevo' => 0.0, 'clientes' => 0];
        }
        $difs = [];
        $clientesConDif = [];

        foreach ($clientes as $c) {
            $cid = (int) $c->cliente_id_legacy;
            foreach (self::METRICAS as $k => $label) {
                $v = round($viejo[$cid][$k] ?? 0, 2);
                $n = round($nuevo[$cid][$k] ?? 0, 2);
                $resumen[$k]['viejo'] += $v;
                $resumen[$k]['nuevo'] += $n;
                if (abs($n - $v) > 0.005) {
                    $resumen[$k]['clientes']++;
                    $clientesConDif[$c->id] = true;
                    $difs[] = [$c->id, $c->sucursal_id, $c->codigo, (string) $c->nombre, $label, $n, $v, round($n - $v, 2), $k];
                }
            }
        }

        $fmt = fn (string $k, float $x) => in_array($k, self::CONTEOS, true) ? number_format($x, 0) : number_format($x, 2);

        $filas = [];
        foreach (self::METRICAS as $k => $label) {
            $r = $resumen[$k];
            $ok = $r['clientes'] === 0 ? 'OK' : $r['clientes'] . ' cliente(s)';
            $filas[] = [$label, $fmt($k, $r['viejo']), $fmt($k, $r['nuevo']), $ok];
        }
        $this->table(['Métrica', 'Sistema viejo', 'Sistema nuevo', 'Difieren'], $filas);

        $this->line('Clientes revisados:       ' . $clientes->count());
        $this->line('Clientes con alguna diferencia: ' . count($clientesConDif));

        // ---- Clientes del viejo que no llegaron al nuevo ----
        if (!$this->option('cliente')) {
            $idsViejo = $legacy->table('cliente')->whereIn('tiendaid', self::TIENDAS)->pluck('id')->map(fn ($i) => (int) $i)->all();
            $idsNuevo = DB::table('clientes')->whereNotNull('cliente_id_legacy')->pluck('cliente_id_legacy')->map(fn ($i) => (int) $i)->all();
            $faltan = array_values(array_diff($idsViejo, $idsNuevo));
            $sobran = array_values(array_diff($idsNuevo, $idsViejo));
            $this->line('Clientes en el viejo (tiendas 5,10,11,12,13): ' . count($idsViejo) . ' · migrados: ' . count($idsNuevo));
            $this->line('Clientes del viejo que NO están en el nuevo: ' . count($faltan) . ($faltan ? ' (ids viejos: ' . implode(', ', array_slice($faltan, 0, 15)) . ')' : ''));
            $this->line('Clientes del nuevo que NO están en el viejo: ' . count($sobran) . ($sobran ? ' (ids viejos: ' . implode(', ', array_slice($sobran, 0, 15)) . ')' : ''));
        }

        $this->line('Facturas del viejo con más de una fila en "pie": ' . $this->piesDuplicados . ' (se usó la primera, igual que la migración)');
        $this->line('Devoluciones del viejo con más de una fila en "piedev": ' . $this->piedevDuplicados);
        $this->newLine();

        usort($difs, fn ($a, $b) => abs($b[7]) <=> abs($a[7]));
        if ($difs) {
            $this->warn('Mayores diferencias:');
            $this->table(
                ['cliente', 'suc', 'nombre', 'métrica', 'nuevo', 'viejo', 'dif'],
                array_map(fn ($r) => [$r[0], $r[1], mb_substr($r[3], 0, 24), $r[4], $fmt($r[8], $r[5]), $fmt($r[8], $r[6]), $fmt($r[8], $r[7])],
                    array_slice($difs, 0, (int) $this->option('limite')))
            );
        } else {
            $this->info('Sin diferencias en ninguna métrica.');
        }

        if ($ruta = $this->option('csv')) {
            $dir = dirname($ruta);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            if ($fh = @fopen($ruta, 'w')) {
                fwrite($fh, "\xEF\xBB\xBF");
                fputcsv($fh, ['cliente_id', 'sucursal_id', 'codigo', 'nombre', 'metrica', 'nuevo', 'viejo', 'diferencia'], ';');
                foreach ($difs as $r) {
                    fputcsv($fh, [$r[0], $r[1], $r[2], $r[3], $r[4],
                        number_format($r[5], 2, ',', ''), number_format($r[6], 2, ',', ''), number_format($r[7], 2, ',', '')], ';');
                }
                fclose($fh);
                $this->info("CSV guardado en: {$ruta} (" . count($difs) . ' filas)');
            }
        }

        $this->warn('SOLO LECTURA: no se modificó ningún dato.');

        return self::SUCCESS;
    }

    private function sumar(array &$arr, int $cid, string $k, float $v): void
    {
        $arr[$cid][$k] = ($arr[$cid][$k] ?? 0) + $v;
    }

    private function compararLote($legacy, $grupo, array &$viejo, array &$nuevo): void
    {
        $legIds = $grupo->pluck('cliente_id_legacy')->map(fn ($i) => (int) $i)->all();
        $nuevoDeLegacy = $grupo->pluck('id', 'cliente_id_legacy')->all(); // legacy => nuevo
        $legacyDeNuevo = [];
        foreach ($nuevoDeLegacy as $leg => $nue) {
            $legacyDeNuevo[(int) $nue] = (int) $leg;
        }
        $nuevosIds = array_values($nuevoDeLegacy);

        $tFact = (new Factura)->getTable();
        $tLin = (new FacturaLinea)->getTable();
        $tAbo = (new Abono)->getTable();
        $tDev = (new Devolucion)->getTable();
        $tDevLin = (new DevolucionLinea)->getTable();

        // =========================== SISTEMA VIEJO ===========================
        $facturas = $legacy->table('factura')->whereIn('clienteid', $legIds)->get(['id', 'clienteid', 'estado', 'fecharegistro']);
        $facIds = $facturas->pluck('id')->all();

        $pies = [];
        foreach (array_chunk($facIds, 1000) as $ids) {
            foreach ($legacy->table('pie')->whereIn('facturaid', $ids)->get(['facturaid', 'total']) as $p) {
                if (isset($pies[$p->facturaid])) {
                    $this->piesDuplicados++;
                    continue;
                }
                $pies[$p->facturaid] = (float) $p->total;
            }
        }

        $factViejo = [];
        $facCliViejo = [];
        foreach ($facturas as $f) {
            $cid = (int) $f->clienteid;
            $total = $pies[$f->id] ?? 0.0;
            $factViejo[$f->id] = [
                'cid' => $cid,
                'estado' => EstadoFactura::desdeLegacy($f->estado)->value,
                'fecha' => $f->fecharegistro ? date('Y-m-d H:i:s', (int) $f->fecharegistro) : null,
                'total' => round($total, 2),
            ];
            $facCliViejo[$f->id] = $cid;
            $this->sumar($viejo, $cid, 'fact_n', 1);
            $this->sumar($viejo, $cid, 'fact_total', $total);
        }

        foreach (array_chunk($facIds, 1000) as $ids) {
            $lineas = $legacy->table('linea')->whereIn('facturaid', $ids)->select('facturaid', 'cantidad', 'preciounit')->cursor();
            foreach ($lineas as $l) {
                $cid = $facCliViejo[$l->facturaid] ?? null;
                if ($cid === null) {
                    continue;
                }
                $this->sumar($viejo, $cid, 'lin_n', 1);
                $this->sumar($viejo, $cid, 'lin_monto', (float) $l->cantidad * (float) $l->preciounit);
            }
        }

        $abonos = $legacy->table('abono')->whereIn('clienteid', $legIds)
            ->select('clienteid', 'abono', 'efectivo', 'sinpe', 'descuento', 'devolucion')->cursor();
        foreach ($abonos as $a) {
            $cid = (int) $a->clienteid;
            $this->sumar($viejo, $cid, 'abo_n', 1);
            $this->sumar($viejo, $cid, 'abo_monto', (float) $a->abono);
            $this->sumar($viejo, $cid, 'abo_efectivo', (float) $a->efectivo);
            $this->sumar($viejo, $cid, 'abo_sinpe', (float) $a->sinpe);
            $this->sumar($viejo, $cid, 'abo_desc', (float) ($a->descuento ?? 0));
            $this->sumar($viejo, $cid, 'abo_dev', (float) ($a->devolucion ?? 0));
        }

        $devs = $legacy->table('devolucion as d')->join('factura as f', 'd.facturaid', '=', 'f.id')
            ->whereIn('f.clienteid', $legIds)->get(['d.id as did', 'f.clienteid as cid']);
        $devCliViejo = [];
        foreach ($devs as $d) {
            $devCliViejo[$d->did] = (int) $d->cid;
            $this->sumar($viejo, (int) $d->cid, 'dev_n', 1);
        }
        $piedev = [];
        foreach (array_chunk(array_keys($devCliViejo), 1000) as $ids) {
            foreach ($legacy->table('piedev')->whereIn('devolucionid', $ids)->get(['devolucionid', 'total']) as $p) {
                if (isset($piedev[$p->devolucionid])) {
                    $this->piedevDuplicados++;
                    continue;
                }
                $piedev[$p->devolucionid] = (float) $p->total;
            }
            $lineasDev = $legacy->table('lineadev')->whereIn('devolucionid', $ids)->select('devolucionid', 'preciototal')->cursor();
            foreach ($lineasDev as $l) {
                $cid = $devCliViejo[$l->devolucionid] ?? null;
                if ($cid === null) {
                    continue;
                }
                $this->sumar($viejo, $cid, 'devlin_n', 1);
                $this->sumar($viejo, $cid, 'devlin_monto', (float) $l->preciototal);
            }
        }
        foreach ($devCliViejo as $did => $cid) {
            $this->sumar($viejo, $cid, 'dev_total', $piedev[$did] ?? 0.0);
        }

        foreach ($legacy->table('consolidado')->whereIn('clienteid', $legIds)->get(['clienteid', 'total']) as $c) {
            $this->sumar($viejo, (int) $c->clienteid, 'con_n', 1);
            $this->sumar($viejo, (int) $c->clienteid, 'con_total', (float) ($c->total ?? 0));
        }

        // =========================== SISTEMA NUEVO ===========================
        $facN = DB::table($tFact)->whereIn('cliente_id', $nuevosIds)->whereNotNull('factura_id_legacy')
            ->get(['id', 'cliente_id', 'estado', 'total', 'fecha', 'factura_id_legacy']);
        $factNuevo = [];
        $facCliNuevo = [];
        foreach ($facN as $f) {
            $cid = $legacyDeNuevo[(int) $f->cliente_id];
            $factNuevo[$f->factura_id_legacy] = [
                'estado' => (string) $f->estado,
                'fecha' => $f->fecha ? substr((string) $f->fecha, 0, 19) : null,
                'total' => round((float) $f->total, 2),
            ];
            $facCliNuevo[$f->id] = $cid;
            $this->sumar($nuevo, $cid, 'fact_n', 1);
            $this->sumar($nuevo, $cid, 'fact_total', (float) $f->total);
        }

        // Comparación factura por factura
        foreach ($factViejo as $idLeg => $v) {
            $cid = $v['cid'];
            $n = $factNuevo[$idLeg] ?? null;
            if ($n === null) {
                $this->sumar($nuevo, $cid, 'fact_faltan', 1);
                continue;
            }
            if (abs($n['total'] - $v['total']) > 0.005) {
                $this->sumar($nuevo, $cid, 'fact_total_dif', 1);
            }
            if ($n['estado'] !== $v['estado']) {
                $this->sumar($nuevo, $cid, 'fact_estado_dif', 1);
            }
            if ($n['fecha'] !== $v['fecha']) {
                $this->sumar($nuevo, $cid, 'fact_fecha_dif', 1);
            }
        }
        foreach ($factNuevo as $idLeg => $_) {
            if (!isset($factViejo[$idLeg])) {
                // factura del nuevo sin pareja en el viejo: se atribuye al cliente con otra búsqueda simple
                $cid = null;
                foreach ($facN as $f) {
                    if ($f->factura_id_legacy == $idLeg) {
                        $cid = $legacyDeNuevo[(int) $f->cliente_id];
                        break;
                    }
                }
                if ($cid !== null) {
                    $this->sumar($nuevo, $cid, 'fact_sobran', 1);
                }
            }
        }

        foreach (array_chunk(array_keys($facCliNuevo), 1000) as $ids) {
            $lineas = DB::table($tLin)->whereIn('factura_id', $ids)->select('factura_id', 'cantidad', 'precio_unit')->cursor();
            foreach ($lineas as $l) {
                $cid = $facCliNuevo[$l->factura_id] ?? null;
                if ($cid === null) {
                    continue;
                }
                $this->sumar($nuevo, $cid, 'lin_n', 1);
                $this->sumar($nuevo, $cid, 'lin_monto', (float) $l->cantidad * (float) $l->precio_unit);
            }
        }

        $abN = DB::table($tAbo)->whereIn('cliente_id', $nuevosIds)->whereNotNull('abono_id_legacy')
            ->select('cliente_id', 'monto_abono', 'efectivo', 'sinpe', 'descuento', 'devolucion')->cursor();
        foreach ($abN as $a) {
            $cid = $legacyDeNuevo[(int) $a->cliente_id];
            $this->sumar($nuevo, $cid, 'abo_n', 1);
            $this->sumar($nuevo, $cid, 'abo_monto', (float) $a->monto_abono);
            $this->sumar($nuevo, $cid, 'abo_efectivo', (float) $a->efectivo);
            $this->sumar($nuevo, $cid, 'abo_sinpe', (float) $a->sinpe);
            $this->sumar($nuevo, $cid, 'abo_desc', (float) ($a->descuento ?? 0));
            $this->sumar($nuevo, $cid, 'abo_dev', (float) ($a->devolucion ?? 0));
        }

        $devN = DB::table($tDev)->whereIn('cliente_id', $nuevosIds)->whereNotNull('devolucion_id_legacy')
            ->get(['id', 'cliente_id', 'total']);
        $devCliNuevo = [];
        foreach ($devN as $d) {
            $cid = $legacyDeNuevo[(int) $d->cliente_id];
            $devCliNuevo[$d->id] = $cid;
            $this->sumar($nuevo, $cid, 'dev_n', 1);
            $this->sumar($nuevo, $cid, 'dev_total', (float) $d->total);
        }
        foreach (array_chunk(array_keys($devCliNuevo), 1000) as $ids) {
            $lineasDev = DB::table($tDevLin)->whereIn('devolucion_id', $ids)->select('devolucion_id', 'precio_total')->cursor();
            foreach ($lineasDev as $l) {
                $cid = $devCliNuevo[$l->devolucion_id] ?? null;
                if ($cid === null) {
                    continue;
                }
                $this->sumar($nuevo, $cid, 'devlin_n', 1);
                $this->sumar($nuevo, $cid, 'devlin_monto', (float) $l->precio_total);
            }
        }

        $conN = DB::table('recibos_consolidados')->whereIn('cliente_id', $nuevosIds)->whereNotNull('consolidado_id_legacy')
            ->get(['cliente_id', 'monto_total']);
        foreach ($conN as $c) {
            $cid = $legacyDeNuevo[(int) $c->cliente_id];
            $this->sumar($nuevo, $cid, 'con_n', 1);
            $this->sumar($nuevo, $cid, 'con_total', (float) $c->monto_total);
        }
    }
}