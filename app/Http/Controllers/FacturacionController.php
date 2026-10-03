<?php
// app/Http/Controllers/FacturacionController.php

namespace App\Http\Controllers;

use App\Exceptions\OperacionException;
use App\Enums\EstadoFactura;
use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Devolucion;
use App\Models\DevolucionLinea;
use App\Models\Factura;
use App\Models\Producto;
use App\Models\RutaCliente;
use App\Models\Subcategoria;
use App\Models\Sucursal;
use App\Services\OperacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ReciboService;


class FacturacionController extends Controller
{
    public function index()
    {
        $canalSucursal = Sucursal::find(session('sucursal_id'))->canal ?? 'normal';

        return view('admin.facturacion', compact('canalSucursal'));
    }

    /** Autocompletado de clientes por nombre, código o teléfono. */
    public function buscarClientes(Request $request)
    {
        $q = trim((string) $request->query('buscar', ''));

        $clientes = Cliente::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nombre', 'like', "%{$q}%")
                        ->orWhere('codigo', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre')
            ->limit(15)
            ->get(['id', 'codigo', 'nombre', 'telefono']);

        return response()->json($clientes);
    }

    /**
     * Datos de un cliente + sus cuentas activas: deuda antigua (migrada, sin
     * factura), facturas nuevas de crédito con pendiente (para abonar) y
     * facturas recientes con sus líneas y disponible para devolver.
     */
    public function cuentas(Cliente $cliente)
    {
        $facturasCredito = Factura::nuevas()
            ->where('cliente_id', $cliente->id)
            ->where('estado', EstadoFactura::Credito->value)
            ->with('operacion')
            ->orderBy('created_at')->orderBy('id')
            ->get();

        $facturasCreditoPendientes = [];
        $sumaPendientes = 0;
        foreach ($facturasCredito as $f) {
            $abonado = (float) Abono::where('factura_id', $f->id)->sum('monto_abono');
            $devuelto = (float) Devolucion::where('factura_id', $f->id)->sum('total');
            $pendiente = round(max(0, (float) $f->total - $abonado - $devuelto), 2);
            if ($pendiente > 0) {
                $facturasCreditoPendientes[] = [
                    'id' => $f->id,
                    'numero' => $f->operacion?->numero,
                    'fecha' => optional($f->created_at)->format('d/m/Y'),
                    'total' => (float) $f->total,
                    'pendiente' => $pendiente,
                ];
                $sumaPendientes += $pendiente;
            }
        }
        $deudaAntigua = round(max(0, (float) $cliente->saldo_actual - $sumaPendientes), 2);

        // Facturas recientes del cliente (nuevas y migradas) con sus líneas, para devolver
        $facturas = Factura::where('cliente_id', $cliente->id)
            ->where('estado', '!=', EstadoFactura::Anulada->value)
            ->with(['lineas', 'operacion'])
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        $facturasDevolucion = $facturas->map(function (Factura $f) {
            $lineas = $f->lineas->map(function ($l) {
                $yaDevuelto = (int) round(DevolucionLinea::where('factura_linea_id', $l->id)->sum('cantidad'));
                $disponible = max(0, (int) $l->cantidad - $yaDevuelto);
                return [
                    'factura_linea_id' => $l->id,
                    'descripcion' => $l->descripcion,
                    'cantidad' => (int) $l->cantidad,
                    'disponible' => $disponible,
                    'precio_unit' => (float) $l->precio_unit,
                ];
            })->filter(fn($l) => $l['disponible'] > 0)->values();

            if ($lineas->isEmpty()) {
                return null;
            }

            return [
                'id' => $f->id,
                'nueva' => $f->operacion_id !== null,
                'numero' => $f->operacion?->numero,
                'factura_id_legacy' => $f->factura_id_legacy,
                'fecha' => optional($f->created_at)->format('d/m/Y'),
                'lineas' => $lineas,
            ];
        })->filter()->values();

        return response()->json([
            'cliente' => [
                'id' => $cliente->id,
                'codigo' => $cliente->codigo,
                'nombre' => $cliente->nombre,
                'telefono' => $cliente->telefono,
                'direccion' => $cliente->direccion,
                'saldo_actual' => (float) $cliente->saldo_actual,
                'maximocredito' => (float) $cliente->maximocredito,
            ],
            'deuda_antigua' => $deudaAntigua,
            'facturas_credito' => $facturasCreditoPendientes,
            'facturas_devolucion' => $facturasDevolucion,
        ]);
    }

    /**
     * 17.3 — Subcategorías del canal de la sucursal actual, para el select
     * opcional de la pestaña Venta.
     */
    public function subcategorias()
    {
        $sucursal = Sucursal::find(session('sucursal_id'));
        $canal = $sucursal->canal ?? 'normal';

        $subcategorias = Subcategoria::whereHas('categoria', fn($qc) => $qc->where('canal', $canal))
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return response()->json($subcategorias);
    }

    /**
     * Autocompletado de productos del catálogo, filtrado por el canal de la
     * sucursal y, opcionalmente (17.3), por subcategoria_id.
     */
    public function buscarProductos(Request $request)
    {
        $sucursal = Sucursal::find(session('sucursal_id'));
        $canal = $sucursal->canal ?? 'normal';
        $q = trim((string) $request->query('buscar', ''));
        $subcategoriaId = $request->query('subcategoria_id');

        $productos = Producto::with(['variantes', 'subcategoria.categoria'])
            ->whereHas('subcategoria.categoria', fn($qc) => $qc->where('canal', $canal))
            ->where('activo', true)
            ->when($subcategoriaId, fn($query) => $query->where('subcategoria_id', $subcategoriaId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nombre', 'like', "%{$q}%")
                        ->orWhere('codigo', 'like', "%{$q}%")
                        ->orWhereHas('subcategoria', fn($qs) => $qs->where('nombre', 'like', "%{$q}%"));
                });
            })
            ->orderBy('nombre')
            ->limit(50)
            ->get();

        return response()->json($productos->map(fn($p) => [
            'id' => $p->id,
            'nombre' => $p->nombre,
            'codigo' => $p->codigo,
            'precio' => $p->precio !== null ? (float) $p->precio : null,
            'categoria' => $p->subcategoria->categoria->nombre ?? null,
            'subcategoria' => $p->subcategoria->nombre ?? null,
            'variantes' => $p->variantes->map(fn($v) => [
                'id' => $v->id,
                'talla' => $v->talla,
                'color' => $v->color,
                'stock' => (int) $v->stock,
            ]),
        ]));
    }

    /** Un solo producto por id, mismo formato que buscarProductos(). Usado para prellenar desde Apartados. */
    public function producto(Producto $producto)
    {
        $producto->load('variantes', 'subcategoria.categoria');

        return response()->json([
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'codigo' => $producto->codigo,
            'precio' => $producto->precio !== null ? (float) $producto->precio : null,
            'categoria' => $producto->subcategoria->categoria->nombre ?? null,
            'subcategoria' => $producto->subcategoria->nombre ?? null,
            'variantes' => $producto->variantes->map(fn($v) => [
                'id' => $v->id,
                'talla' => $v->talla,
                'color' => $v->color,
                'stock' => (int) $v->stock,
            ]),
        ]);
    }

    /** Guarda la operación completa (venta + abono + devoluciones de la visita). */
       /** Guarda la operación completa (venta + abono + devoluciones de la visita). */
    public function guardar(Request $request, OperacionService $servicio)
    {
        $request->validate([
            'payload' => 'required|string',
        ]);

        $payload = json_decode($request->input('payload'), true);
        if (!is_array($payload) || empty($payload['cliente_id'])) {
            return response()->json(['mensaje' => 'Datos incompletos para guardar la operación.'], 422);
        }

        $fotos = [
            'venta' => $request->file('foto_venta'),
            'abono' => $request->file('foto_abono'),
        ];

        // Bloque 5 #1 — si llegó un ruta_cliente_id que NO es de este cliente
        // (por ejemplo, se entró desde Rutas con otro cliente y luego se cambió
        // de cliente en la misma pantalla), se descarta para que corra la
        // búsqueda automática de abajo en vez de perder el vínculo.
        if (!empty($payload['ruta_cliente_id'])) {
            $pertenece = RutaCliente::where('id', $payload['ruta_cliente_id'])
                ->where('cliente_id', $payload['cliente_id'])
                ->exists();
            if (!$pertenece) {
                $payload['ruta_cliente_id'] = null;
            }
        }

        // 17.1 — si no vino ruta_cliente_id (o sea, no se entró desde Rutas),
        // se busca automáticamente si el cliente tiene una única ruta activa
        // en esta sucursal. Si hay más de una coincidencia, no se adivina.
        $rutasAmbiguas = false;
        if (empty($payload['ruta_cliente_id'])) {
            $saldoCliente = (float) Cliente::whereKey($payload['cliente_id'])->value('saldo_actual');
            $filtroEsperado = $saldoCliente > 0 ? 'activos' : 'cancelados';

            $rutasCliente = RutaCliente::whereHas('ruta', fn($q) => $q
                ->where('sucursal_id', session('sucursal_id'))
                ->where('filtro', $filtroEsperado))
                ->where('cliente_id', $payload['cliente_id'])
                ->whereIn('estado', ['pendiente', 'recobro'])
                ->get();

            if ($rutasCliente->count() === 1) {
                $payload['ruta_cliente_id'] = $rutasCliente->first()->id;
            } elseif ($rutasCliente->count() > 1) {
                $rutasAmbiguas = true;
            }
        }

        $operacion = $servicio->guardar($payload, $fotos);

       

        $operacion->load('cliente', 'facturas.lineas', 'abonos', 'devoluciones.lineas');

        return response()->json([
            'mensaje' => 'Operación guardada.',
            'operacion' => $operacion,
            'ruta_vinculada' => $operacion->ruta_cliente_id !== null,
            'rutas_ambiguas' => $rutasAmbiguas,
        ]);
    }


    /** Anula una factura fresca (ítem 7), restringido a superadmin. */
    public function anular(Request $request, Factura $factura, OperacionService $servicio)
    {
        if (!Auth::user()?->es_superadmin) {
            return response()->json(['mensaje' => 'Solo un superadmin puede anular una factura.'], 403);
        }

        $request->validate([
            'motivo' => 'nullable|string|max:500',
        ]);

        try {
            $factura = $servicio->anularFactura($factura, $request->input('motivo'), Auth::id());
        } catch (OperacionException $e) {
            return response()->json(['mensaje' => $e->getMessage()], 422);
        }

        return response()->json([
            'mensaje' => 'Factura anulada.',
            'factura' => $factura,
        ]);
    }
}
