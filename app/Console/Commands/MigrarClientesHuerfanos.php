<?php

namespace App\Console\Commands;

use App\Enums\EstadoFactura;
use App\Enums\MotivoDevolucion;
use App\Models\Cliente;
use App\Models\Devolucion;
use App\Models\DevolucionLinea;
use App\Models\Sucursal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrarClientesHuerfanos extends Command
{
    protected $signature = 'migrar:clientes-huerfanos
        {--apply : Escribe en la BD (sin esto es corrida en seco)}
        {--confirmar-bd= : Nombre de la BD de destino, obligatorio con --apply o --revertir}
        {--revertir= : Archivo de reversa a deshacer}';

    protected $description = 'Carga clientes con crédito abierto cuya ficha no se migró, con todo su historial, en la sucursal de su deuda. Corrida en seco por defecto.';

    private const TIENDAS = [5, 10, 11, 12, 13];

    /** 1 = cliente genérico "Contado"; 1861 = José Luis Bolívar. Decisión del usuario: no se cargan. */
    private const EXCLUIR = [1, 1861];

    private array $codigosUsados = [];

    public function handle(): int
    {
        $bd = DB::connection()->getDatabaseName();
        $host = (string) config('database.connections.' . config('database.default') . '.host');
        $this->line("Destino: BD '{$bd}' (" . (in_array($host, ['127.0.0.1', 'localhost'], true) ? 'LOCAL' : 'REMOTA') . ')');

        if ($archivo = $this->option('revertir')) {
            return $this->revertir($archivo, $bd);
        }

        $leg = DB::connection('legacy');
        $sucursalPorTienda = Sucursal::pluck('id', 'tiendaid_legacy')->mapWithKeys(fn ($id, $t) => [(int) $t => (int) $id]);

        $existentes = DB::table('clientes')->whereNotNull('cliente_id_legacy')
            ->pluck('cliente_id_legacy')->map(fn ($i) => (int) $i)->flip();

        $candidatos = $leg->table('factura')->where('estado', 'R')->whereIn('tiendaid', self::TIENDAS)
            ->pluck('clienteid')->map(fn ($i) => (int) $i)->unique()
            ->reject(fn ($i) => isset($existentes[$i]) || in_array($i, self::EXCLUIR, true))
            ->values()->all();

        if (!$candidatos) {
            $this->info('No hay clientes para cargar.');
            return self::SUCCESS;
        }

        $fichas = $leg->table('cliente')->whereIn('id', $candidatos)->get()->keyBy('id');
        $facturas = $leg->table('factura')->whereIn('clienteid', $candidatos)->orderBy('id')->get();

        $pies = [];
        foreach (array_chunk($facturas->pluck('id')->all(), 1000) as $ids) {
            foreach ($leg->table('pie')->whereIn('facturaid', $ids)->get() as $p) {
                $pies[$p->facturaid] ??= $p;
            }
        }
        $saldos = $this->saldos($leg, $facturas->where('estado', 'R')->pluck('id')->all(), $pies);

        $plan = [];
        foreach ($candidatos as $cid) {
            $mias = $facturas->where('clienteid', $cid);
            $abiertas = $mias->where('estado', 'R');
            $porTienda = $abiertas->whereIn('tiendaid', self::TIENDAS)->groupBy('tiendaid')
                ->map(fn ($g) => $g->sum(fn ($f) => $saldos[$f->id]));
            $tienda = (int) $porTienda->sortDesc()->keys()->first();
            $sucursalId = $sucursalPorTienda[$tienda] ?? null;
            if (!$sucursalId) {
                $this->error("Cliente viejo {$cid}: no encuentro sucursal para la tienda vieja {$tienda}.");
                return self::FAILURE;
            }
            $ficha = $fichas->get($cid);
            $plan[$cid] = [
                'cid' => $cid,
                'ficha' => $ficha,
                'nombre' => $ficha ? trim((string) $ficha->nombre) : 'SIN FICHA ' . $cid,
                'tienda' => $tienda,
                'sucursal_id' => $sucursalId,
                'saldo' => round($abiertas->sum(fn ($f) => $saldos[$f->id]), 2),
                'n_fact' => $mias->count(),
                'n_abiertas' => $abiertas->count(),
                'multi' => $porTienda->count() > 1,
            ];
        }

        $this->table(
            ['cliente viejo', 'nombre', 'sucursal', 'tienda vieja', 'facturas', 'abiertas', 'saldo', 'aviso'],
            collect($plan)->sortBy('sucursal_id')->map(fn ($p) => [
                $p['cid'], mb_substr($p['nombre'], 0, 30), $p['sucursal_id'], $p['tienda'],
                $p['n_fact'], $p['n_abiertas'], number_format($p['saldo'], 2),
                $p['multi'] ? 'deuda en varias tiendas' : '',
            ])->values()->all()
        );
        foreach (collect($plan)->groupBy('sucursal_id') as $sid => $g) {
            $this->line("Sucursal {$sid}: {$g->count()} clientes, saldo ₡" . number_format($g->sum('saldo'), 2));
        }
        $this->line('TOTAL: ' . count($plan) . ' clientes, saldo ₡' . number_format(collect($plan)->sum('saldo'), 2));
        $this->line('Se cargan además: ' . $facturas->count() . ' facturas, '
            . $leg->table('abono')->whereIn('clienteid', $candidatos)->count() . ' abonos, '
            . $leg->table('consolidado')->whereIn('clienteid', $candidatos)->count() . ' consolidados.');
        $this->line('No se cargan por decisión (clientes viejos): ' . implode(', ', self::EXCLUIR));

        if (!$this->option('apply')) {
            $this->warn("CORRIDA EN SECO: no se escribió nada. Para aplicar: --apply --confirmar-bd={$bd}");
            return self::SUCCESS;
        }
        if ($this->option('confirmar-bd') !== $bd) {
            $this->error("Con --apply hay que pasar --confirmar-bd={$bd}.");
            return self::FAILURE;
        }

        DB::transaction(function () use ($plan, $facturas, $pies, $saldos, $leg) {
            foreach ($plan as $p) {
                $this->cargarCliente($leg, $p, $facturas, $pies, $saldos);
            }
        });

        $reversa = storage_path('app/reversa_huerfanos_' . date('Ymd_His') . '.json');
        file_put_contents($reversa, json_encode(['bd' => $bd, 'clientes_legacy' => $candidatos]));
        $this->info("Aplicado. Reversa: {$reversa}");

        $this->verificar($leg, $candidatos, $facturas, $plan);

        return self::SUCCESS;
    }

    private function cargarCliente($leg, array $p, $facturas, array $pies, array $saldos): void
    {
        $ficha = $p['ficha'];
        $telefono = $ficha ? (($ficha->telefono ?: $ficha->celular) ?: null) : null;

        $cliente = Cliente::create([
            'sucursal_id' => $p['sucursal_id'],
            'codigo' => $this->codigo($telefono, $p['sucursal_id']),
            'nombre' => $p['nombre'],
            'telefono' => $telefono,
            'direccion' => $ficha ? ($ficha->domicilio ?: null) : null,
            'maximocredito' => $ficha->maximocredito ?? 0,
            'saldo_actual' => $p['saldo'],
            'genero' => $ficha ? ($ficha->genero ?: null) : null,
            'cliente_id_legacy' => $p['cid'],
        ]);

        $mapaFact = [];
        foreach ($facturas->where('clienteid', $p['cid']) as $f) {
            $pie = $pies[$f->id] ?? null;
            $estado = EstadoFactura::desdeLegacy($f->estado)->value;
            $mapaFact[$f->id] = DB::table('facturas')->insertGetId([
                'sucursal_id' => $p['sucursal_id'],
                'cliente_id' => $cliente->id,
                'estado' => $estado,
                'plazo' => $f->plazo,
                'fecha' => $f->fecharegistro ? date('Y-m-d H:i:s', (int) $f->fecharegistro) : null,
                'montototal' => $pie->montototal ?? 0,
                'impuesto' => $pie->impuesto ?? 0,
                'descuento' => $pie->descuento ?? 0,
                'flete' => $pie->flete ?? 0,
                'total' => $pie->total ?? 0,
                'pago' => $pie->pago ?? 0,
                'vuelto' => $pie->vuelto ?? 0,
                'factura_id_legacy' => $f->id,
                'saldo_migrado' => $estado === EstadoFactura::Credito->value ? $saldos[$f->id] : null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $filas = [];
        foreach ($leg->table('linea')->whereIn('facturaid', array_keys($mapaFact))->get() as $l) {
            $filas[] = [
                'factura_id' => $mapaFact[$l->facturaid],
                'producto_id' => $l->productoid,
                'producto_variante_id' => null,
                'descripcion' => $l->descripcion,
                'costo_unit' => $l->costo,
                'precio_unit' => $l->preciounit,
                'cantidad' => $l->cantidad,
                'descuento' => $l->descuento ?? 0,
                'created_at' => now(), 'updated_at' => now(),
            ];
        }
        foreach (array_chunk($filas, 500) as $lote) {
            DB::table('factura_lineas')->insert($lote);
        }

        $filas = [];
        foreach ($leg->table('abono')->where('clienteid', $p['cid'])->orderBy('id')->get() as $a) {
            $filas[] = [
                'cliente_id' => $cliente->id,
                'factura_id' => $mapaFact[$a->facturaid] ?? null,
                'saldo_inicial' => $a->saldoinicial,
                'saldo_final' => $a->saldofinal,
                'sinpe' => $a->sinpe,
                'efectivo' => $a->efectivo,
                'monto_abono' => $a->abono,
                'descuento' => $a->descuento ?? 0,
                'devolucion' => $a->devolucion ?? 0,
                'es_abono' => $a->isabono === 'Y',
                'fecha' => date('Y-m-d H:i:s', (int) $a->datecreated),
                'abono_id_legacy' => $a->id,
                'created_at' => now(), 'updated_at' => now(),
            ];
        }
        foreach (array_chunk($filas, 500) as $lote) {
            DB::table('abonos')->insert($lote);
        }

        $filas = [];
        foreach ($leg->table('consolidado')->where('clienteid', $p['cid'])->orderBy('id')->get() as $c) {
            $filas[] = [
                'cliente_id' => $cliente->id,
                'monto_total' => $c->total ?? 0,
                'detalle_facturas_legacy' => $c->facturas,
                'admin_registro' => $c->adminname,
                'fecha' => date('Y-m-d H:i:s', (int) $c->datecreated),
                'consolidado_id_legacy' => $c->id,
                'created_at' => now(), 'updated_at' => now(),
            ];
        }
        foreach (array_chunk($filas, 500) as $lote) {
            DB::table('recibos_consolidados')->insert($lote);
        }

        $devs = $leg->table('devolucion')->join('factura', 'devolucion.facturaid', '=', 'factura.id')
            ->where('factura.clienteid', $p['cid'])->orderBy('devolucion.id')->select('devolucion.*')->get();
        foreach ($devs as $d) {
            $nueva = Devolucion::create([
                'cliente_id' => $cliente->id,
                'factura_id' => $mapaFact[$d->facturaid] ?? null,
                'estado_legacy' => $d->estado,
                'motivo' => MotivoDevolucion::desdeLegacy($d->descripcion)->value,
                'total_lineas' => $d->totallineas,
                'monto_total' => 0, 'impuesto' => 0, 'descuento' => 0, 'total' => 0,
                'fecha' => date('Y-m-d H:i:s', (int) $d->fecharegistro),
                'devolucion_id_legacy' => $d->id,
            ]);
            $pie = $leg->table('piedev')->where('devolucionid', $d->id)->first();
            if ($pie) {
                $nueva->update([
                    'monto_total' => $pie->montototal, 'impuesto' => $pie->impuesto,
                    'descuento' => $pie->descuento, 'total' => $pie->total,
                ]);
            }
            foreach ($leg->table('lineadev')->where('devolucionid', $d->id)->get() as $l) {
                DevolucionLinea::create([
                    'devolucion_id' => $nueva->id,
                    'producto_id' => $l->productoid,
                    'descripcion' => $l->descripcion,
                    'cantidad' => $l->cantidad,
                    'precio_unit' => $l->preciounit,
                    'precio_total' => $l->preciototal,
                    'descuento' => $l->descuento ?? 0,
                    'impuesto' => $l->impuesto,
                    'costo' => $l->costo ?? 0,
                ]);
            }
        }
    }

    /** Saldo de cada factura abierta: saldofinal de su último abono, o el total si no tiene abonos. */
    private function saldos($leg, array $ids, array $pies): array
    {
        $saldos = [];
        foreach (array_chunk($ids, 1000) as $grupo) {
            $ult = $leg->table('abono')->whereIn('facturaid', $grupo)
                ->selectRaw('facturaid, MAX(id) as ult')->groupBy('facturaid')->pluck('ult', 'facturaid');
            $sal = $ult->isEmpty() ? collect() : $leg->table('abono')->whereIn('id', $ult->values()->all())->pluck('saldofinal', 'facturaid');
            foreach ($grupo as $id) {
                $saldos[$id] = round((float) ($sal[$id] ?? ($pies[$id]->total ?? 0)), 2);
            }
        }
        return $saldos;
    }

    /** Últimos 4 dígitos del teléfono, único dentro de la sucursal (incluye los códigos que ya existen). */
    private function codigo(?string $telefono, int $sucursalId): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $telefono);
        if (strlen($digitos) < 4) {
            return null;
        }
        if (!isset($this->codigosUsados[$sucursalId])) {
            $this->codigosUsados[$sucursalId] = DB::table('clientes')->where('sucursal_id', $sucursalId)
                ->whereNotNull('codigo')->pluck('codigo')->flip()->all();
        }
        $base = substr($digitos, -4);
        $codigo = $base;
        $n = 2;
        while (isset($this->codigosUsados[$sucursalId][$codigo])) {
            $codigo = $base . '-' . $n++;
        }
        $this->codigosUsados[$sucursalId][$codigo] = true;

        return $codigo;
    }

    private function verificar($leg, array $candidatos, $facturas, array $plan): void
    {
        $ids = DB::table('clientes')->whereIn('cliente_id_legacy', $candidatos)->pluck('id')->all();

        $filas = [
            ['Clientes', count($candidatos), DB::table('clientes')->whereIn('cliente_id_legacy', $candidatos)->count()],
            ['Saldo de clientes', round(collect($plan)->sum('saldo'), 2), round((float) DB::table('clientes')->whereIn('id', $ids)->sum('saldo_actual'), 2)],
            ['Facturas', $facturas->count(), DB::table('facturas')->whereIn('cliente_id', $ids)->count()],
            ['Abonos (cantidad)', $leg->table('abono')->whereIn('clienteid', $candidatos)->count(), DB::table('abonos')->whereIn('cliente_id', $ids)->count()],
            ['Abonos (suma)', round((float) $leg->table('abono')->whereIn('clienteid', $candidatos)->sum('abono'), 2), round((float) DB::table('abonos')->whereIn('cliente_id', $ids)->sum('monto_abono'), 2)],
            ['Consolidados (suma)', round((float) $leg->table('consolidado')->whereIn('clienteid', $candidatos)->sum('total'), 2), round((float) DB::table('recibos_consolidados')->whereIn('cliente_id', $ids)->sum('monto_total'), 2)],
            ['Devoluciones', $leg->table('devolucion')->join('factura', 'devolucion.facturaid', '=', 'factura.id')->whereIn('factura.clienteid', $candidatos)->count(), DB::table('devoluciones')->whereIn('cliente_id', $ids)->count()],
        ];
        $descuadre = DB::table('clientes')->whereIn('id', $ids)
            ->whereRaw('saldo_actual <> coalesce((select sum(saldo_migrado) from facturas f where f.cliente_id = clientes.id), 0)')->count();

        $this->table(['Métrica', 'Sistema viejo', 'Sistema nuevo', ''],
            array_map(fn ($f) => [$f[0], $f[1], $f[2], abs($f[1] - $f[2]) < 0.005 ? 'OK' : 'DIFERENCIA'], $filas));
        $this->line('Clientes cuyo saldo no coincide con la suma de sus facturas: ' . $descuadre . ' (debe ser 0)');
    }

    private function revertir(string $archivo, string $bd): int
    {
        if ($this->option('confirmar-bd') !== $bd) {
            $this->error("Para revertir pasá --confirmar-bd={$bd}.");
            return self::FAILURE;
        }
        $datos = json_decode((string) @file_get_contents($archivo), true);
        if (!is_array($datos) || empty($datos['clientes_legacy'])) {
            $this->error('Archivo de reversa inválido.');
            return self::FAILURE;
        }
        if (($datos['bd'] ?? null) !== $bd) {
            $this->error("Esta reversa es de la BD '{$datos['bd']}', no de '{$bd}'.");
            return self::FAILURE;
        }

        $ids = DB::table('clientes')->whereIn('cliente_id_legacy', $datos['clientes_legacy'])->pluck('id')->all();
        if (DB::table('operaciones')->whereIn('cliente_id', $ids)->exists()) {
            $this->error('Ya hay operaciones nuevas con estos clientes: no se revierte.');
            return self::FAILURE;
        }

        DB::transaction(function () use ($ids) {
            $devIds = DB::table('devoluciones')->whereIn('cliente_id', $ids)->pluck('id')->all();
            $facIds = DB::table('facturas')->whereIn('cliente_id', $ids)->pluck('id')->all();
            DB::table('devolucion_lineas')->whereIn('devolucion_id', $devIds)->delete();
            DB::table('devoluciones')->whereIn('cliente_id', $ids)->delete();
            DB::table('abonos')->whereIn('cliente_id', $ids)->delete();
            DB::table('recibos_consolidados')->whereIn('cliente_id', $ids)->delete();
            DB::table('factura_lineas')->whereIn('factura_id', $facIds)->delete();
            DB::table('facturas')->whereIn('cliente_id', $ids)->delete();
            DB::table('ruta_clientes')->whereIn('cliente_id', $ids)->delete();
            DB::table('clientes')->whereIn('id', $ids)->delete();
        });
        $this->info('Revertido: ' . count($ids) . ' clientes con su historial.');

        return self::SUCCESS;
    }
}