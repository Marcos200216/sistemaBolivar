<?php
// app/Console/Commands/MoverSucursalCompleta.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MoverSucursalCompleta extends Command
{
    protected $signature = 'mover:sucursal-completa
        {--de= : id de la sucursal de origen}
        {--a= : id de la sucursal de destino}
        {--revertir= : lote a revertir (ver tabla respaldo_mover_sucursal)}
        {--apply : aplicar los cambios (sin esto es corrida en seco)}
        {--confirmar-bd= : nombre real de la BD, obligatorio con --apply}';

    protected $description = 'Mueve todos los clientes de una sucursal a otra con sus facturas, operaciones y demas cuentas';

    private const TABLA_RESPALDO = 'respaldo_mover_sucursal';

    public function handle(): int
    {
        $bd = DB::connection()->getDatabaseName();
        $host = (string) config('database.connections.' . config('database.default') . '.host');
        $local = in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
        $apply = (bool) $this->option('apply');

        $this->line("Destino: BD '{$bd}' (" . ($local ? 'LOCAL' : 'REMOTA') . ')');
        $this->line($apply ? 'MODO: APLICAR' : 'MODO: CORRIDA EN SECO (no se escribe nada)');

        if ($apply && $this->option('confirmar-bd') !== $bd) {
            $this->error("Con --apply hay que pasar --confirmar-bd={$bd}");
            return 1;
        }

        if ($this->option('revertir')) {
            return $this->revertir((string) $this->option('revertir'), $apply);
        }

        return $this->mover((int) $this->option('de'), (int) $this->option('a'), $apply);
    }

    // ------------------------------------------------------------------
    // MOVER
    // ------------------------------------------------------------------
    private function mover(int $de, int $a, bool $apply): int
    {
        if (!$de || !$a || $de === $a) {
            $this->error('Indica --de=ID y --a=ID distintos.');
            return 1;
        }

        $suc = DB::table('sucursales')->whereIn('id', [$de, $a])->pluck('nombre', 'id');
        if ($suc->count() !== 2) {
            $this->error('Alguna de las sucursales no existe.');
            return 1;
        }
        $this->line("Origen: {$suc[$de]} (id {$de})  ->  Destino: {$suc[$a]} (id {$a})");

        $clientes = DB::table('clientes')->where('sucursal_id', $de)->orderBy('id')->get(['id', 'codigo', 'nombre', 'zona_id']);
        if ($clientes->isEmpty()) {
            $this->error('La sucursal de origen no tiene clientes.');
            return 1;
        }
        $ids = $clientes->pluck('id')->all();

        // --- Cruces: si hay algo cruzado con clientes que no se mueven, se frena
        $cruzTras = DB::table('operaciones')
            ->where(function ($q) use ($ids) {
                $q->whereIn('cliente_id', $ids)->whereNotNull('traspaso_cliente_id')->whereNotIn('traspaso_cliente_id', $ids);
            })
            ->orWhere(function ($q) use ($ids) {
                $q->whereNotIn('cliente_id', $ids)->whereIn('traspaso_cliente_id', $ids);
            })->count();

        $cruz = ['traspasos' => $cruzTras];
        foreach (['abonos', 'devoluciones'] as $t) {
            $cruz[$t] = DB::table("{$t} as x")
                ->join('facturas as f', 'f.id', '=', 'x.factura_id')
                ->whereColumn('x.cliente_id', '<>', 'f.cliente_id')
                ->where(function ($q) use ($ids) {
                    $q->whereIn('x.cliente_id', $ids)->orWhereIn('f.cliente_id', $ids);
                })->count();
        }
        $this->line('Cruces con clientes que no se mueven: ' . json_encode($cruz));
        if (array_sum($cruz) > 0) {
            $this->error('Hay registros cruzados. No se mueve nada.');
            return 1;
        }

        // --- Codigos: si chocan con Azur destino se agrega sufijo -2, -3...
        $usados = [];
        foreach (DB::table('clientes')->where('sucursal_id', $a)->pluck('codigo') as $c) {
            $usados[mb_strtolower(trim((string) $c))] = true;
        }
        $nuevoCodigo = [];
        $cambios = [];
        foreach ($clientes as $c) {
            $cod = (string) $c->codigo;
            $k = mb_strtolower(trim($cod));
            if ($k !== '' && isset($usados[$k])) {
                $i = 2;
                do {
                    $cand = $cod . '-' . $i;
                    $i++;
                } while (isset($usados[mb_strtolower($cand)]));
                $nuevoCodigo[$c->id] = $cand;
                $usados[mb_strtolower($cand)] = true;
                $cambios[] = [$c->id, $c->nombre, $cod, $cand];
            } else {
                $nuevoCodigo[$c->id] = $cod;
                if ($k !== '') {
                    $usados[$k] = true;
                }
            }
        }

        // --- Lo que se va a mover
        $ops = DB::table('operaciones')->whereIn('cliente_id', $ids)->where('sucursal_id', '<>', $a)
            ->orderBy('numero')->orderBy('id')->get(['id', 'sucursal_id', 'numero', 'ruta_cliente_id']);
        $maxDestino = (int) DB::table('operaciones')->where('sucursal_id', $a)->max('numero');

        $rutasOrigen = DB::table('rutas')->where('sucursal_id', $de)->pluck('id')->all();
        $rutaClientes = DB::table('ruta_clientes')->whereIn('cliente_id', $ids)->whereIn('ruta_id', $rutasOrigen)->get();

        $resumen = [
            'clientes' => count($ids),
            'facturas' => DB::table('facturas')->whereIn('cliente_id', $ids)->where('sucursal_id', '<>', $a)->count(),
            'operaciones' => $ops->count(),
            'apartados' => DB::table('apartados')->whereIn('cliente_id', $ids)->where('sucursal_id', '<>', $a)->count(),
            'recibo_envios' => DB::table('recibo_envios')->whereIn('cliente_id', $ids)->where('sucursal_id', '<>', $a)->count(),
            'ajustes_saldo' => DB::table('ajustes_saldo')->whereIn('cliente_id', $ids)->where('sucursal_id', '<>', $a)->count(),
            'ruta_clientes (se borran)' => $rutaClientes->count(),
        ];
        $this->table(['Se mueve', 'Cantidad'], collect($resumen)->map(fn($v, $k) => [$k, $v])->values()->all());

        if ($ops->count() > 0) {
            $this->line('Operaciones: numero ' . ($maxDestino + 1) . ' a ' . ($maxDestino + $ops->count()) . ' en el mismo orden que tenian.');
        }

        $this->line('Cambios de codigo (' . count($cambios) . '):');
        foreach ($cambios as $c) {
            $this->line("  cliente {$c[0]} {$c[1]}: {$c[2]} -> {$c[3]}");
        }

        $facOtra = DB::table('facturas')->whereIn('cliente_id', $ids)->where('sucursal_id', '<>', $de)
            ->get(['id', 'sucursal_id', 'cliente_id', 'operacion_id', 'estado', 'total', 'saldo_migrado', 'fecha']);
        $this->line('Facturas de estos clientes que hoy NO estan en origen (' . $facOtra->count() . '):');
        foreach ($facOtra as $f) {
            $this->line('  ' . json_encode($f));
        }

        $this->line('No se tocan: reservas (' . DB::table('reservas')->whereIn('cliente_id', $ids)->count()
            . '), recibos_consolidados (' . DB::table('recibos_consolidados')->whereIn('cliente_id', $ids)->count()
            . '), reportes_ruta, gastos, compras, usuarios, rutas.');
        $this->line('Clientes con zona_id asignada (la zona sigue siendo la de origen): '
            . $clientes->whereNotNull('zona_id')->count());

        if (!$apply) {
            $this->info('Corrida en seco terminada. No se escribio nada.');
            return 0;
        }

        // --- Aplicar
        $lote = now()->format('Ymd_His') . "_{$de}a{$a}";
        $this->crearTablaRespaldo();
        $antes = $this->snapshot($ids);

        try {
            DB::transaction(function () use ($lote, $de, $a, $clientes, $ids, $nuevoCodigo, $ops, $maxDestino, $rutaClientes, $antes) {
                // 1) clientes
                $resp = [];
                foreach ($clientes as $c) {
                    $resp[$c->id] = ['sucursal_id' => $de, 'codigo' => $c->codigo];
                }
                $this->respaldar($lote, 'clientes', $resp);
                foreach ($clientes as $c) {
                    $n = DB::table('clientes')->where('id', $c->id)->where('sucursal_id', $de)
                        ->update(['sucursal_id' => $a, 'codigo' => $nuevoCodigo[$c->id]]);
                    $this->exigirUno($n, 'clientes', $c->id);
                }

                // 2) operaciones (con renumeracion)
                $resp = [];
                foreach ($ops as $o) {
                    $resp[$o->id] = ['sucursal_id' => $o->sucursal_id, 'numero' => $o->numero, 'ruta_cliente_id' => $o->ruta_cliente_id];
                }
                $this->respaldar($lote, 'operaciones', $resp);
                $max = $maxDestino;
                foreach ($ops as $o) {
                    $max++;
                    $n = DB::table('operaciones')->where('id', $o->id)->where('sucursal_id', $o->sucursal_id)
                        ->update(['sucursal_id' => $a, 'numero' => $max]);
                    $this->exigirUno($n, 'operaciones', $o->id);
                }

                // 3) tablas que solo cambian de sucursal
                foreach (['facturas', 'apartados', 'recibo_envios', 'ajustes_saldo'] as $t) {
                    $filas = DB::table($t)->whereIn('cliente_id', $ids)->where('sucursal_id', '<>', $a)->get(['id', 'sucursal_id']);
                    $resp = [];
                    foreach ($filas as $f) {
                        $resp[$f->id] = ['sucursal_id' => $f->sucursal_id];
                    }
                    $this->respaldar($lote, $t, $resp);
                    foreach ($filas->pluck('id')->chunk(500) as $chunk) {
                        $n = DB::table($t)->whereIn('id', $chunk->all())->update(['sucursal_id' => $a]);
                        if ($n !== $chunk->count()) {
                            throw new \RuntimeException("{$t}: se esperaban {$chunk->count()} filas y se actualizaron {$n}");
                        }
                    }
                }

                // 4) ruta_clientes de las rutas de origen (igual que hace hoy el cambio manual de sucursal)
                $resp = [];
                foreach ($rutaClientes as $r) {
                    $resp[$r->id] = (array) $r;
                }
                $this->respaldar($lote, 'ruta_clientes', $resp);
                foreach ($rutaClientes->pluck('id')->chunk(500) as $chunk) {
                    DB::table('ruta_clientes')->whereIn('id', $chunk->all())->delete();
                }

                // 5) verificacion
                $despues = $this->snapshot($ids);
                $dif = [];
                foreach ($antes as $id => $v) {
                    if (($despues[$id] ?? null) !== $v) {
                        $dif[] = "cliente {$id}: antes=" . json_encode($v) . ' despues=' . json_encode($despues[$id] ?? null);
                    }
                }
                if ($dif) {
                    throw new \RuntimeException("Saldos o conteos distintos en " . count($dif) . " cliente(s):\n" . implode("\n", array_slice($dif, 0, 10)));
                }

                $malos = [
                    'clientes fuera de destino' => DB::table('clientes')->whereIn('id', $ids)->where('sucursal_id', '<>', $a)->count(),
                    'facturas fuera de destino' => DB::table('facturas')->whereIn('cliente_id', $ids)->where('sucursal_id', '<>', $a)->count(),
                    'operaciones fuera de destino' => DB::table('operaciones')->whereIn('cliente_id', $ids)->where('sucursal_id', '<>', $a)->count(),
                    'apartados fuera de destino' => DB::table('apartados')->whereIn('cliente_id', $ids)->where('sucursal_id', '<>', $a)->count(),
                    'codigos repetidos en destino' => DB::table('clientes')->where('sucursal_id', $a)
                        ->whereNotNull('codigo')->where('codigo', '<>', '')
                        ->select('codigo')->groupBy('codigo')->havingRaw('count(*) > 1')->get()->count(),
                    'numeros de operacion repetidos en destino' => DB::table('operaciones')->where('sucursal_id', $a)
                        ->select('numero')->groupBy('numero')->havingRaw('count(*) > 1')->get()->count(),
                ];
                foreach ($malos as $k => $v) {
                    if ($v > 0) {
                        throw new \RuntimeException("Verificacion fallo: {$k} = {$v}");
                    }
                }
            });
        } catch (\Throwable $e) {
            $this->error('SE REVIRTIO TODO (nada quedo cambiado): ' . $e->getMessage());
            return 1;
        }

        $this->info("Listo. Lote de respaldo: {$lote}");
        $this->line("Para revertir: php artisan mover:sucursal-completa --revertir={$lote} (corrida en seco), luego con --apply --confirmar-bd=...");
        return 0;
    }

    // ------------------------------------------------------------------
    // REVERTIR
    // ------------------------------------------------------------------
    private function revertir(string $lote, bool $apply): int
    {
        if (!Schema::hasTable(self::TABLA_RESPALDO)) {
            $this->error('No existe la tabla de respaldo.');
            return 1;
        }
        $filas = DB::table(self::TABLA_RESPALDO)->where('lote', $lote)->get();
        if ($filas->isEmpty()) {
            $this->error("No hay respaldo para el lote {$lote}.");
            return 1;
        }
        if ($filas->whereNotNull('revertido_at')->isNotEmpty()) {
            $this->error('Ese lote ya fue revertido.');
            return 1;
        }

        $porTabla = $filas->groupBy('tabla')->map->count();
        $this->table(['Tabla', 'Filas a restaurar'], $porTabla->map(fn($v, $k) => [$k, $v])->values()->all());
        $this->line('Ojo: lo creado despues de la migracion (facturas, abonos, operaciones nuevas) no se mueve de vuelta.');

        if (!$apply) {
            $this->info('Corrida en seco terminada. No se escribio nada.');
            return 0;
        }

        try {
            DB::transaction(function () use ($filas, $lote) {
                // ruta_clientes primero, para que ruta_cliente_id de operaciones pueda volver a apuntar
                foreach ($filas->where('tabla', 'ruta_clientes') as $f) {
                    DB::table('ruta_clientes')->insert(json_decode($f->datos, true));
                }
                foreach (['operaciones', 'facturas', 'apartados', 'recibo_envios', 'ajustes_saldo', 'clientes'] as $t) {
                    foreach ($filas->where('tabla', $t) as $f) {
                        $n = DB::table($t)->where('id', $f->registro_id)->update(json_decode($f->datos, true));
                        $this->exigirUno($n, $t, $f->registro_id);
                    }
                }
                DB::table(self::TABLA_RESPALDO)->where('lote', $lote)->update(['revertido_at' => now()]);
            });
        } catch (\Throwable $e) {
            $this->error('SE REVIRTIO LA REVERSA (nada quedo cambiado): ' . $e->getMessage());
            return 1;
        }

        $this->info("Lote {$lote} revertido.");
        return 0;
    }

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------
    private function crearTablaRespaldo(): void
    {
        if (Schema::hasTable(self::TABLA_RESPALDO)) {
            return;
        }
        Schema::create(self::TABLA_RESPALDO, function (Blueprint $t) {
            $t->id();
            $t->string('lote', 60)->index();
            $t->string('tabla', 60);
            $t->unsignedBigInteger('registro_id');
            $t->longText('datos');
            $t->timestamp('revertido_at')->nullable();
            $t->timestamp('created_at')->nullable();
        });
    }

    private function respaldar(string $lote, string $tabla, array $filas): void
    {
        $rows = [];
        foreach ($filas as $id => $datos) {
            $rows[] = [
                'lote' => $lote,
                'tabla' => $tabla,
                'registro_id' => $id,
                'datos' => json_encode($datos),
                'created_at' => now(),
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table(self::TABLA_RESPALDO)->insert($chunk);
        }
    }

    private function exigirUno(int $n, string $tabla, $id): void
    {
        if ($n !== 1) {
            throw new \RuntimeException("{$tabla} id {$id}: se esperaba 1 fila actualizada y fueron {$n}");
        }
    }

    private function n($v): string
    {
        return number_format((float) $v, 2, '.', '');
    }

    /** Saldo, conteos y sumas por cliente (sin Global Scopes). */
    private function snapshot(array $ids): array
    {
        $snap = [];
        foreach (DB::table('clientes')->whereIn('id', $ids)->get(['id', 'saldo_actual']) as $c) {
            $snap[$c->id] = [
                'saldo' => $this->n($c->saldo_actual),
                'fact_n' => 0, 'fact_total' => '0.00', 'fact_migrado' => '0.00',
                'abonos_n' => 0, 'abonos_sum' => '0.00',
                'dev_n' => 0, 'dev_sum' => '0.00',
                'ops_n' => 0, 'ops_saldo_ini' => '0.00', 'ops_saldo_fin' => '0.00',
            ];
        }

        foreach (DB::table('facturas')->whereIn('cliente_id', $ids)->groupBy('cliente_id')
            ->selectRaw('cliente_id, count(*) n, coalesce(sum(total),0) t, coalesce(sum(saldo_migrado),0) m')->get() as $r) {
            if (isset($snap[$r->cliente_id])) {
                $snap[$r->cliente_id]['fact_n'] = (int) $r->n;
                $snap[$r->cliente_id]['fact_total'] = $this->n($r->t);
                $snap[$r->cliente_id]['fact_migrado'] = $this->n($r->m);
            }
        }
        foreach (DB::table('abonos')->whereIn('cliente_id', $ids)->groupBy('cliente_id')
            ->selectRaw('cliente_id, count(*) n, coalesce(sum(monto_abono),0) s')->get() as $r) {
            if (isset($snap[$r->cliente_id])) {
                $snap[$r->cliente_id]['abonos_n'] = (int) $r->n;
                $snap[$r->cliente_id]['abonos_sum'] = $this->n($r->s);
            }
        }
        foreach (DB::table('devoluciones')->whereIn('cliente_id', $ids)->groupBy('cliente_id')
            ->selectRaw('cliente_id, count(*) n, coalesce(sum(total),0) s')->get() as $r) {
            if (isset($snap[$r->cliente_id])) {
                $snap[$r->cliente_id]['dev_n'] = (int) $r->n;
                $snap[$r->cliente_id]['dev_sum'] = $this->n($r->s);
            }
        }
        foreach (DB::table('operaciones')->whereIn('cliente_id', $ids)->groupBy('cliente_id')
            ->selectRaw('cliente_id, count(*) n, coalesce(sum(saldo_inicial),0) si, coalesce(sum(saldo_final),0) sf')->get() as $r) {
            if (isset($snap[$r->cliente_id])) {
                $snap[$r->cliente_id]['ops_n'] = (int) $r->n;
                $snap[$r->cliente_id]['ops_saldo_ini'] = $this->n($r->si);
                $snap[$r->cliente_id]['ops_saldo_fin'] = $this->n($r->sf);
            }
        }

        ksort($snap);
        return $snap;
    }
}