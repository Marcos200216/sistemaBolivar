<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnosticarSaldosClientes extends Command
{
    protected $signature = 'diagnostico:saldos-clientes
        {--estados=R : Letras de estado de factura vieja que cuentan como deuda abierta, separadas por coma}
        {--cliente= : id (nuevo) de un solo cliente}
        {--limite=40 : cuántas diferencias mostrar en pantalla}
        {--csv= : ruta de un CSV donde guardar TODAS las diferencias (ej: storage/app/ajustes_saldos.csv)}';

    protected $description = 'SOLO LECTURA. Compara el saldo guardado de cada cliente con el recalculado desde las facturas abiertas del sistema viejo.';

    public function handle(): int
    {
        $estados = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('estados')))));
        if (!$estados) {
            $this->error('Indicá al menos un estado, por ejemplo --estados=R');
            return self::FAILURE;
        }

        $clientes = DB::table('clientes')
            ->whereNotNull('cliente_id_legacy')
            ->when($this->option('cliente'), fn ($q, $id) => $q->where('id', $id))
            ->get(['id', 'codigo', 'nombre', 'sucursal_id', 'cliente_id_legacy', 'saldo_actual']);

        $legacy = DB::connection('legacy');
        $correcto = [];            // clienteid legacy => suma de saldos de facturas abiertas
        $erroneo = [];             // clienteid legacy => saldofinal del último abono (lo que dejó la migración)
        $facturaUltimoAbono = [];  // clienteid legacy => facturaid de ese último abono
        $facturasAbiertas = [];    // clienteid legacy => cuántas facturas abiertas tiene

        $this->info('Leyendo el sistema viejo...');

        foreach (array_chunk($clientes->pluck('cliente_id_legacy')->all(), 300) as $lote) {
            $ultimoPorCliente = [];
            $ultimoPorFactura = [];
            $abonos = $legacy->table('abono')
                ->whereIn('clienteid', $lote)
                ->orderBy('id')
                ->select('id', 'clienteid', 'facturaid', 'saldofinal')
                ->cursor();
            foreach ($abonos as $a) {
                $ultimoPorCliente[$a->clienteid] = [(float) $a->saldofinal, $a->facturaid];
                $ultimoPorFactura[$a->facturaid] = (float) $a->saldofinal;
            }
            foreach ($ultimoPorCliente as $cid => [$saldo, $fid]) {
                $erroneo[$cid] = $saldo;
                $facturaUltimoAbono[$cid] = $fid;
            }

            $facturas = $legacy->table('factura')
                ->whereIn('clienteid', $lote)
                ->whereIn('estado', $estados)
                ->get(['id', 'clienteid']);

            $sinAbonos = $facturas->filter(fn ($f) => !array_key_exists($f->id, $ultimoPorFactura))->pluck('id')->all();
            $totales = $sinAbonos
                ? $legacy->table('pie')->whereIn('facturaid', $sinAbonos)->pluck('total', 'facturaid')->all()
                : [];

            foreach ($facturas as $f) {
                $saldo = array_key_exists($f->id, $ultimoPorFactura)
                    ? $ultimoPorFactura[$f->id]
                    : (float) ($totales[$f->id] ?? 0);
                $correcto[$f->clienteid] = ($correcto[$f->clienteid] ?? 0) + $saldo;
                $facturasAbiertas[$f->clienteid] = ($facturasAbiertas[$f->clienteid] ?? 0) + 1;
            }
        }

        // Estado actual (en el viejo) de la factura del último abono de cada cliente
        $estadoFactura = [];
        foreach (array_chunk(array_values(array_unique($facturaUltimoAbono)), 500) as $ids) {
            foreach ($legacy->table('factura')->whereIn('id', $ids)->pluck('estado', 'id') as $id => $e) {
                $estadoFactura[$id] = $e;
            }
        }

        $difs = [];
        $sumaGuardado = 0;
        $sumaPropuesto = 0;
        $conMovimientos = 0;
        $reaparecen = 0;
        $positivos = 0;
        $negativos = 0;

        foreach ($clientes as $c) {
            $cid = $c->cliente_id_legacy;
            $guardado = round((float) $c->saldo_actual, 2);
            $err = round($erroneo[$cid] ?? 0, 2);
            $ok = round($correcto[$cid] ?? 0, 2);
            $ajuste = round($ok - $err, 2);
            $propuesto = round($guardado + $ajuste, 2);

            $sumaGuardado += $guardado;
            $sumaPropuesto += $propuesto;
            if (abs($guardado - $err) > 0.005) {
                $conMovimientos++;
            }
            if (abs($ajuste) > 0.005) {
                if ($guardado <= 0 && $propuesto > 0) {
                    $reaparecen++;
                }
                $ajuste > 0 ? $positivos++ : $negativos++;
                $estUlt = $estadoFactura[$facturaUltimoAbono[$cid] ?? 0] ?? '-';
                // 0 id, 1 suc, 2 nombre corto, 3 guardado, 4 propuesto, 5 ajuste, 6 estado últ. abono, 7 legacy, 8 código, 9 nombre, 10 facturas abiertas
                $difs[] = [$c->id, $c->sucursal_id, mb_substr($c->nombre, 0, 28), $guardado, $propuesto, $ajuste, $estUlt, $cid, $c->codigo, $c->nombre, $facturasAbiertas[$cid] ?? 0];
            }
        }

        usort($difs, fn ($a, $b) => abs($b[5]) <=> abs($a[5]));

        $this->info('Estados contados como deuda abierta: ' . implode(',', $estados));
        $this->line('Clientes revisados:                    ' . $clientes->count());
        $this->line('Clientes con diferencia:               ' . count($difs) . " ($positivos suben, $negativos bajan)");
        $this->line('Pasarían de <=0 a deuda (reaparecen):  ' . $reaparecen);
        $this->line('Con movimientos desde la carga:        ' . $conMovimientos);
        $this->line('Total por cobrar guardado:             ' . number_format($sumaGuardado, 2));
        $this->line('Total por cobrar propuesto:            ' . number_format($sumaPropuesto, 2));
        $this->newLine();

        $f2 = fn ($v) => number_format($v, 2);
        $this->table(
            ['id', 'suc', 'cliente', 'guardado', 'propuesto', 'ajuste', 'est.últ.abono'],
            array_map(fn ($r) => [$r[0], $r[1], $r[2], $f2($r[3]), $f2($r[4]), $f2($r[5]), $r[6]],
                array_slice($difs, 0, (int) $this->option('limite')))
        );

        if ($ruta = $this->option('csv')) {
            $dir = dirname($ruta);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            $fh = @fopen($ruta, 'w');
            if (!$fh) {
                $this->error("No pude crear el archivo $ruta");
            } else {
                // Separador ";" y decimales con coma: así lo abre bien Excel en español
                $n = fn ($v) => number_format($v, 2, ',', '');
                fwrite($fh, "\xEF\xBB\xBF");
                fputcsv($fh, ['cliente_id', 'cliente_id_legacy', 'sucursal_id', 'codigo', 'nombre', 'saldo_guardado', 'saldo_propuesto', 'ajuste', 'facturas_abiertas', 'estado_factura_ultimo_abono'], ';');
                foreach ($difs as $r) {
                    fputcsv($fh, [$r[0], $r[7], $r[1], $r[8], $r[9], $n($r[3]), $n($r[4]), $n($r[5]), $r[10], $r[6]], ';');
                }
                fclose($fh);
                $this->info('CSV guardado en: ' . $ruta . ' (' . count($difs) . ' filas)');
            }
        }

        $this->warn('SOLO LECTURA: no se modificó ningún dato.');

        return self::SUCCESS;
    }
}