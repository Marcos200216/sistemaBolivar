<?php

namespace App\Console\Commands;

use App\Services\RutaSaldoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CorregirSaldosClientes extends Command
{
    protected $signature = 'corregir:saldos-clientes
        {--apply : Escribe los cambios (sin esto es corrida en seco)}
        {--confirmar-bd= : Nombre exacto de la BD destino (obligatorio con --apply)}
        {--incluir-bajas : Incluye también a los clientes cuyo saldo bajaría}
        {--cliente=* : Limita a estos ids de cliente (nuevo); se puede repetir}
        {--sin-rutas : No sincroniza las rutas Activos/Cancelados}
        {--revertir= : Archivo de reversa a restaurar (junto con --apply)}';

    protected $description = 'Corrige saldo_actual de clientes migrados desde las facturas abiertas (estado R) del sistema viejo. Corrida en seco por defecto.';

    public function handle(): int
    {
        $bd = DB::connection()->getDatabaseName();
        $host = (string) config('database.connections.' . config('database.default') . '.host');
        $esLocal = in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
        $this->info("Destino: BD '{$bd}' (" . ($esLocal ? 'LOCAL' : 'REMOTA') . ')');

        if ($this->option('revertir')) {
            return $this->revertir((string) $this->option('revertir'), $bd);
        }

        $this->info('Leyendo el sistema viejo...');
        $filas = $this->calcular();

        $candidatos = [];
        $omitidos = ['con movimientos' => 0, 'bajas (falta --incluir-bajas)' => 0, 'quedarían negativos' => 0];
        foreach ($filas as $f) {
            if (abs($f['ajuste']) < 0.005) {
                continue;
            }
            if (abs($f['guardado'] - $f['err']) > 0.005) {
                $omitidos['con movimientos']++;   // su saldo ya no es el de la carga: no se toca
                continue;
            }
            if ($f['propuesto'] < 0) {
                $omitidos['quedarían negativos']++;
                continue;
            }
            if ($f['ajuste'] < 0 && !$this->option('incluir-bajas')) {
                $omitidos['bajas (falta --incluir-bajas)']++;
                continue;
            }
            $candidatos[] = $f;
        }

        usort($candidatos, fn ($a, $b) => abs($b['ajuste']) <=> abs($a['ajuste']));

        $porSuc = [];
        $cruzan = 0;
        foreach ($candidatos as $c) {
            $s = $c['sucursal'];
            $porSuc[$s]['n'] = ($porSuc[$s]['n'] ?? 0) + 1;
            $porSuc[$s]['ajuste'] = ($porSuc[$s]['ajuste'] ?? 0) + $c['ajuste'];
            if (($c['guardado'] > 0) !== ($c['propuesto'] > 0)) {
                $cruzan++;
            }
        }
        ksort($porSuc);

        $this->line('Clientes a corregir:            ' . count($candidatos));
        $this->line('Cruzan el 0 (cambian de ruta):  ' . $cruzan);
        foreach ($omitidos as $motivo => $n) {
            $this->line(str_pad("Omitidos - {$motivo}:", 40) . $n);
        }
        $this->table(
            ['sucursal', 'clientes', 'ajuste total'],
            array_map(fn ($s, $d) => [$s, $d['n'], number_format($d['ajuste'], 2)], array_keys($porSuc), $porSuc)
        );
        $this->table(
            ['id', 'suc', 'cliente', 'guardado', 'propuesto', 'ajuste'],
            array_map(fn ($c) => [$c['id'], $c['sucursal'], mb_substr($c['nombre'], 0, 28),
                number_format($c['guardado'], 2), number_format($c['propuesto'], 2), number_format($c['ajuste'], 2)],
                array_slice($candidatos, 0, 40))
        );

        if (!$this->option('apply')) {
            $this->warn('CORRIDA EN SECO: no se escribió nada. Para aplicar: --apply --confirmar-bd=' . $bd);
            return self::SUCCESS;
        }
        if (!$this->bdConfirmada($bd)) {
            return self::FAILURE;
        }
        if (!$candidatos) {
            $this->info('No hay nada que aplicar.');
            return self::SUCCESS;
        }

        // Archivo de reversa ANTES de escribir
        $rutaReversa = storage_path('app/reversa_saldos_' . now()->format('Ymd_His') . '.csv');
        $fh = @fopen($rutaReversa, 'w');
        if (!$fh) {
            $this->error("No pude crear el archivo de reversa en {$rutaReversa}. No se escribió nada.");
            return self::FAILURE;
        }
        fputcsv($fh, ['cliente_id', 'saldo_anterior', 'saldo_nuevo'], ';');
        foreach ($candidatos as $c) {
            fputcsv($fh, [$c['id'], number_format($c['guardado'], 2, '.', ''), number_format($c['propuesto'], 2, '.', '')], ';');
        }
        fclose($fh);
        $this->info("Reversa guardada en: {$rutaReversa}");

        $aplicados = [];
        $saltados = 0;
        DB::transaction(function () use ($candidatos, &$aplicados, &$saltados) {
            foreach ($candidatos as $c) {
                $n = DB::table('clientes')
                    ->where('id', $c['id'])
                    ->whereRaw('saldo_actual = ?', [number_format($c['guardado'], 2, '.', '')])
                    ->update(['saldo_actual' => number_format($c['propuesto'], 2, '.', ''), 'updated_at' => now()]);
                if ($n) {
                    $aplicados[] = $c;
                } else {
                    $saltados++; // el saldo cambió mientras corría: no se toca
                }
            }
        });

        $this->info('Saldos corregidos: ' . count($aplicados) . ($saltados ? " (saltados porque cambiaron: {$saltados})" : ''));

        if (!$this->option('sin-rutas')) {
            $cambios = array_map(fn ($c) => ['id' => $c['id'], 'antes' => $c['guardado'], 'despues' => $c['propuesto']], $aplicados);
            [$ok, $err] = $this->sincronizarRutas($cambios);
            $this->info("Rutas sincronizadas: {$ok}" . ($err ? " (con error: {$err}, revisar el log)" : ''));
        }

        return self::SUCCESS;
    }

    /** Calcula, para cada cliente migrado, qué debería tener como saldo según las facturas R del viejo. */
    private function calcular(): array
    {
        $ids = array_filter((array) $this->option('cliente'));
        $clientes = DB::table('clientes')
            ->whereNotNull('cliente_id_legacy')
            ->when($ids, fn ($q) => $q->whereIn('id', $ids))
            ->get(['id', 'nombre', 'sucursal_id', 'cliente_id_legacy', 'saldo_actual']);

        $legacy = DB::connection('legacy');
        $correcto = []; // clienteid legacy => suma de saldos de facturas R
        $erroneo = [];  // clienteid legacy => saldofinal del último abono (lo que dejó la migración)

        foreach (array_chunk($clientes->pluck('cliente_id_legacy')->all(), 300) as $lote) {
            $ultimoPorCliente = [];
            $ultimoPorFactura = [];
            $abonos = $legacy->table('abono')
                ->whereIn('clienteid', $lote)
                ->orderBy('id')
                ->select('id', 'clienteid', 'facturaid', 'saldofinal')
                ->cursor();
            foreach ($abonos as $a) {
                $ultimoPorCliente[$a->clienteid] = (float) $a->saldofinal;
                $ultimoPorFactura[$a->facturaid] = (float) $a->saldofinal;
            }
            foreach ($ultimoPorCliente as $cid => $saldo) {
                $erroneo[$cid] = $saldo;
            }

            $facturas = $legacy->table('factura')
                ->whereIn('clienteid', $lote)
                ->where('estado', 'R')
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
            }
        }

        $filas = [];
        foreach ($clientes as $c) {
            $cid = $c->cliente_id_legacy;
            $guardado = round((float) $c->saldo_actual, 2);
            $err = round($erroneo[$cid] ?? 0, 2);
            $ok = round($correcto[$cid] ?? 0, 2);
            $filas[] = [
                'id' => $c->id,
                'sucursal' => $c->sucursal_id,
                'nombre' => (string) $c->nombre,
                'guardado' => $guardado,
                'err' => $err,
                'ajuste' => round($ok - $err, 2),
                'propuesto' => round($guardado + $ok - $err, 2),
            ];
        }

        return $filas;
    }

    private function bdConfirmada(string $bd): bool
    {
        if ($this->option('confirmar-bd') !== $bd) {
            $this->error("Para escribir hay que confirmar la BD destino: --confirmar-bd={$bd}");
            return false;
        }
        return true;
    }

    /** @param array<int, array{id:int, antes:float, despues:float}> $cambios */
    private function sincronizarRutas(array $cambios): array
    {
        $servicio = app(RutaSaldoService::class);
        $ok = 0;
        $err = 0;
        foreach ($cambios as $c) {
            if (($c['antes'] > 0) === ($c['despues'] > 0)) {
                continue; // no cruzó el 0: su ruta sigue siendo la correcta
            }
            try {
                $servicio->sincronizar((int) $c['id']);
                $ok++;
            } catch (\Throwable $e) {
                report($e);
                $err++;
            }
        }
        return [$ok, $err];
    }

    private function revertir(string $ruta, string $bd): int
    {
        if (!is_file($ruta)) {
            $this->error("No existe el archivo: {$ruta}");
            return self::FAILURE;
        }

        $filas = [];
        $fh = fopen($ruta, 'r');
        fgetcsv($fh, 0, ';'); // encabezado
        while (($r = fgetcsv($fh, 0, ';')) !== false) {
            if (count($r) >= 3) {
                $filas[] = ['id' => (int) $r[0], 'antes' => $r[1], 'nuevo' => $r[2]];
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

        $restaurados = [];
        $saltados = 0;
        DB::transaction(function () use ($filas, &$restaurados, &$saltados) {
            foreach ($filas as $f) {
                $n = DB::table('clientes')
                    ->where('id', $f['id'])
                    ->whereRaw('saldo_actual = ?', [$f['nuevo']])
                    ->update(['saldo_actual' => $f['antes'], 'updated_at' => now()]);
                if ($n) {
                    $restaurados[] = ['id' => $f['id'], 'antes' => (float) $f['nuevo'], 'despues' => (float) $f['antes']];
                } else {
                    $saltados++; // ese cliente ya tuvo movimientos: no se pisa
                }
            }
        });

        $this->info('Restaurados: ' . count($restaurados) . ($saltados ? " (saltados porque ya cambiaron: {$saltados})" : ''));

        if (!$this->option('sin-rutas')) {
            [$ok, $err] = $this->sincronizarRutas($restaurados);
            $this->info("Rutas sincronizadas: {$ok}" . ($err ? " (con error: {$err})" : ''));
        }

        return self::SUCCESS;
    }
}