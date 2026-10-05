<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrarFacturasSaldos extends Command
{
    protected $signature = 'migrar:facturas-saldos
        {--apply : Escribe los cambios (sin esto es corrida en seco)}
        {--confirmar-bd= : Nombre exacto de la BD destino (obligatorio con --apply)}
        {--sql= : Guarda los UPDATE en un .sql para aplicarlos en otra BD (no escribe en esta)}
        {--revertir= : Archivo de reversa a restaurar (junto con --apply)}';

    protected $description = 'Corrige estado y fecha de las facturas migradas y guarda su saldo del sistema viejo (saldo_migrado). Corrida en seco por defecto.';

    private const ESTADOS = ['C' => 'contado', 'E' => 'anulada', 'F' => 'saldada', 'R' => 'credito'];

    public function handle(): int
    {
        $bd = DB::connection()->getDatabaseName();
        $host = (string) config('database.connections.' . config('database.default') . '.host');
        $esLocal = in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
        $this->info("Destino: BD '{$bd}' (" . ($esLocal ? 'LOCAL' : 'REMOTA') . ')');

        if (!Schema::hasColumn('facturas', 'saldo_migrado')) {
            $this->error('Falta la columna saldo_migrado. Corré primero la migración (con dump antes).');
            return self::FAILURE;
        }

        if ($this->option('revertir')) {
            return $this->revertir((string) $this->option('revertir'), $bd);
        }

        $migradas = DB::table('facturas')
            ->whereNotNull('factura_id_legacy')->whereNull('operacion_id')
            ->get(['id', 'factura_id_legacy', 'estado', 'fecha']);

        // Facturas viejas que ya recibieron abonos o devoluciones desde el sistema nuevo: no se tocan
        $tocadas = DB::table('abonos')->whereNotNull('operacion_id')->whereNotNull('factura_id')->pluck('factura_id')
            ->merge(DB::table('devoluciones')->whereNotNull('operacion_id')->whereNotNull('factura_id')->pluck('factura_id'))
            ->unique()->flip()->all();

        $this->info('Leyendo el sistema viejo...');
        $legacy = DB::connection('legacy');
        $plan = [];
        $sinLegacy = 0;
        $saltadas = 0;
        $transiciones = [];
        $nSaldo = 0;
        $sumaSaldo = 0.0;
        $nFecha = 0;

        foreach ($migradas->chunk(500) as $lote) {
            $lids = $lote->pluck('factura_id_legacy')->all();
            $viejas = $legacy->table('factura')->whereIn('id', $lids)->get(['id', 'estado', 'fecharegistro'])->keyBy('id');

            $ultimo = [];
            $abonos = $legacy->table('abono')->whereIn('facturaid', $lids)->orderBy('id')->select('facturaid', 'saldofinal')->cursor();
            foreach ($abonos as $a) {
                $ultimo[$a->facturaid] = (float) $a->saldofinal;
            }
            $sinAbonos = array_values(array_diff($lids, array_keys($ultimo)));
            $totales = $sinAbonos ? $legacy->table('pie')->whereIn('facturaid', $sinAbonos)->pluck('total', 'facturaid') : collect();

            foreach ($lote as $f) {
                $v = $viejas->get($f->factura_id_legacy);
                $estado = $v ? (self::ESTADOS[$v->estado] ?? null) : null;
                if (!$estado) {
                    $sinLegacy++;
                    continue;
                }
                if (isset($tocadas[$f->id])) {
                    $saltadas++;
                    continue;
                }

                $saldo = null;
                if ($v->estado === 'R') {
                    $saldo = array_key_exists($f->factura_id_legacy, $ultimo)
                        ? $ultimo[$f->factura_id_legacy]
                        : (float) $totales->get($f->factura_id_legacy, 0);
                    $nSaldo++;
                    $sumaSaldo += $saldo;
                }

                $fecha = null;
                if ($f->fecha === null && $v->fecharegistro) {
                    $fecha = Carbon::createFromTimestamp((int) $v->fecharegistro, config('app.timezone'))->format('Y-m-d H:i:s');
                    $nFecha++;
                }

                if ($f->estado !== $estado) {
                    $k = "{$f->estado} => {$estado}";
                    $transiciones[$k] = ($transiciones[$k] ?? 0) + 1;
                }
                $plan[] = ['id' => $f->id, 'legacy' => $f->factura_id_legacy, 'antes' => $f->estado, 'estado' => $estado, 'fecha' => $fecha, 'saldo' => $saldo];
            }
        }

        ksort($transiciones);
        $this->newLine();
        $this->line('Facturas migradas:                 ' . $migradas->count());
        $this->line('Sin factura en el viejo (omitidas): ' . $sinLegacy);
        $this->line('Ya operadas en el nuevo (omitidas): ' . $saltadas);
        $this->line('Cambios de estado:');
        foreach ($transiciones as $k => $n) {
            $this->line("   {$k}: {$n}");
        }
        $this->line('Fechas reales a cargar:            ' . $nFecha);
        $this->line('saldo_migrado a cargar:            ' . $nSaldo . ' facturas, suma ' . number_format($sumaSaldo, 2) . ' (debe dar 29,950,001.00)');
        $this->newLine();

        if ($ruta = $this->option('sql')) {
            return $this->exportarSql($ruta, $plan);
        }

        if (!$this->option('apply')) {
            $this->warn('CORRIDA EN SECO: no se escribió nada. Para aplicar: --apply --confirmar-bd=' . $bd);
            return self::SUCCESS;
        }
        if (!$this->bdConfirmada($bd)) {
            return self::FAILURE;
        }

        $reversa = storage_path('app/reversa_facturas_' . now()->format('Ymd_His') . '.csv');
        $fh = @fopen($reversa, 'w');
        if (!$fh) {
            $this->error("No pude crear la reversa en {$reversa}. No se escribió nada.");
            return self::FAILURE;
        }
        fputcsv($fh, ['factura_id', 'estado_anterior', 'estado_nuevo', 'fecha_puesta'], ';');
        foreach ($plan as $p) {
            fputcsv($fh, [$p['id'], $p['antes'], $p['estado'], $p['fecha'] ? 1 : 0], ';');
        }
        fclose($fh);
        $this->info("Reversa guardada en: {$reversa}");

        $n = 0;
        DB::transaction(function () use ($plan, &$n) {
            foreach ($plan as $p) {
                $cambios = ['estado' => $p['estado']];
                if ($p['fecha'] !== null) {
                    $cambios['fecha'] = $p['fecha'];
                }
                if ($p['saldo'] !== null) {
                    $cambios['saldo_migrado'] = number_format($p['saldo'], 2, '.', '');
                }
                $n += DB::table('facturas')->where('id', $p['id'])->whereNull('operacion_id')->update($cambios);
            }
        });
        $this->info("Facturas actualizadas: {$n}");

        return self::SUCCESS;
    }

    private function exportarSql(string $ruta, array $plan): int
    {
        $dir = dirname($ruta);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $fh = @fopen($ruta, 'w');
        if (!$fh) {
            $this->error("No pude crear {$ruta}");
            return self::FAILURE;
        }
        $guarda = ' AND operacion_id IS NULL'
            . ' AND NOT EXISTS (SELECT 1 FROM abonos a WHERE a.factura_id = facturas.id AND a.operacion_id IS NOT NULL)'
            . ' AND NOT EXISTS (SELECT 1 FROM devoluciones d WHERE d.factura_id = facturas.id AND d.operacion_id IS NOT NULL)';
        fwrite($fh, "-- Requiere la columna facturas.saldo_migrado. Respaldar antes de correr.\n");
        foreach ($plan as $p) {
            $set = "estado='{$p['estado']}'";
            if ($p['fecha'] !== null) {
                $set .= ", fecha=COALESCE(fecha,'{$p['fecha']}')";
            }
            if ($p['saldo'] !== null) {
                $set .= ', saldo_migrado=' . number_format($p['saldo'], 2, '.', '');
            }
            fwrite($fh, "UPDATE facturas SET {$set} WHERE factura_id_legacy={$p['legacy']}{$guarda};\n");
        }
        fclose($fh);
        $this->info('SQL guardado en: ' . $ruta . ' (' . count($plan) . ' sentencias). No se modificó esta BD.');

        return self::SUCCESS;
    }

    private function bdConfirmada(string $bd): bool
    {
        if ($this->option('confirmar-bd') !== $bd) {
            $this->error("Para escribir hay que confirmar la BD destino: --confirmar-bd={$bd}");
            return false;
        }
        return true;
    }

    private function revertir(string $ruta, string $bd): int
    {
        if (!is_file($ruta)) {
            $this->error("No existe el archivo: {$ruta}");
            return self::FAILURE;
        }
        $filas = [];
        $fh = fopen($ruta, 'r');
        fgetcsv($fh, 0, ';');
        while (($r = fgetcsv($fh, 0, ';')) !== false) {
            if (count($r) >= 4) {
                $filas[] = ['id' => (int) $r[0], 'antes' => $r[1], 'nuevo' => $r[2], 'fecha' => $r[3] === '1'];
            }
        }
        fclose($fh);

        $this->info('Filas en la reversa: ' . count($filas));
        if (!$this->option('apply')) {
            $this->warn('CORRIDA EN SECO: no se restauró nada. Para restaurar: --apply --confirmar-bd=' . $bd);
            return self::SUCCESS;
        }
        if (!$this->bdConfirmada($bd)) {
            return self::FAILURE;
        }

        $n = 0;
        DB::transaction(function () use ($filas, &$n) {
            foreach ($filas as $f) {
                $cambios = ['estado' => $f['antes'], 'saldo_migrado' => null];
                if ($f['fecha']) {
                    $cambios['fecha'] = null;
                }
                $n += DB::table('facturas')->where('id', $f['id'])->whereNull('operacion_id')
                    ->where('estado', $f['nuevo'])->update($cambios);
            }
        });
        $this->info("Restauradas: {$n}");

        return self::SUCCESS;
    }
}