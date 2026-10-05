<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerificarSaldosClientes extends Command
{
    protected $signature = 'verificar:saldos-clientes
        {--limite=40 : cuántas diferencias mostrar en pantalla}
        {--csv= : ruta de un CSV con todas las diferencias}';

    protected $description = 'SOLO LECTURA. Compara el saldo de cada cliente migrado con lo que muestra el sistema viejo (facturas en estado R).';

    private const TIENDAS = [5, 10, 11, 12, 13];

    public function handle(): int
    {
        $clientes = DB::table('clientes')
            ->whereNotNull('cliente_id_legacy')
            ->get(['id', 'codigo', 'nombre', 'sucursal_id', 'cliente_id_legacy', 'saldo_actual']);

        $legacy = DB::connection('legacy');
        $todas = []; // clienteid legacy => saldo de sus facturas R (cualquier tienda)
        $cinco = []; // lo mismo, solo facturas emitidas en Guana y Azur 1 a 4

        $this->info('Leyendo el sistema viejo...');
        foreach (array_chunk($clientes->pluck('cliente_id_legacy')->all(), 300) as $lote) {
            $ultimoPorFactura = [];
            $abonos = $legacy->table('abono')
                ->whereIn('clienteid', $lote)
                ->orderBy('id')
                ->select('id', 'facturaid', 'saldofinal')
                ->cursor();
            foreach ($abonos as $a) {
                $ultimoPorFactura[$a->facturaid] = (float) $a->saldofinal;
            }

            $facturas = $legacy->table('factura')
                ->whereIn('clienteid', $lote)
                ->where('estado', 'R')
                ->get(['id', 'clienteid', 'tiendaid']);

            $sinAbonos = $facturas->filter(fn ($f) => !array_key_exists($f->id, $ultimoPorFactura))->pluck('id')->all();
            $totales = $sinAbonos
                ? $legacy->table('pie')->whereIn('facturaid', $sinAbonos)->pluck('total', 'facturaid')->all()
                : [];

            foreach ($facturas as $f) {
                $saldo = array_key_exists($f->id, $ultimoPorFactura)
                    ? $ultimoPorFactura[$f->id]
                    : (float) ($totales[$f->id] ?? 0);
                $todas[$f->clienteid] = ($todas[$f->clienteid] ?? 0) + $saldo;
                if (in_array((int) $f->tiendaid, self::TIENDAS, true)) {
                    $cinco[$f->clienteid] = ($cinco[$f->clienteid] ?? 0) + $saldo;
                }
            }
        }

        $coinciden = 0;
        $conOtrasTiendas = 0;
        $sumaNuevo = 0;
        $sumaViejo = 0;
        $difs = [];

        foreach ($clientes as $c) {
            $cid = $c->cliente_id_legacy;
            $nuevo = round((float) $c->saldo_actual, 2);
            $viejo = round($todas[$cid] ?? 0, 2);
            $soloCinco = round($cinco[$cid] ?? 0, 2);

            $sumaNuevo += $nuevo;
            $sumaViejo += $viejo;
            if (abs($viejo - $soloCinco) > 0.005) {
                $conOtrasTiendas++; // su deuda incluye facturas emitidas en las tiendas 6 o 7
            }
            if (abs($nuevo - $viejo) < 0.005) {
                $coinciden++;
            } else {
                $difs[] = [$c->id, $c->sucursal_id, $c->codigo, (string) $c->nombre, $nuevo, $viejo, round($nuevo - $viejo, 2)];
            }
        }

        usort($difs, fn ($a, $b) => abs($b[6]) <=> abs($a[6]));

        $this->line('Clientes revisados:               ' . $clientes->count());
        $this->line('Coinciden con el sistema viejo:   ' . $coinciden);
        $this->line('Difieren:                         ' . count($difs));
        $this->line('Con deuda de tiendas 6 o 7:       ' . $conOtrasTiendas . ' (ya incluida en su saldo)');
        $this->line('Suma sistema nuevo:               ' . number_format($sumaNuevo, 2));
        $this->line('Suma sistema viejo:               ' . number_format($sumaViejo, 2));
        $this->newLine();

        $f2 = fn ($v) => number_format($v, 2);
        $this->table(
            ['id', 'suc', 'cliente', 'nuevo', 'viejo', 'diferencia'],
            array_map(fn ($r) => [$r[0], $r[1], mb_substr($r[3], 0, 28), $f2($r[4]), $f2($r[5]), $f2($r[6])],
                array_slice($difs, 0, (int) $this->option('limite')))
        );

        if ($ruta = $this->option('csv')) {
            $dir = dirname($ruta);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            if ($fh = @fopen($ruta, 'w')) {
                fwrite($fh, "\xEF\xBB\xBF");
                fputcsv($fh, ['cliente_id', 'sucursal_id', 'codigo', 'nombre', 'saldo_nuevo', 'saldo_viejo', 'diferencia'], ';');
                foreach ($difs as $r) {
                    fputcsv($fh, [$r[0], $r[1], $r[2], $r[3], number_format($r[4], 2, ',', ''), number_format($r[5], 2, ',', ''), number_format($r[6], 2, ',', '')], ';');
                }
                fclose($fh);
                $this->info("CSV guardado en: {$ruta} (" . count($difs) . ' filas)');
            }
        }

        $this->warn('SOLO LECTURA: no se modificó ningún dato.');

        return self::SUCCESS;
    }
}