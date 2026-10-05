<?php

namespace App\Console\Commands;

use App\Models\Apartado;
use App\Models\Operacion;
use App\Models\Ruta;
use App\Models\RutaCliente;
use App\Models\Sucursal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MoverClientesSucursal extends Command
{
    protected $signature = 'mover:clientes-sucursal
        {--mover= : Pares cliente_id_legacy:sucursal_id separados por coma, ej. 4404:2,4409:2}
        {--apply : Escribe en la BD (sin esto es corrida en seco)}
        {--confirmar-bd= : Nombre de la BD de destino, obligatorio con --apply o --revertir}
        {--revertir= : Archivo de reversa a deshacer}';

    protected $description = 'Mueve clientes migrados a otra sucursal junto con sus facturas. Corrida en seco por defecto.';

    public function handle(): int
    {
        $bd = DB::connection()->getDatabaseName();
        $host = (string) config('database.connections.' . config('database.default') . '.host');
        $this->line("Destino: BD '{$bd}' (" . (in_array($host, ['127.0.0.1', 'localhost'], true) ? 'LOCAL' : 'REMOTA') . ')');

        if ($archivo = $this->option('revertir')) {
            return $this->revertir($archivo, $bd);
        }

        $pares = [];
        foreach (array_filter(explode(',', (string) $this->option('mover'))) as $par) {
            [$leg, $suc] = array_pad(explode(':', trim($par)), 2, null);
            if (!ctype_digit((string) $leg) || !ctype_digit((string) $suc)) {
                $this->error("Par inválido '{$par}'. Formato: cliente_legacy:sucursal");
                return self::FAILURE;
            }
            $pares[(int) $leg] = (int) $suc;
        }
        if (!$pares) {
            $this->error('Indicá --mover=4404:2,4409:2');
            return self::FAILURE;
        }

        $sucursales = Sucursal::pluck('nombre', 'id');
        $plan = [];
        $hayProblema = false;

        foreach ($pares as $leg => $destino) {
            $c = DB::table('clientes')->where('cliente_id_legacy', $leg)->first();
            $problema = '';
            $ops = $aps = $nFact = $nRutas = 0;
            $codigoNuevo = null;

            if (!$c) {
                $problema = 'no existe en esta BD';
            } elseif (!isset($sucursales[$destino])) {
                $problema = 'la sucursal destino no existe';
            } elseif ((int) $c->sucursal_id === $destino) {
                $problema = 'ya está en esa sucursal';
            } else {
                $ops = Operacion::withoutGlobalScopes()->where('cliente_id', $c->id)->count();
                $aps = Apartado::withoutGlobalScopes()->where('cliente_id', $c->id)->count();
                $nFact = DB::table('facturas')->where('cliente_id', $c->id)->count();
                $nRutas = $this->filasRuta($c)->count();
                $codigoNuevo = $this->codigoLibre($c->codigo, $destino);
                if ($ops > 0) {
                    $problema = "tiene {$ops} operaciones nuevas";
                } elseif ($aps > 0) {
                    $problema = "tiene {$aps} apartados";
                }
            }
            $hayProblema = $hayProblema || $problema !== '';

            $plan[$leg] = compact('c', 'destino', 'nFact', 'nRutas', 'codigoNuevo', 'problema');
        }

        $this->table(
            ['legacy', 'nombre', 'saldo', 'de', 'a', 'facturas', 'filas de ruta', 'código', 'problema'],
            collect($plan)->map(fn ($p, $leg) => [
                $leg,
                $p['c'] ? mb_substr((string) $p['c']->nombre, 0, 26) : '—',
                $p['c'] ? number_format((float) $p['c']->saldo_actual, 2) : '—',
                $p['c'] ? ($sucursales[$p['c']->sucursal_id] ?? $p['c']->sucursal_id) : '—',
                $sucursales[$p['destino']] ?? $p['destino'],
                $p['nFact'], $p['nRutas'],
                $p['c'] ? (($p['c']->codigo ?? '—') . ' → ' . ($p['codigoNuevo'] ?? '—')) : '—',
                $p['problema'],
            ])->values()->all()
        );

        if ($hayProblema) {
            $this->error('Hay clientes con problema: no se aplica nada hasta resolverlo.');
            return self::FAILURE;
        }
        if (!$this->option('apply')) {
            $this->warn("CORRIDA EN SECO: no se escribió nada. Para aplicar: --apply --confirmar-bd={$bd}");
            return self::SUCCESS;
        }
        if ($this->option('confirmar-bd') !== $bd) {
            $this->error("Con --apply hay que pasar --confirmar-bd={$bd}.");
            return self::FAILURE;
        }

        $reversa = [];
        DB::transaction(function () use ($plan, &$reversa) {
            foreach ($plan as $p) {
                $c = $p['c'];
                $facIds = DB::table('facturas')->where('cliente_id', $c->id)->pluck('id')->all();
                $reversa[] = [
                    'cliente_id' => $c->id,
                    'sucursal_anterior' => (int) $c->sucursal_id,
                    'codigo_anterior' => $c->codigo,
                    'facturas' => $facIds,
                ];

                DB::table('clientes')->where('id', $c->id)->update([
                    'sucursal_id' => $p['destino'],
                    'codigo' => $p['codigoNuevo'],
                    'updated_at' => now(),
                ]);
                DB::table('facturas')->where('cliente_id', $c->id)->update(['sucursal_id' => $p['destino']]);
                $this->filasRuta($c)->delete();
            }
        });

        $archivo = storage_path('app/reversa_mover_' . date('Ymd_His') . '.json');
        file_put_contents($archivo, json_encode(['bd' => $bd, 'items' => $reversa]));
        $this->info("Aplicado. Reversa: {$archivo} (no restaura las filas de ruta borradas)");

        $filas = [];
        foreach ($plan as $leg => $p) {
            $nuevo = DB::table('clientes')->where('id', $p['c']->id)->first();
            $fuera = DB::table('facturas')->where('cliente_id', $nuevo->id)->where('sucursal_id', '<>', $p['destino'])->count();
            $suma = (float) DB::table('facturas')->where('cliente_id', $nuevo->id)->sum('saldo_migrado');
            $filas[] = [
                $leg, $nuevo->nombre, $nuevo->sucursal_id, $nuevo->saldo_actual, $suma, $fuera,
                ((int) $nuevo->sucursal_id === $p['destino'] && $fuera === 0 && abs((float) $nuevo->saldo_actual - $suma) < 0.005) ? 'OK' : 'REVISAR',
            ];
        }
        $this->table(['legacy', 'nombre', 'sucursal', 'saldo cliente', 'suma saldo_migrado', 'facturas en otra sucursal', ''], $filas);

        return self::SUCCESS;
    }

    /** Filas de ruta del cliente en las rutas de su sucursal actual (igual que ClienteController::update). */
    private function filasRuta(object $c)
    {
        return RutaCliente::where('cliente_id', $c->id)
            ->whereIn('ruta_id', Ruta::withoutGlobalScopes()->where('sucursal_id', $c->sucursal_id)->pluck('id'));
    }

    /** Conserva el código si está libre en la sucursal destino; si no, le agrega -2, -3... */
    private function codigoLibre(?string $codigo, int $sucursalId): ?string
    {
        if ($codigo === null || $codigo === '') {
            return null;
        }
        $nuevo = $codigo;
        $n = 2;
        while (DB::table('clientes')->where('sucursal_id', $sucursalId)->where('codigo', $nuevo)->exists()) {
            $nuevo = $codigo . '-' . $n++;
        }

        return $nuevo;
    }

    private function revertir(string $archivo, string $bd): int
    {
        if ($this->option('confirmar-bd') !== $bd) {
            $this->error("Para revertir pasá --confirmar-bd={$bd}.");
            return self::FAILURE;
        }
        $datos = json_decode((string) @file_get_contents($archivo), true);
        if (!is_array($datos) || empty($datos['items']) || ($datos['bd'] ?? null) !== $bd) {
            $this->error('Archivo de reversa inválido o de otra BD.');
            return self::FAILURE;
        }
        foreach ($datos['items'] as $i) {
            if (Operacion::withoutGlobalScopes()->where('cliente_id', $i['cliente_id'])->exists()) {
                $this->error("El cliente {$i['cliente_id']} ya tiene operaciones nuevas: no se revierte.");
                return self::FAILURE;
            }
        }
        DB::transaction(function () use ($datos) {
            foreach ($datos['items'] as $i) {
                DB::table('clientes')->where('id', $i['cliente_id'])->update([
                    'sucursal_id' => $i['sucursal_anterior'], 'codigo' => $i['codigo_anterior'],
                ]);
                DB::table('facturas')->whereIn('id', $i['facturas'])->update(['sucursal_id' => $i['sucursal_anterior']]);
            }
        });
        $this->info('Revertido: ' . count($datos['items']) . ' clientes.');

        return self::SUCCESS;
    }
}