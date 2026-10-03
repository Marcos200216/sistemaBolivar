<?php

namespace App\Http\Controllers;

use App\Enums\EstadoFactura;
use App\Enums\EstadoRutaCliente;
use App\Enums\FiltroRuta;
use App\Enums\TipoRuta;
use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Operacion;
use App\Models\ReporteRuta;
use App\Models\Ruta;
use App\Models\RutaCliente;
use Illuminate\Http\Request;
use App\Exceptions\OperacionException;
use App\Services\OperacionService;
use App\Services\RutaSaldoService;
use App\Services\AtrasadosService;

class RutaController extends Controller
{
    public function index()
    {
        return view('admin.rutas');
    }

    // Obtiene (o crea si es la primera vez) la ruta fija de esta combinación tipo+filtro
    public function obtener(string $tipo, string $filtro)
    {
        $tipoEnum = TipoRuta::tryFrom($tipo);
        $filtroEnum = FiltroRuta::tryFrom($filtro);
        abort_if(!$tipoEnum || !$filtroEnum, 404);

                $ruta = Ruta::firstOrCreate(
            ['tipo' => $tipoEnum->value, 'filtro' => $filtroEnum->value],
            ['nombre' => 'Ruta ' . $tipoEnum->label() . ' - ' . $filtroEnum->label()]
        );
        
        
        $activos = $ruta->clientesActivos()->with('cliente')->get();

        // Ítem 8 (reunión) — solo tiene sentido marcar atrasados en el filtro
        // "Activos" (clientes que deben plata); en "Cancelados" nadie debe.
        if ($filtroEnum === FiltroRuta::Activos) {
            $this->marcarAtrasados($activos);
        }

        return response()->json([
            'ruta' => $ruta,
            'activos' => $activos,
            'finalizados' => $ruta->clientesFinalizados()->with('cliente')->get(),
        ]);
    }

    // Datos para el modal "Administrar ruta": los ya agregados (con su orden) + candidatos nuevos
    public function gestionar(Ruta $ruta)
{
    $actuales = $ruta->clientes()->with('cliente')->orderBy('orden')->get();
    $idsActuales = $actuales->pluck('cliente_id');

    $candidatos = $this->clientesSegunFiltro($ruta->filtro)
        ->whereNotIn('id', $idsActuales)
        ->orderBy('nombre')
        ->get(['id', 'nombre', 'telefono', 'saldo_actual', 'latitud', 'longitud']);

    // Ítem 4: "Nuevo" (nunca estuvo en ninguna ruta) vs "Se pasó de ruta"
    // (tiene historial en ruta_clientes de OTRA sucursal).
    $idsCandidatos = $candidatos->pluck('id');
    $idsConHistorialOtraSucursal = RutaCliente::whereIn('cliente_id', $idsCandidatos)
        ->whereHas('ruta', fn ($q) => $q->withoutGlobalScopes()->where('sucursal_id', '!=', $ruta->sucursal_id))
        ->pluck('cliente_id')
        ->unique();

    $candidatos->each(function ($c) use ($idsConHistorialOtraSucursal) {
        $c->trasladado = $idsConHistorialOtraSucursal->contains($c->id);
    });

    return response()->json([
        'ruta' => $ruta,
        'actuales' => $actuales,
        'candidatos' => $candidatos,
    ]);
}

    // Guarda nombre + la lista final de clientes en el orden enviado (agrega, reordena y quita en una sola pasada)
    public function administrar(Request $request, Ruta $ruta)
    {
        $data = $request->validate([
    'nombre' => 'nullable|string|max:255',
    'clientes' => 'present|array',
    'clientes.*' => 'integer|exists:clientes,id',
]);

        if (!empty($data['nombre'])) {
            $ruta->update(['nombre' => $data['nombre']]);
        }

        $existentes = $ruta->clientes()->get()->keyBy('cliente_id');
        $idsFinal = $data['clientes'];

        // Quitar de la ruta a los que ya no vienen en la lista final
        RutaCliente::where('ruta_id', $ruta->id)->whereNotIn('cliente_id', $idsFinal)->delete();

        foreach ($idsFinal as $i => $clienteId) {
            if ($existentes->has($clienteId)) {
                $existentes[$clienteId]->update(['orden' => $i + 1]);
            } else {
                RutaCliente::create([
                    'ruta_id' => $ruta->id,
                    'cliente_id' => $clienteId,
                    'orden' => $i + 1,
                    'estado' => EstadoRutaCliente::Pendiente->value,
                ]);
            }
        }

        return response()->json(['ok' => true]);
    }

    public function reordenar(Request $request, Ruta $ruta)
    {
        $data = $request->validate([
            'orden' => 'required|array|min:1',
            'orden.*' => 'integer|exists:ruta_clientes,id',
        ]);

        foreach ($data['orden'] as $i => $rutaClienteId) {
            RutaCliente::where('id', $rutaClienteId)
                ->where('ruta_id', $ruta->id)
                ->update(['orden' => $i + 1]);
        }

        return response()->json(['ok' => true]);
    }

    public function cambiarEstado(Request $request, RutaCliente $rutaCliente)
    {
        $data = $request->validate([
            'estado' => 'required|in:pendiente,finalizado,no_abono,recobro',
        ]);

        $nuevoEstado = EstadoRutaCliente::from($data['estado']);
        $update = ['estado' => $nuevoEstado->value];

        if ($nuevoEstado === EstadoRutaCliente::Recobro) {
            $maxOrden = RutaCliente::where('ruta_id', $rutaCliente->ruta_id)->max('orden');
            $update['orden'] = $maxOrden + 1;
        }

        $rutaCliente->update($update);

        return response()->json($rutaCliente->fresh());
    }


        // "No abonó" desde Rutas: registra una operación real (con el administrador que la marca),
    // igual que en Facturación. El estado del cliente en la ruta lo cambia el servicio.
    public function noAbono(Request $request, RutaCliente $rutaCliente, OperacionService $servicio)
    {
        $data = $request->validate(['descripcion' => 'nullable|string|max:500']);

        if (in_array($rutaCliente->estado->value, ['finalizado', 'no_abono'], true)) {
            return response()->json(['mensaje' => 'Este cliente ya fue marcado en esta ruta.'], 422);
        }

        try {
            $operacion = $servicio->guardar([
                'cliente_id' => $rutaCliente->cliente_id,
                'ruta_cliente_id' => $rutaCliente->id,
                'no_abono' => true,
                'no_abono_descripcion' => $data['descripcion'] ?? null,
            ]);
        } catch (OperacionException $e) {
            return response()->json(['mensaje' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'operacion_id' => $operacion->id]);
    }

    // Ordenada: resetea estados, la lista y el orden se conservan siempre.
    // Aleatoria: borra la lista completa, queda vacía.
    // En ambos casos se marca ultimo_reinicio: arranca un ciclo nuevo, para
    // que el próximo reporte exportado no mezcle operaciones de un ciclo
    // anterior con las de este (ver exportarReporte()).
       public function reiniciar(Ruta $ruta)
    {
        $clienteIds = $ruta->clientes()->pluck('cliente_id');

        if ($ruta->tipo === TipoRuta::Aleatoria) {
            $ruta->clientes()->delete();
        } else {
            $ruta->clientes()->update([
                'estado' => EstadoRutaCliente::Pendiente->value,
                'etiqueta' => null,
            ]);
            $this->limpiarClientesFueraDeFiltro($clienteIds);
        }

        $ruta->update(['ultimo_reinicio' => now()]);

        return response()->json(['ok' => true]);
    }

    // Solo se puede exportar cuando ya no queda nadie pendiente/en recobro.
    // Por cada cliente, suma el efectivo y sinpe de las operaciones ligadas
    // a su visita en ESTE ciclo (ruta_cliente_id + posterior a
    // ultimo_reinicio, para no arrastrar visitas de ciclos anteriores en
    // las rutas Ordenadas, que reutilizan la misma fila de ruta_clientes).
    public function exportarReporte(Ruta $ruta)
    {
        $clientes = $ruta->clientes()->with('cliente')->orderBy('orden')->get();

        if ($clientes->isEmpty() || $clientes->contains(fn($rc) => in_array($rc->estado->value, ['pendiente', 'recobro']))) {
            return response()->json([
                'mensaje' => 'Todavía hay clientes pendientes o en recobro, no se puede exportar.',
            ], 422);
        }

        $datos = $clientes->map(function ($rc) use ($ruta) {
            $operaciones = Operacion::where('ruta_cliente_id', $rc->id)
                ->when($ruta->ultimo_reinicio, fn ($q) => $q->where('created_at', '>=', $ruta->ultimo_reinicio))
                ->with('facturas', 'abonos')
                ->get();

            $efectivo = 0.0;
            $sinpe = 0.0;
            foreach ($operaciones as $op) {
                                       foreach ($op->facturas as $f) {
                    if ($f->estado === EstadoFactura::Anulada->value) {
                        continue;
                    }
                    $efectivo += (float) $f->efectivo - (float) $f->vuelto;
                    $sinpe += (float) $f->sinpe;
                }
                foreach ($op->abonos as $ab) {
                    $efectivo += (float) $ab->efectivo;
                    $sinpe += (float) $ab->sinpe;
                }
            }

            return [
                'cliente_id' => $rc->cliente_id,
                'nombre' => $rc->cliente->nombre,
                'orden' => $rc->orden,
                'estado' => $rc->estado->value,
                'etiqueta' => $rc->etiqueta,
                'efectivo' => round($efectivo, 2),
                'sinpe' => round($sinpe, 2),
                // Para poder linkear al comprobante (y de ahí a la foto de
                // sinpe) desde la pantalla de Reportes.
                'operaciones' => $operaciones->map(fn ($op) => [
                    'id' => $op->id,
                    'numero' => $op->numero,
                ])->values(),
            ];
        })->values();

               $reporte = ReporteRuta::create([
            'ruta_id' => $ruta->id,
            'tipo' => $ruta->tipo->value,
            'filtro' => $ruta->filtro->value,
            'nombre_ruta' => $ruta->nombre,
            'generado_en' => now(),
            'datos' => $datos,
        ]);

        // Ítem 6: exportar el reporte ahora también reinicia la ruta para
        // el siguiente ciclo, así ya no hace falta un clic aparte en
        // "Empezar de nuevo" (que además queda bloqueado en pantalla hasta
        // que se exporte, para no perder el reporte de la semana).
        if ($ruta->tipo === TipoRuta::Aleatoria) {
            $ruta->clientes()->delete();
                } else {
            $ruta->clientes()->update([
                'estado' => EstadoRutaCliente::Pendiente->value,
                'etiqueta' => null,
            ]);
            $this->limpiarClientesFueraDeFiltro($clientes->pluck('cliente_id'));
        }

        $ruta->update(['ultimo_reinicio' => now()]);

        return response()->json($reporte);
    }
        /** Al cerrar el ciclo, saca de la ruta a quien ya no corresponde a su filtro por saldo. */
    private function limpiarClientesFueraDeFiltro($clienteIds): void
    {
        foreach (collect($clienteIds)->unique() as $id) {
            try {
                app(RutaSaldoService::class)->sincronizar((int) $id, true);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

       protected function clientesSegunFiltro(FiltroRuta $filtro)
    {
        return $filtro === FiltroRuta::Activos
            ? Cliente::activos()
            : Cliente::cancelados();
    }

    /**
     * Ítem 8 (reunión) — marca cada RutaCliente con un atributo temporal
     * `atrasado` (no se guarda en base) según si el cliente lleva 4 semanas
     * o más sin abonar. Referencia: su abono más reciente en cualquier
     * sucursal/operación; si nunca abonó, la factura de crédito nueva más
     * vieja todavía pendiente; si tampoco tiene ninguna (todo su saldo es
     * deuda migrada), la fecha en que se creó el cliente.
     */
       /**
     * Ítem 8 (reunión) — marca cada RutaCliente con un atributo temporal
     * `atrasado` (no se guarda en base) según si el cliente lleva 4 semanas
     * o más sin abonar. Referencia: la fecha MÁS RECIENTE entre su último
     * abono NUEVO real (operacion_id no nulo — los abonos migrados tienen
     * operacion_id null y su created_at es la fecha de la migración, no la
     * fecha real, así que se excluyen acá), su último abono del sistema
     * viejo (tabla `abono` de `legacy`, vía cliente_id_legacy — datecreated
     * ahí es timestamp Unix), y la factura de crédito nueva pendiente más
     * vieja. Si no hay rastro de ninguna de las tres (cliente nuevo, sin
     * historial), se usa created_at del cliente como último recurso.
     */
       /**
     * Marca cada RutaCliente con un atributo temporal `atrasado` (no se guarda en base):
     * 4 semanas o más sin abonar. La regla vive en AtrasadosService.
     */
    private function marcarAtrasados($coleccion): void
    {
        if ($coleccion->isEmpty()) {
            return;
        }

        $clientes = $coleccion->pluck('cliente')->filter()->unique('id')->values();
        $atrasados = app(AtrasadosService::class)->referencias($clientes);

        foreach ($coleccion as $rc) {
            $rc->atrasado = $atrasados->has($rc->cliente_id);
        }
    }
}