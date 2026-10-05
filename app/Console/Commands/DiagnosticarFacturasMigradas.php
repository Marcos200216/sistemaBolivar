<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnosticarFacturasMigradas extends Command
{
    protected $signature = 'diagnostico:facturas-migradas {--limite=30 : cuántas diferencias mostrar}';

    protected $description = 'SOLO LECTURA. Compara el saldo de cada factura migrada (total - abonos - devoluciones) con el que mostraba el sistema viejo.';

    public function handle(): int
    {
        $legacy = DB::connection('legacy');
        $facturas = DB::table('facturas')
            ->whereNotNull('factura_id_legacy')
            ->whereNull('operacion_id')
            ->get(['id', 'factura_id_legacy', 'cliente_id', 'total', 'estado', 'fecha']);

        $this->info('Facturas migradas: ' . $facturas->count());
        $this->info('Leyendo el sistema viejo...');

        // Sistema viejo: estado y saldo de cada factura (último saldofinal, o el total si no tiene abonos)
        $viejo = []; // id nuevo => [estado viejo, saldo viejo]
        foreach ($facturas->chunk(500) as $lote) {
            $legacyIds = $lote->pluck('factura_id_legacy')->all();
            $estados = $legacy->table('factura')->whereIn('id', $legacyIds)->pluck('estado', 'id');

            $ultimo = [];
            $abonos = $legacy->table('abono')->whereIn('facturaid', $legacyIds)
                ->orderBy('id')->select('facturaid', 'saldofinal')->cursor();
            foreach ($abonos as $a) {
                $ultimo[$a->facturaid] = (float) $a->saldofinal;
            }

            $sinAbonos = array_values(array_diff($legacyIds, array_keys($ultimo)));
            $totales = $sinAbonos
                ? $legacy->table('pie')->whereIn('facturaid', $sinAbonos)->pluck('total', 'facturaid')
                : collect();

            foreach ($lote as $f) {
                $lid = $f->factura_id_legacy;
                $saldo = array_key_exists($lid, $ultimo) ? $ultimo[$lid] : (float) $totales->get($lid, 0);
                $viejo[$f->id] = [$estados->get($lid), $saldo];
            }
        }

        // Sistema nuevo: total - abonos - devoluciones
        $abonado = [];
        $devuelto = [];
        foreach (array_chunk($facturas->pluck('id')->all(), 1000) as $c) {
            foreach (DB::table('abonos')->whereIn('factura_id', $c)->groupBy('factura_id')
                ->selectRaw('factura_id, SUM(monto_abono) as s')->get() as $r) {
                $abonado[$r->factura_id] = (float) $r->s;
            }
            foreach (DB::table('devoluciones')->whereIn('factura_id', $c)->groupBy('factura_id')
                ->selectRaw('factura_id, SUM(total) as s')->get() as $r) {
                $devuelto[$r->factura_id] = (float) $r->s;
            }
        }

        $cruce = [];        // "estado viejo => estado nuevo" => cantidad
        $coinciden = 0;
        $difs = [];
        $sumaViejo = 0;
        $sumaNuevo = 0;
        $pendientePorCliente = [];

        foreach ($facturas as $f) {
            [$estV, $saldoV] = $viejo[$f->id];
            $k = ($estV ?? '?') . ' => ' . $f->estado;
            $cruce[$k] = ($cruce[$k] ?? 0) + 1;

            if ($estV !== 'R') {
                continue;
            }
            $nuevo = round(max(0, (float) $f->total - ($abonado[$f->id] ?? 0) - ($devuelto[$f->id] ?? 0)), 2);
            $saldoV = round($saldoV, 2);
            $sumaViejo += $saldoV;
            $sumaNuevo += $nuevo;
            $pendientePorCliente[$f->cliente_id] = ($pendientePorCliente[$f->cliente_id] ?? 0) + $nuevo;

            if (abs($nuevo - $saldoV) < 0.005) {
                $coinciden++;
            } else {
                $difs[] = [$f->id, $f->factura_id_legacy, $f->cliente_id, $saldoV, $nuevo, round($nuevo - $saldoV, 2)];
            }
        }

        // Por cliente: lo que sobra de su saldo después de explicarlo con sus facturas abiertas
        $residuos = [];
        foreach (DB::table('clientes')->whereNotNull('cliente_id_legacy')->get(['id', 'nombre', 'sucursal_id', 'saldo_actual']) as $c) {
            $r = round((float) $c->saldo_actual - ($pendientePorCliente[$c->id] ?? 0), 2);
            if (abs($r) > 0.005) {
                $residuos[] = [$c->id, $c->sucursal_id, mb_substr((string) $c->nombre, 0, 28), (float) $c->saldo_actual, $pendientePorCliente[$c->id] ?? 0, $r];
            }
        }

        ksort($cruce);
        $this->newLine();
        $this->line('Estado viejo => estado actual en el sistema nuevo:');
        foreach ($cruce as $k => $n) {
            $this->line("   {$k}: {$n}");
        }
        $this->newLine();
        $this->line('Facturas R del viejo:                ' . ($coinciden + count($difs)));
        $this->line('  Saldo por factura coincide:        ' . $coinciden);
        $this->line('  Saldo por factura difiere:         ' . count($difs));
        $this->line('  Suma saldos viejo / nuevo:         ' . number_format($sumaViejo, 2) . ' / ' . number_format($sumaNuevo, 2));
        $this->line('Migradas sin fecha:                  ' . $facturas->whereNull('fecha')->count());
        $this->line('Clientes con saldo sin explicar:     ' . count($residuos));
        $this->newLine();

        $f2 = fn ($v) => number_format($v, 2);
        $lim = (int) $this->option('limite');
        if ($difs) {
            usort($difs, fn ($a, $b) => abs($b[5]) <=> abs($a[5]));
            $this->table(['factura', 'legacy', 'cliente', 'viejo', 'nuevo', 'dif'],
                array_map(fn ($r) => [$r[0], $r[1], $r[2], $f2($r[3]), $f2($r[4]), $f2($r[5])], array_slice($difs, 0, $lim)));
        }
        if ($residuos) {
            usort($residuos, fn ($a, $b) => abs($b[5]) <=> abs($a[5]));
            $this->table(['cliente', 'suc', 'nombre', 'saldo_actual', 'suma facturas', 'sin explicar'],
                array_map(fn ($r) => [$r[0], $r[1], $r[2], $f2($r[3]), $f2($r[4]), $f2($r[5])], array_slice($residuos, 0, $lim)));
        }

        $this->warn('SOLO LECTURA: no se modificó ningún dato.');

        return self::SUCCESS;
    }
}