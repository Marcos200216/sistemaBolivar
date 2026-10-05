<?php
// app/Http/Controllers/AbonoController.php

namespace App\Http\Controllers;

use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Devolucion;
use App\Models\Factura;
use App\Models\Operacion;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\ReciboEnvio;

class AbonoController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->wantsJson()) {
            return view('admin.abonos');
        }

        // El Global Scope de sucursal ya filtra los clientes de la sucursal en sesión
        $query = Cliente::query();

        if ($busqueda = trim((string) $request->query('busqueda'))) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                  ->orWhere('codigo', 'like', "%{$busqueda}%")
                  ->orWhere('telefono', 'like', "%{$busqueda}%");
            });
        }

        // activos = saldo > 0 · cancelados = saldo <= 0 (incluye saldo a favor) · todos = sin filtro
        $filtro = $request->query('filtro', 'todos');
        if ($filtro === 'activos' || $filtro === 'deuda') {
            $query->where('saldo_actual', '>', 0);
        } elseif ($filtro === 'cancelados') {
            $query->where('saldo_actual', '<=', 0);
        }

        $clientes = $query->orderBy('nombre')
            ->paginate(20, ['id', 'codigo', 'nombre', 'telefono', 'saldo_actual'])
            ->withQueryString();

        // Totales de abonos y de facturas solo de los clientes de esta página
        $ids = $clientes->getCollection()->pluck('id');

        // Operaciones de traspaso: sus abonos/facturas no cuentan como pagos ni compras reales
        // (igual que en Reportes). Los migrados (operacion_id NULL) se conservan.
        $traspasos = Operacion::withoutGlobalScopes()->where('tipo', 'traspaso')->select('id');
        $sinTraspaso = fn ($q) => $q->whereNull('operacion_id')->orWhereNotIn('operacion_id', $traspasos);

        $abonos = Abono::whereIn('cliente_id', $ids)
            ->where('monto_abono', '>', 0)
            ->where($sinTraspaso)
            ->selectRaw('cliente_id, COUNT(DISTINCT COALESCE(operacion_id, -id)) as n, SUM(monto_abono) as total, MAX(fecha) as ultimo')
            ->groupBy('cliente_id')
            ->get()
            ->keyBy('cliente_id');

        $facturas = Factura::whereIn('cliente_id', $ids)
            ->where('estado', '!=', 'anulada')
            ->where($sinTraspaso)
            ->selectRaw('cliente_id, COUNT(*) as n, SUM(total) as total, MAX(COALESCE(fecha, created_at)) as ultima')
            ->groupBy('cliente_id')
            ->get()
            ->keyBy('cliente_id');

        $clientes->getCollection()->transform(function ($c) use ($abonos, $facturas) {
            $a = $abonos->get($c->id);
            $f = $facturas->get($c->id);

            $c->setAttribute('abonos_n', $a ? (int) $a->n : 0);
            $c->setAttribute('abonos_total', $a ? (float) $a->total : 0);
            $c->setAttribute('ultimo_abono', $a && $a->ultimo ? Carbon::parse($a->ultimo)->format('d/m/Y') : null);

            $c->setAttribute('facturas_n', $f ? (int) $f->n : 0);
            $c->setAttribute('facturas_total', $f ? (float) $f->total : 0);
            $c->setAttribute('ultima_factura', $f && $f->ultima ? Carbon::parse($f->ultima)->format('d/m/Y') : null);

            return $c;
        });

        // Resumen de toda la sucursal (no depende del buscador ni del filtro)
        $resumen = Cliente::where('saldo_actual', '>', 0)
            ->selectRaw('COUNT(*) as clientes, COALESCE(SUM(saldo_actual), 0) as total')
            ->first();

        return response()->json([
            'clientes' => $clientes,
            'resumen' => [
                'clientes_activos' => (int) $resumen->clientes,
                'total_por_cobrar' => $resumen->total,
            ],
        ]);
    }

    /**
     * Línea de tiempo del cliente: compras, devoluciones, abonos y visitas sin abono,
     * mezclados por fecha (más reciente primero).
     *
     * - "efecto" es lo que el movimiento le hace a la cuenta: compra a crédito suma (+),
     *   devolución y abono restan (−), compra de contado casi siempre 0 (solo suma si
     *   consumió saldo a favor).
     * - "saldo" (saldo después del movimiento) solo se calcula para operaciones del
     *   sistema nuevo. En lo migrado el saldo_inicial no encadena, así que va null.
     * - Dentro de una misma visita el orden real es devolución → venta → abono.
     * - Los traspasos se marcan con es_traspaso y dicen con qué cliente fueron.
     *
     * Abono/Devolucion no tienen el trait de sucursal, pero {cliente} ya viene scoped
     * por el route model binding, así que un cliente de otra sucursal da 404.
     */
    public function historial(Cliente $cliente)
    {
        $abonos = Abono::where('cliente_id', $cliente->id)->get();
        $facturas = Factura::where('cliente_id', $cliente->id)->withCount('lineas')->get();
        $facturasPorId = $facturas->keyBy('id');
        $devoluciones = Devolucion::where('cliente_id', $cliente->id)->get();
        $ops = Operacion::withoutGlobalScopes()->where('cliente_id', $cliente->id)->get()->keyBy('id');

        // Traspasos: nombre del otro cliente para mostrar "a quién / de quién".
        $nombresTraspaso = Cliente::withoutGlobalScopes()
            ->whereIn('id', $ops->pluck('traspaso_cliente_id')->filter()->unique())
            ->pluck('nombre', 'id');
        $traspasoDe = function ($operacionId) use ($ops, $nombresTraspaso) {
            $op = $operacionId ? $ops->get($operacionId) : null;
            if (!$op || $op->tipo !== 'traspaso') {
                return null;
            }
            return ['nombre' => $nombresTraspaso->get($op->traspaso_cliente_id) ?? 'otro cliente'];
        };

        // Los movimientos de una operación nueva se anclan a la fecha de la operación,
        // así quedan juntos y en el orden correcto aunque difieran por segundos.
        $momento = function ($operacionId, $propia) use ($ops) {
            $op = $operacionId ? $ops->get($operacionId) : null;
            return $op
                ? (self::aFecha($op->fecha) ?? self::aFecha($op->created_at) ?? self::aFecha($propia))
                : self::aFecha($propia);
        };

        // Número visible de una factura: el de la operación (nuevas) o el del sistema anterior (migradas)
        $numeroFactura = function ($f) use ($ops) {
            $op = $f->operacion_id ? $ops->get($f->operacion_id) : null;
            return $op ? $op->numero : $f->factura_id_legacy;
        };

        $items = [];

        // Abonos (agrupados por operación; los migrados sin operación van uno por uno)
        foreach ($abonos->groupBy(fn ($a) => $a->operacion_id ?? 'legacy-' . $a->id) as $grupo) {
            $p = $grupo->first();
            $monto = round((float) $grupo->sum('monto_abono'), 2);
            $tr = $traspasoDe($p->operacion_id);
                        if ($tr) {
                continue; // los traspasos se arman más abajo, una fila por operación
            }

            // A qué factura(s) se aplicó el abono
            $aplicado = $grupo->pluck('factura_id')->unique()->map(function ($fid) use ($facturasPorId, $numeroFactura) {
                if (!$fid) {
                    return 'deuda anterior';
                }
                $f = $facturasPorId->get($fid);
                if (!$f) {
                    return null;
                }
                $n = $numeroFactura($f);
                $txt = $n ? 'N° ' . $n : 'una factura';
                return (string) $f->estado === 'anulada' ? $txt . ' (anulada)' : $txt;
            })->filter()->values();

            $items[] = [
                'tipo' => $monto > 0 ? 'abono' : 'sin_abono',
                'etiqueta' => $tr ? 'Traspaso enviado' : ($monto > 0 ? 'Abono' : 'Visita sin abono'),
                'es_traspaso' => (bool) $tr,
                'orden' => 3,
                'id' => $p->id,
                'operacion_id' => $p->operacion_id,
                'abono_id' => $p->id,
                'factura_id' => null,
                'fecha_obj' => $momento($p->operacion_id, $p->fecha),
                'monto' => $monto,
                'efectivo' => round((float) $grupo->sum('efectivo'), 2),
                'sinpe' => round((float) $grupo->sum('sinpe'), 2),
                'efecto' => -$monto,
                'detalle' => $tr
                    ? 'Deuda trasladada a ' . $tr['nombre']
                    : (($monto > 0 && $aplicado->isNotEmpty()) ? 'Aplicado a: ' . $aplicado->implode(', ') : null),
                'saldo' => null,
            ];
        }

        // Visitas "no abonó" del sistema nuevo (no generan fila de Abono)
        foreach ($ops as $op) {
            if (!$op->no_abono) {
                continue;
            }
            $items[] = [
                'tipo' => 'sin_abono',
                'etiqueta' => 'Visita sin abono',
                'es_traspaso' => false,
                'orden' => 3,
                'id' => $op->id,
                'operacion_id' => $op->id,
                'abono_id' => null,
                'factura_id' => null,
                'fecha_obj' => $momento($op->id, $op->created_at),
                'monto' => 0.0,
                'efectivo' => 0.0,
                'sinpe' => 0.0,
                'efecto' => 0.0,
                'detalle' => null,
                'saldo' => null,
            ];
        }

        // Compras
        foreach ($facturas as $f) {
            $estado = (string) $f->estado;
            $total = round((float) $f->total, 2);
            $favor = round((float) $f->saldo_favor_aplicado, 2);

            if ($estado === 'anulada') {
                $efecto = 0.0;
                $etiqueta = 'Compra anulada';
            } elseif ($estado === 'contado') {
                $efecto = $favor; // consumir saldo a favor acerca la cuenta a 0
                $etiqueta = 'Compra de contado';
            } else {
                $efecto = $total;
                $etiqueta = 'Compra a crédito';
            }

            $partes = [];
            $tr = $traspasoDe($f->operacion_id);
                        if ($tr) {
                continue; // la factura del traspaso se muestra en la fila del traspaso
            }
            if ($tr) {
                $etiqueta = 'Traspaso recibido';
                $partes[] = 'deuda trasladada desde ' . $tr['nombre'];
            }
            if ($f->lineas_count) {
                $partes[] = $f->lineas_count . ($f->lineas_count == 1 ? ' producto' : ' productos');
            }
            if (!in_array($estado, ['contado', 'anulada'], true) && (int) $f->plazo > 0) {
                $partes[] = 'plazo ' . (int) $f->plazo . ' días';
            }
            if ($estado === 'contado' && $favor > 0) {
                $partes[] = 'usó ₡' . number_format($favor, 2) . ' de saldo a favor';
            }
            if ($estado === 'anulada') {
                $partes[] = 'no suma a la cuenta';
                $abonadoAnulada = round((float) $abonos->where('factura_id', $f->id)->sum('monto_abono'), 2);
                if ($abonadoAnulada > 0) {
                    $partes[] = 'tiene abonos por ₡' . number_format($abonadoAnulada, 2);
                }
            }

            $items[] = [
                'tipo' => 'compra',
                'etiqueta' => $etiqueta,
                'es_traspaso' => (bool) $tr,
                'orden' => 2,
                'id' => $f->id,
                'operacion_id' => $f->operacion_id,
                'abono_id' => null,
                'factura_id' => $f->id,
                'fecha_obj' => $f->operacion_id
                    ? $momento($f->operacion_id, $f->created_at)
                    : (self::aFecha($f->fecha) ?? self::aFecha($f->created_at)),
                'monto' => $total,
                'efectivo' => 0.0,
                'sinpe' => 0.0,
                'efecto' => $efecto,
                'detalle' => $partes ? implode(' · ', $partes) : null,
                'saldo' => null,
            ];
        }

        // Devoluciones
        foreach ($devoluciones as $d) {
            $total = round((float) $d->total, 2);
            $motivo = $d->motivo instanceof \App\Enums\MotivoDevolucion
                ? $d->motivo->label()
                : null;

            $items[] = [
                'tipo' => 'devolucion',
                'etiqueta' => 'Devolución',
                'es_traspaso' => false,
                'orden' => 1,
                'id' => $d->id,
                'operacion_id' => $d->operacion_id,
                'abono_id' => null,
                'factura_id' => null,
                'fecha_obj' => $momento($d->operacion_id, $d->fecha),
                'monto' => $total,
                'efectivo' => 0.0,
                'sinpe' => 0.0,
                'efecto' => -$total,
                'detalle' => $motivo ? 'Motivo: ' . $motivo : null,
                'saldo' => null,
            ];
        }


                // Traspasos: una sola fila por operación, con la dirección y el tipo bien dichos.
        // El origen siempre se crea primero: su pareja tiene el número siguiente.
        foreach ($ops->where('tipo', 'traspaso') as $op) {
            $efecto = round((float) $op->saldo_final - (float) $op->saldo_inicial, 2);

            $esOrigen = Operacion::withoutGlobalScopes()
                ->where('sucursal_id', $op->sucursal_id)
                ->where('cliente_id', $op->traspaso_cliente_id)
                ->where('traspaso_cliente_id', $op->cliente_id)
                ->where('tipo', 'traspaso')
                ->where('numero', $op->numero + 1)
                ->exists();

            $aFavor = $esOrigen ? ((float) $op->saldo_inicial < 0) : ($efecto < 0);
            $otro = $nombresTraspaso->get($op->traspaso_cliente_id) ?? 'otro cliente';
            $que = $aFavor ? 'Saldo a favor trasladado' : 'Deuda trasladada';

            $items[] = [
                'tipo' => 'traspaso',
                'etiqueta' => $esOrigen ? 'Traspaso enviado' : 'Traspaso recibido',
                'es_traspaso' => true,
                'orden' => 2,
                'id' => $op->id,
                'operacion_id' => $op->id,
                'abono_id' => null,
                'factura_id' => null,
                'fecha_obj' => $momento($op->id, $op->created_at),
                'monto' => abs($efecto),
                'efectivo' => 0.0,
                'sinpe' => 0.0,
                'efecto' => $efecto,
                'detalle' => $que . ($esOrigen ? ' a ' : ' desde ') . $otro,
                'saldo' => null,
            ];
        }
        // Saldo después de cada movimiento — solo operaciones nuevas.
        $porOperacion = [];
        foreach ($items as $k => $it) {
            if ($it['operacion_id'] && $ops->has($it['operacion_id'])) {
                $porOperacion[$it['operacion_id']][] = $k;
            }
        }
        foreach ($porOperacion as $opId => $claves) {
            usort($claves, fn ($a, $b) =>
                [$items[$a]['orden'], $items[$a]['id']] <=> [$items[$b]['orden'], $items[$b]['id']]);

            $corrido = (float) $ops[$opId]->saldo_inicial;
            foreach ($claves as $k) {
                $corrido = round($corrido + $items[$k]['efecto'], 2);
                $items[$k]['saldo'] = $corrido;
            }
        }

        // Más reciente primero; en empate, abono arriba de compra y compra arriba de devolución
        usort($items, function ($a, $b) {
            $ta = $a['fecha_obj'] ? $a['fecha_obj']->timestamp : 0;
            $tb = $b['fecha_obj'] ? $b['fecha_obj']->timestamp : 0;
            return [$tb, $b['orden'], $b['id']] <=> [$ta, $a['orden'], $a['id']];
        });

        $recibos = $this->recibosPorOperacion($ops->keys());

$salida = array_map(function ($it) use ($recibos) {
    $it['fecha'] = $it['fecha_obj'] ? $it['fecha_obj']->format('d/m/Y H:i') : null;
    $it['recibo'] = $it['operacion_id'] ? ($recibos[$it['operacion_id']] ?? null) : null;
    unset($it['fecha_obj'], $it['orden']);
    return $it;
}, $items);

        return response()->json(array_values($salida));
    }

    /** Facturas del cliente (pestaña "Factura" del modal), más reciente primero. */
    public function facturas(Cliente $cliente)
    {
        $facturas = Factura::where('cliente_id', $cliente->id)->withCount('lineas')->get();
        $ops = Operacion::withoutGlobalScopes()
            ->whereIn('id', $facturas->pluck('operacion_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $recibos = $this->recibosPorOperacion($facturas->pluck('operacion_id'));
        $lista = $facturas->map(function ($f) use ($ops, $recibos) {
            $op = $f->operacion_id ? $ops->get($f->operacion_id) : null;
            $fecha = self::aFecha($f->fecha)
                ?? self::aFecha($op?->fecha)
                ?? self::aFecha($op?->created_at)
                ?? self::aFecha($f->created_at);

            $estado = (string) $f->estado;
            $esTraspaso = $op?->tipo === 'traspaso';
            $etiqueta = match ($estado) {
                'contado' => 'Contado',
                'saldada' => 'Crédito saldado',
                'anulada' => 'Anulada',
                default => 'Crédito',
            };
            if ($esTraspaso) {
                $etiqueta = 'Traspaso recibido';
            }

            $partes = [];
            if ($f->lineas_count) {
                $partes[] = $f->lineas_count . ($f->lineas_count == 1 ? ' producto' : ' productos');
            }
            if (in_array($estado, ['credito', 'saldada'], true) && (int) $f->plazo > 0) {
                $partes[] = 'plazo ' . (int) $f->plazo . ' días';
            }

            return [
                'id' => $f->id,
                'ts' => $fecha ? $fecha->timestamp : 0,
                'fecha' => $fecha ? $fecha->format('d/m/Y H:i') : null,
                'numero' => $op ? $op->numero : $f->factura_id_legacy,
                'estado' => $estado,
                'etiqueta' => $etiqueta,
                'es_traspaso' => $esTraspaso,
                'total' => round((float) $f->total, 2),
                'detalle' => $partes ? implode(' · ', $partes) : null,
                'operacion_id' => $f->operacion_id,
                'factura_id' => $f->id,
                'recibo' => $f->operacion_id ? ($recibos[$f->operacion_id] ?? null) : null,
            ];
        })->sortBy([['ts', 'desc'], ['id', 'desc']])->values()->map(function ($x) {
            unset($x['ts']);
            return $x;
        });

        return response()->json($lista);
    }

    /**
     * Factura migrada (sin operación): recibo simple con lo que quedó guardado.
     * Las facturas nuevas se mandan al comprobante normal.
     * Factura sí tiene el trait de sucursal, así que otra sucursal da 404.
     */
    public function facturaHistorica(Factura $factura)
    {
        if ($factura->operacion_id) {
            return redirect()->route('operaciones.comprobante', $factura->operacion_id);
        }

        $factura->load('lineas', 'cliente');

        $sucursal = Sucursal::find(session('sucursal_id'));
        $canal = $sucursal?->canal ?? 'normal';
        $canal = $canal instanceof \BackedEnum ? $canal->value : $canal;
        $nombreCanal = $canal === 'mayorista' ? 'Distribuidora Guana' : 'Distribuidora Azur';
        $logo = $canal === 'mayorista' ? 'fondo_guana.png' : 'fondo_azur.png';

        $estado = (string) $factura->estado;
        $etiqueta = match ($estado) {
            'contado' => 'Contado',
            'saldada' => 'Crédito saldado',
            'anulada' => 'Anulada',
            default => 'Crédito',
        };

        return view('admin.factura-historica', [
            'factura' => $factura,
            'nombreCanal' => $nombreCanal,
            'logo' => $logo,
            'etiqueta' => $etiqueta,
            'fecha' => self::aFecha($factura->fecha) ?? self::aFecha($factura->created_at),
        ]);
    }

    private static function aFecha($valor): ?Carbon
    {
        if (!$valor) {
            return null;
        }

        return $valor instanceof Carbon ? $valor : Carbon::parse($valor);
    }

    /** Último estado de envío y si hay PDF guardado, por operación. */
private function recibosPorOperacion($operacionIds): array
{
    $ids = collect($operacionIds)->filter()->unique()->values();
    if ($ids->isEmpty()) {
        return [];
    }

    return ReciboEnvio::whereIn('operacion_id', $ids)
        ->orderBy('id')
        ->get()
        ->groupBy('operacion_id')
        ->map(function ($g) {
            $ultimo = $g->last();

            return [
                'estado' => $ultimo->estado,
                'pdf' => $g->last(fn ($r) => $r->ruta_pdf) !== null,
                'fecha' => optional($ultimo->enviado_at ?? $ultimo->created_at)->format('d/m H:i'),
            ];
        })
        ->all();
}
}