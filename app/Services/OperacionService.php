<?php
// app/Services/OperacionService.php

namespace App\Services;

use App\Enums\EstadoFactura;
use App\Enums\EstadoRutaCliente;
use App\Enums\MotivoDevolucion;
use App\Exceptions\OperacionException;
use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Devolucion;
use App\Models\DevolucionLinea;
use App\Models\Factura;
use App\Models\FacturaLinea;
use App\Models\Operacion;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\RutaCliente;
use App\Models\Sucursal;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;


/**
 * Guarda de una sola vez todo lo que pasó en una visita al cliente.
 *
 * $datos = [
 *   'cliente_id'      => int,
 *   'user_id'         => int (opcional, por defecto el usuario logueado),
 *   'ruta_cliente_id' => int|null,
 *   'no_abono'        => bool (excluyente con lo demás),
 *   'venta' => [
 *       'tipo'      => 'contado'|'credito',
 *       'plazo'     => 15|30|45 (solo crédito),
 *       'efectivo'  => number, 'sinpe' => number (solo contado),
 *       'direccion' => string|null,
 *       'lineas'    => [[ 'producto_id', 'cantidad', 'precio_unit' (solo si el producto no tiene precio),
 *                         'producto_variante_id' (opcional), 'descuento_tipo' => 'monto'|'porcentaje', 'descuento' ]],
 *   ],
 *   'abono' => [ 'efectivo' => number, 'sinpe' => number, 'factura_id' => int|null (null = automático) ],
 *   'devoluciones' => [[ 'factura_id', 'motivo', 'lineas' => [[ 'factura_linea_id', 'cantidad', 'regresa_stock' ]] ]],
 * ]
 * $fotos = ['venta' => UploadedFile|null, 'abono' => UploadedFile|null]  (comprobantes de sinpe)
 *
 * Orden de aplicación: devoluciones → venta → abono.
 * Todos los montos se calculan en céntimos (enteros) para evitar errores de redondeo.
 */
class OperacionService
{
    /** @var array<int, array{0:int,1:int,2:string}> [variante_id, delta, nombre] */
    private array $stockPendiente = [];
    private array $stockAplicado = [];
    private array $archivosGuardados = [];
    private array $preciosNuevos = [];

    private const HORAS_LIMITE_ANULACION = 24;
    
    public function guardar(array $datos, array $fotos = []): Operacion
    {
        $this->stockPendiente = [];
        $this->stockAplicado = [];
        $this->archivosGuardados = [];
        $this->preciosNuevos = [];

        DB::beginTransaction();

        try {
            $operacion = $this->ejecutar($datos, $fotos);
            // El stock vive en otra BD (Railway): se mueve al final, justo antes del commit
            $this->aplicarStock();
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            $this->revertirStock();
            if ($this->archivosGuardados) {
                Storage::disk('comprobantes')->delete($this->archivosGuardados);
            }
            throw $e;
        }

               $this->guardarPreciosNuevos();
        $this->sincronizarRutas((int) $operacion->cliente_id);

        return $operacion->refresh();
    }

       /**
     * Traspasa deuda de un cliente (origen) a otro (destino), dentro de la
     * misma sucursal. $datos:
     *   'cliente_origen_id'  => int,
     *   'cliente_destino_id' => int,
     *   'user_id'            => int (opcional),
     *   'factura_ids'        => int[]|null (null = todo el saldo del origen)
     *
     * Se crean DOS Operacion, cada una de un solo cliente (para no romper
     * ComprobanteController, que asume una operación = un cliente):
     *   - Operación del origen: Abono(s) sin dinero real (efectivo=sinpe=0)
     *     que saldan lo traspasado.
     *   - Operación del destino: una Factura nueva a crédito, sin líneas
     *     (no hay producto real detrás), por el monto traspasado.
     *
     * @return array{0: Operacion, 1: Operacion} [operación origen, operación destino]
     */
    public function traspasar(array $datos): array
    {
        DB::beginTransaction();
        try {
            $resultado = $this->ejecutarTraspaso($datos);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

                $this->sacarDeRutas((int) $resultado[0]->cliente_id, (int) $resultado[1]->cliente_id);

        return [$resultado[0]->refresh(), $resultado[1]->refresh()];
    }

    /** Anula una factura fresca: revierte saldo del cliente y devuelve stock. No borra la fila. */
public function anularFactura(Factura $factura, ?string $motivo, ?int $userId = null): Factura
{
    DB::beginTransaction();
    try {
        $factura = $this->ejecutarAnulacion($factura, $motivo, $userId);
        DB::commit();
    } catch (Throwable $e) {
        DB::rollBack();
        throw $e;
    }

        $this->sincronizarRutas((int) $factura->cliente_id);

    return $factura->refresh();
}

private function ejecutarAnulacion(Factura $factura, ?string $motivo, ?int $userId): Factura
{
    $userId = $userId ?? Auth::id();
    if (!$userId) {
        throw new OperacionException('No se pudo identificar al usuario que anula la factura.');
    }

    $factura = Factura::with('lineas')->lockForUpdate()->findOrFail($factura->id);

    if ($factura->estado === EstadoFactura::Anulada->value) {
        throw new OperacionException('Esta factura ya está anulada.');
    }
    if ($factura->operacion_id === null) {
        throw new OperacionException('Las facturas migradas del sistema anterior no se pueden anular. Hacé una devolución en su lugar.');
    }
    $limite = now()->subHours(self::HORAS_LIMITE_ANULACION);
    if ($factura->created_at && $factura->created_at->lt($limite)) {
        throw new OperacionException(
            'Esta factura tiene más de ' . self::HORAS_LIMITE_ANULACION . ' horas y ya no se puede anular desde acá. Hacé una devolución en su lugar.'
        );
    }

    if (Abono::where('factura_id', $factura->id)->exists()) {
        throw new OperacionException('Esta factura ya tiene abonos registrados, no se puede anular. Hacé una devolución en su lugar.');
    }
    if (Devolucion::where('factura_id', $factura->id)->exists()) {
        throw new OperacionException('Esta factura ya tiene devoluciones registradas, no se puede anular.');
    }

    $cliente = Cliente::lockForUpdate()->findOrFail($factura->cliente_id);

    $efectoSaldo = $factura->estado === EstadoFactura::Credito->value
        ? $this->c($factura->total)
        : $this->c($factura->saldo_favor_aplicado);

    if ($efectoSaldo !== 0) {
        $cliente->saldo_actual = $this->m($this->c($cliente->saldo_actual) - $efectoSaldo);
        $cliente->save();
    }

    foreach ($factura->lineas as $linea) {
        if ($linea->producto_variante_id) {
            DB::connection('catalogo')->table('producto_variantes')
                ->where('id', $linea->producto_variante_id)
                ->increment('stock', (int) $linea->cantidad);
        }
    }

    $desc = trim((string) $motivo);
    $factura->update([
        'estado' => EstadoFactura::Anulada->value,
        'anulada_at' => now(),
        'anulada_por' => $userId,
        'motivo_anulacion' => $desc !== '' ? mb_substr($desc, 0, 500) : null,
    ]);

    return $factura;
}



    private function ejecutarTraspaso(array $datos): array
    {
        $sucursalId = (int) session('sucursal_id');
        if (!$sucursalId) {
            throw new OperacionException('No hay una sucursal seleccionada.');
        }

        $userId = (int) ($datos['user_id'] ?? Auth::id());
        if (!$userId) {
            throw new OperacionException('No se pudo identificar al administrador que emite el traspaso.');
        }

        $origenId = (int) ($datos['cliente_origen_id'] ?? 0);
        $destinoId = (int) ($datos['cliente_destino_id'] ?? 0);
        if (!$origenId || !$destinoId) {
            throw new OperacionException('Elegí el cliente origen y el cliente destino.');
        }
        if ($origenId === $destinoId) {
            throw new OperacionException('El cliente origen y el destino no pueden ser el mismo.');
        }

        // Orden fijo de bloqueo (por id, menor primero) para evitar interbloqueos
        $idsOrden = [$origenId, $destinoId];
        sort($idsOrden);
        $clientes = Cliente::whereIn('id', $idsOrden)->lockForUpdate()->get()->keyBy('id');
        $origen = $clientes->get($origenId);
        $destino = $clientes->get($destinoId);
        if (!$origen || !$destino) {
            throw new OperacionException('Alguno de los dos clientes no existe en esta sucursal.');
        }

               $saldoOrigen = $this->c($origen->saldo_actual);
        if ($saldoOrigen === 0) {
            throw new OperacionException('El cliente origen no tiene deuda ni saldo a favor para traspasar.');
        }
        if ($saldoOrigen < 0) {
            // Saldo a favor: se pasa al destino (le resta deuda o le queda a favor)
            return $this->ejecutarTraspasoAFavor($sucursalId, $userId, $origen, $destino, -$saldoOrigen);
        }

        $facturaIds = $datos['factura_ids'] ?? null;
        $plan = []; // [factura_id|null, monto_centimos]
        $monto = 0;

        if (!empty($facturaIds)) {
            // Traspaso de facturas específicas
            $facturas = Factura::query()
                ->where('cliente_id', $origen->id)
                ->where('estado', EstadoFactura::Credito->value)
                ->whereIn('id', $facturaIds)
                ->get();

            if ($facturas->count() !== count(array_unique($facturaIds))) {
                throw new OperacionException('Alguna factura elegida no pertenece a este cliente o ya no está activa.');
            }

            foreach ($facturas as $f) {
                $p = $this->pendiente($f);
                if ($p <= 0) {
                    throw new OperacionException("La factura #{$f->id} ya no tiene saldo pendiente.");
                }
                $plan[] = [$f->id, $p];
                $monto += $p;
            }
        } else {
            // Traspaso del saldo total: mismo criterio que el abono automático
            // (residuo antiguo primero, luego facturas nuevas de más vieja a más nueva)
            $monto = $saldoOrigen;

                        $facturas = $this->facturasCreditoAbiertas($origen->id);

            $pendientesPorFactura = [];
            $sumaPendientes = 0;
            foreach ($facturas as $f) {
                $p = $this->pendiente($f);
                if ($p > 0) {
                    $pendientesPorFactura[$f->id] = $p;
                    $sumaPendientes += $p;
                }
            }
            $residuoAntiguo = max(0, $saldoOrigen - $sumaPendientes);

            $resto = $monto;
            if ($residuoAntiguo > 0) {
                $t = min($resto, $residuoAntiguo);
                $plan[] = [null, $t];
                $resto -= $t;
            }
            foreach ($pendientesPorFactura as $facturaId => $p) {
                if ($resto <= 0) {
                    break;
                }
                $t = min($resto, $p);
                $plan[] = [$facturaId, $t];
                $resto -= $t;
            }
            if ($resto > 0) {
                throw new OperacionException('No se pudo repartir el saldo del cliente origen entre sus cuentas.');
            }
        }

        // Máximo de crédito del destino
        $maxDestino = $this->c($destino->maximocredito);
        $saldoDestino = $this->c($destino->saldo_actual);
        if ($maxDestino > 0 && $saldoDestino + $monto > $maxDestino) {
            throw new OperacionException(
                'El cliente destino alcanzaría su máximo de crédito (' . $this->fmt($maxDestino) . '). '
                . 'Ajustá el máximo de crédito de ese cliente en Clientes antes de continuar.'
            );
        }

        // ---- Operación del ORIGEN: se salda lo traspasado ----
        $numeroOrigen = (int) Operacion::where('sucursal_id', $sucursalId)->max('numero') + 1;
        $opOrigen = Operacion::create([
            'sucursal_id' => $sucursalId,
            'cliente_id' => $origen->id,
            'user_id' => $userId,
            'numero' => $numeroOrigen,
            'saldo_inicial' => $this->m($saldoOrigen),
            'saldo_final' => $this->m($saldoOrigen - $monto),
            'tipo' => 'traspaso',
            'traspaso_cliente_id' => $destino->id,
        ]);

        $facturasTocadas = [];
        $actual = $saldoOrigen;
        foreach ($plan as [$facturaId, $m]) {
            Abono::create([
                'operacion_id' => $opOrigen->id,
                'cliente_id' => $origen->id,
                'factura_id' => $facturaId,
                'saldo_inicial' => $this->m($actual),
                'saldo_final' => $this->m($actual - $m),
                'efectivo' => $this->m(0),
                'sinpe' => $this->m(0),
                'monto_abono' => $this->m($m),
            ]);
            $actual -= $m;
            if ($facturaId) {
                $facturasTocadas[] = $facturaId;
            }
        }
        $this->actualizarEstados(array_unique($facturasTocadas));

        $origen->saldo_actual = $this->m($actual);
        $origen->save();
        $opOrigen->update(['saldo_final' => $this->m($actual)]);

        // ---- Operación del DESTINO: recibe la deuda ----
        $numeroDestino = (int) Operacion::where('sucursal_id', $sucursalId)->max('numero') + 1;
        $opDestino = Operacion::create([
            'sucursal_id' => $sucursalId,
            'cliente_id' => $destino->id,
            'user_id' => $userId,
            'numero' => $numeroDestino,
            'saldo_inicial' => $this->m($saldoDestino),
            'saldo_final' => $this->m($saldoDestino + $monto),
            'tipo' => 'traspaso',
            'traspaso_cliente_id' => $origen->id,
        ]);

        Factura::create([
            'operacion_id' => $opDestino->id,
            'sucursal_id' => $sucursalId,
            'cliente_id' => $destino->id,
            'estado' => EstadoFactura::Credito->value,
            'plazo' => 0,
            'montototal' => $this->m($monto),
            'impuesto' => 0,
            'descuento' => 0,
            'flete' => 0,
            'total' => $this->m($monto),
            'pago' => 0,
            'vuelto' => 0,
            'efectivo' => 0,
            'sinpe' => 0,
            'saldo_favor_aplicado' => 0,
        ]);

        $destino->saldo_actual = $this->m($saldoDestino + $monto);
        $destino->save();

        return [$opOrigen, $opDestino];
    }

    /**
     * Traspasa el SALDO A FAVOR del origen al destino.
     *  - Origen: queda en 0 (operación sin abonos ni facturas).
     *  - Destino: si debe, se le abona sin dinero hasta donde alcance (deuda antigua primero,
     *    luego facturas de más vieja a más nueva); lo que sobre, o todo si no debía, queda a favor.
     * @param int $favor saldo a favor del origen en céntimos (positivo)
     * @return array{0: Operacion, 1: Operacion} [operación origen, operación destino]
     */
    private function ejecutarTraspasoAFavor(int $sucursalId, int $userId, Cliente $origen, Cliente $destino, int $favor): array
    {
        $saldoDestino = $this->c($destino->saldo_actual);
        $aplicar = min($favor, max(0, $saldoDestino)); // parte que cae sobre deuda del destino

        $plan = []; // [factura_id|null, monto_centimos]
        if ($aplicar > 0) {
                       $facturas = $this->facturasCreditoAbiertas($destino->id);

            $pendientes = [];
            $suma = 0;
            foreach ($facturas as $f) {
                $p = $this->pendiente($f);
                if ($p > 0) {
                    $pendientes[$f->id] = $p;
                    $suma += $p;
                }
            }
            $residuoAntiguo = max(0, $saldoDestino - $suma);

            $resto = $aplicar;
            if ($residuoAntiguo > 0) {
                $t = min($resto, $residuoAntiguo);
                $plan[] = [null, $t];
                $resto -= $t;
            }
            foreach ($pendientes as $facturaId => $p) {
                if ($resto <= 0) {
                    break;
                }
                $t = min($resto, $p);
                $plan[] = [$facturaId, $t];
                $resto -= $t;
            }
            if ($resto > 0) {
                throw new OperacionException('No se pudo repartir el saldo a favor entre las cuentas del cliente destino.');
            }
        }

        // ---- Operación del ORIGEN: su saldo a favor queda en 0 ----
        $numeroOrigen = (int) Operacion::where('sucursal_id', $sucursalId)->max('numero') + 1;
        $opOrigen = Operacion::create([
            'sucursal_id' => $sucursalId,
            'cliente_id' => $origen->id,
            'user_id' => $userId,
            'numero' => $numeroOrigen,
            'saldo_inicial' => $this->m(-$favor),
            'saldo_final' => $this->m(0),
            'tipo' => 'traspaso',
            'traspaso_cliente_id' => $destino->id,
        ]);
        $origen->saldo_actual = $this->m(0);
        $origen->save();

        // ---- Operación del DESTINO: recibe el saldo a favor ----
        $saldoFinalDestino = $saldoDestino - $favor;
        $numeroDestino = (int) Operacion::where('sucursal_id', $sucursalId)->max('numero') + 1;
        $opDestino = Operacion::create([
            'sucursal_id' => $sucursalId,
            'cliente_id' => $destino->id,
            'user_id' => $userId,
            'numero' => $numeroDestino,
            'saldo_inicial' => $this->m($saldoDestino),
            'saldo_final' => $this->m($saldoFinalDestino),
            'tipo' => 'traspaso',
            'traspaso_cliente_id' => $origen->id,
        ]);

        $facturasTocadas = [];
        $actual = $saldoDestino;
        foreach ($plan as [$facturaId, $m]) {
            Abono::create([
                'operacion_id' => $opDestino->id,
                'cliente_id' => $destino->id,
                'factura_id' => $facturaId,
                'saldo_inicial' => $this->m($actual),
                'saldo_final' => $this->m($actual - $m),
                'efectivo' => $this->m(0),
                'sinpe' => $this->m(0),
                'monto_abono' => $this->m($m),
            ]);
            $actual -= $m;
            if ($facturaId) {
                $facturasTocadas[] = $facturaId;
            }
        }
        $this->actualizarEstados(array_unique($facturasTocadas));

        $destino->saldo_actual = $this->m($saldoFinalDestino);
        $destino->save();

        return [$opOrigen, $opDestino];
    }

    /** Pendiente (en colones) de una factura, para mostrarlo en el selector del traspaso. */
    public function pendienteFactura(Factura $factura): float
    {
        return round($this->pendiente($factura) / 100, 2);
    }



    private function ejecutar(array $datos, array $fotos): Operacion
    {
        $sucursalId = (int) session('sucursal_id');
        if (!$sucursalId) {
            throw new OperacionException('No hay una sucursal seleccionada.');
        }

        $userId = (int) ($datos['user_id'] ?? Auth::id());
        if (!$userId) {
            throw new OperacionException('No se pudo identificar al administrador que emite la operación.');
        }

        // Orden de bloqueos fijo (cliente → sucursal) para evitar interbloqueos
        $cliente = Cliente::lockForUpdate()->find($datos['cliente_id'] ?? null);
        if (!$cliente) {
            throw new OperacionException('El cliente no existe en esta sucursal.');
        }
        $sucursal = Sucursal::lockForUpdate()->findOrFail($sucursalId);
        $mueveStock = $sucursal->canal === 'normal'; // Azur mueve stock, Guana no

        $noAbono = (bool) ($datos['no_abono'] ?? false);
        $noAbonoDescripcion = null;
        if ($noAbono) {
            $desc = trim((string) ($datos['no_abono_descripcion'] ?? ''));
            $noAbonoDescripcion = $desc !== '' ? mb_substr($desc, 0, 500) : null;
        }
        $venta = $datos['venta'] ?? null;
        $abono = $datos['abono'] ?? null;
        $devoluciones = $datos['devoluciones'] ?? [];

        if ($noAbono && ($venta || $abono || $devoluciones)) {
            throw new OperacionException('«No abonó» no se puede combinar con ventas, abonos ni devoluciones.');
        }
        if (!$noAbono && !$venta && !$abono && !$devoluciones) {
            throw new OperacionException('No hay nada para guardar.');
        }

        // Bloque 5 #2 — una factura no puede tener dos devoluciones en la misma visita,
        // ni recibir un abono específico si ya tiene devolución (el abono automático sí se permite)
        $facturasDevueltas = [];
        foreach ($devoluciones as $dev) {
            $fid = (int) ($dev['factura_id'] ?? 0);
            if (in_array($fid, $facturasDevueltas, true)) {
                throw new OperacionException('Una misma factura no puede tener dos devoluciones en la misma visita. Juntá los productos en una sola devolución.');
            }
            $facturasDevueltas[] = $fid;
        }
        if (!empty($abono['factura_id']) && $abono['factura_id'] !== 'venta_actual' && in_array((int) $abono['factura_id'], $facturasDevueltas, true)) {
            throw new OperacionException('Esa factura ya tiene una devolución en esta visita, así que no se le puede abonar directamente. Usá el abono automático.');
        }

        // Ruta: solo se usa si sigue existiendo y es de este cliente y sucursal
        $rutaCliente = null;
        if (!empty($datos['ruta_cliente_id'])) {
            $rc = RutaCliente::with('ruta')->find($datos['ruta_cliente_id']);
            if ($rc && $rc->ruta && (int) $rc->cliente_id === (int) $cliente->id) {
                $rutaCliente = $rc;
            }
        }

        $saldo = $this->c($cliente->saldo_actual);
        $saldoInicial = $saldo;

        $numero = (int) Operacion::where('sucursal_id', $sucursalId)->max('numero') + 1;

        $operacion = Operacion::create([
            'sucursal_id' => $sucursalId,
            'cliente_id' => $cliente->id,
            'user_id' => $userId,
            'ruta_cliente_id' => $rutaCliente?->id,
            'numero' => $numero,
            'saldo_inicial' => $this->m($saldoInicial),
            'saldo_final' => $this->m($saldoInicial),
            'no_abono' => $noAbono,
            'no_abono_descripcion' => $noAbonoDescripcion,
        ]);

        $facturasTocadas = [];

        // 1) Devoluciones
        foreach ($devoluciones as $dev) {
            $saldo -= $this->registrarDevolucion($operacion, $cliente, $mueveStock, $dev, $facturasTocadas);
        }

        // 2) Venta
        $direccion = '';
        $facturaVentaActual = null;
        if ($venta) {
            [$factura, $saldo] = $this->registrarVenta($operacion, $cliente, $sucursal, $mueveStock, $venta, $fotos['venta'] ?? null, $saldo);
            $direccion = trim((string) ($venta['direccion'] ?? ''));
            // Item 13 (reunión) — si la venta de esta visita quedó a crédito, se
            // ofrece como destino directo de abono con el sentinel "venta_actual",
            // porque el front no puede conocer su id todavía (no está guardada).
            if ($factura->estado === EstadoFactura::Credito->value) {
                $facturaVentaActual = $factura;
            }
        }

        // 3) Abono
        if ($abono) {
            $saldo = $this->registrarAbono($operacion, $cliente, $abono, $fotos['abono'] ?? null, $saldo, $facturasTocadas, $facturaVentaActual);
        }

        $this->actualizarEstados(array_unique($facturasTocadas));

        // Saldo del cliente en la misma transacción
        $cliente->saldo_actual = $this->m($saldo);
        if ($direccion !== '' && $direccion !== (string) $cliente->direccion) {
            $cliente->direccion = $direccion;
        }
        $cliente->save();

        $operacion->update(['saldo_final' => $this->m($saldo)]);

        // Ruta: se finaliza en el servidor, en la misma transacción
        if ($rutaCliente) {
            $rutaCliente->update([
                'estado' => $noAbono ? EstadoRutaCliente::NoAbono : EstadoRutaCliente::Finalizado,
            ]);
        }

        return $operacion;
    }

    // ------------------------------------------------------------------
    // Devoluciones
    // ------------------------------------------------------------------

    /** @return int monto devuelto en céntimos */
    private function registrarDevolucion(Operacion $op, Cliente $cliente, bool $mueveStock, array $dev, array &$facturasTocadas): int
    {
        $factura = Factura::with('lineas')->find($dev['factura_id'] ?? null);
        if (!$factura || (int) $factura->cliente_id !== (int) $cliente->id) {
            throw new OperacionException('La factura de la devolución no pertenece a este cliente.');
        }
        if ($factura->estado === EstadoFactura::Anulada->value) {
            throw new OperacionException('No se puede devolver de una factura anulada.');
        }

        $lineasIn = $dev['lineas'] ?? [];
        if (!$lineasIn) {
            throw new OperacionException('Elegí al menos un producto para devolver.');
        }

        $nueva = $factura->operacion_id !== null;
        $motivo = MotivoDevolucion::tryFrom((string) ($dev['motivo'] ?? '')) ?? MotivoDevolucion::NotaCredito;

        // En facturas migradas el descuento está en la cabecera: se reparte en proporción a cada línea
        $brutoFactura = $factura->lineas->sum(fn($l) => $this->c($l->precio_unit) * (int) $l->cantidad);
        $descCabecera = $this->c($factura->descuento);

        $devolucion = Devolucion::create([
            'operacion_id' => $op->id,
            'cliente_id' => $cliente->id,
            'factura_id' => $factura->id,
            'motivo' => $motivo,
            'total_lineas' => count($lineasIn),
            'monto_total' => 0,
            'impuesto' => 0,
            'descuento' => 0,
            'total' => 0,
        ]);

        $suma = 0;
        $sumaDesc = 0;

        foreach ($lineasIn as $li) {
            $linea = $factura->lineas->firstWhere('id', (int) ($li['factura_linea_id'] ?? 0));
            if (!$linea) {
                throw new OperacionException('Un producto de la devolución no pertenece a esa factura.');
            }

            $cant = (float) ($li['cantidad'] ?? 0);
            if ($cant <= 0 || floor($cant) != $cant) {
                throw new OperacionException("La cantidad a devolver de «{$linea->descripcion}» debe ser un entero mayor a 0.");
            }
            $cant = (int) $cant;

            $yaDevuelto = (int) round(DevolucionLinea::where('factura_linea_id', $linea->id)->sum('cantidad'));
            $disponible = (int) $linea->cantidad - $yaDevuelto;
            if ($cant > $disponible) {
                throw new OperacionException("De «{$linea->descripcion}» solo se pueden devolver {$disponible}.");
            }

            $cantLinea = (int) $linea->cantidad;
            $precio = $this->c($linea->precio_unit);
            $bruto = $precio * $cantLinea;
            $descLinea = $this->c($linea->descuento);
            $descProrrateo = ($nueva || $brutoFactura <= 0)
                ? 0
                : (int) round($descCabecera * $bruto / $brutoFactura);

            $netoLinea = max(0, $bruto - $descLinea - $descProrrateo);
            $monto = (int) round($netoLinea * $cant / $cantLinea);
            $brutoDevuelto = $precio * $cant;

            // Solo las ventas nuevas de Azur devuelven producto al stock
                        $regresa = $mueveStock && $nueva && $linea->producto_id !== null && !empty($li['regresa_stock']);
            if ($regresa && !$linea->producto_variante_id) {
                throw new OperacionException("«{$linea->descripcion}» no tiene variante para regresar al stock.");
            }

            DevolucionLinea::create([
                'devolucion_id' => $devolucion->id,
                'producto_id' => $linea->producto_id,
                'producto_variante_id' => $linea->producto_variante_id,
                'factura_linea_id' => $linea->id,
                'descripcion' => $linea->descripcion,
                'cantidad' => $cant,
                'precio_unit' => $linea->precio_unit,
                'precio_total' => $this->m($monto),
                'descuento' => $this->m(max(0, $brutoDevuelto - $monto)),
                'impuesto' => 0,
                'costo' => $linea->costo_unit,
                'regresa_stock' => $regresa,
            ]);

            if ($regresa) {
                $this->stockPendiente[] = [(int) $linea->producto_variante_id, $cant, $linea->descripcion];
            }

            $suma += $monto;
            $sumaDesc += max(0, $brutoDevuelto - $monto);
        }

        $devolucion->update([
            'monto_total' => $this->m($suma),
            'descuento' => $this->m($sumaDesc),
            'total' => $this->m($suma),
        ]);

        $facturasTocadas[] = $factura->id;

        return $suma;
    }

    // ------------------------------------------------------------------
    // Venta
    // ------------------------------------------------------------------

    /** @return array{0: Factura, 1: int} factura creada y saldo en céntimos después de la venta */
    private function registrarVenta(Operacion $op, Cliente $cliente, Sucursal $sucursal, bool $mueveStock, array $venta, ?UploadedFile $foto, int $saldo): array
    {
        $tipo = $venta['tipo'] ?? null;
        if (!in_array($tipo, ['contado', 'credito'], true)) {
            throw new OperacionException('Elegí si la venta es de contado o a crédito.');
        }

        $lineasIn = $venta['lineas'] ?? [];
        if (!$lineasIn) {
            throw new OperacionException('Agregá al menos un producto a la venta.');
        }

        $lineas = [];
        $bruto = 0;
        $desc = 0;

        foreach ($lineasIn as $li) {
                        // Línea libre: producto que no existe en el catálogo. Solo texto, sin stock ni catálogo.
            if (!empty($li['libre'])) {
                [$lineaLibre, $brutoLibre, $descLibre] = $this->lineaLibre($li);
                $lineas[] = $lineaLibre;
                $bruto += $brutoLibre;
                $desc += $descLibre;
                continue;
            }
            $producto = Producto::with('subcategoria.categoria')->find($li['producto_id'] ?? null);
            if (!$producto || !$producto->activo) {
                throw new OperacionException('Un producto de la venta ya no está disponible.');
            }
            if ($producto->subcategoria?->categoria?->canal !== $sucursal->canal) {
                throw new OperacionException("«{$producto->nombre}» no pertenece al catálogo de esta sucursal.");
            }

            $cant = (float) ($li['cantidad'] ?? 0);
            if ($cant < 1 || floor($cant) != $cant) {
                throw new OperacionException("La cantidad de «{$producto->nombre}» debe ser un entero mayor a 0.");
            }
            $cant = (int) $cant;

            // Precio: el del catálogo; solo si el producto no tiene precio se acepta el escrito y se guarda en Inventario
            $precioCatalogo = $producto->precio !== null ? $this->c($producto->precio) : 0;
            if ($precioCatalogo > 0) {
                $precio = $precioCatalogo;
            } else {
                $precio = $this->c($li['precio_unit'] ?? 0);
                if ($precio <= 0) {
                    throw new OperacionException("«{$producto->nombre}» no tiene precio. Escribilo para poder facturarlo.");
                }
                $this->preciosNuevos[$producto->id] = $precio;
            }

            // Variante y stock (solo Azur)
            $varianteId = null;
            if ($mueveStock) {
                $variantes = ProductoVariante::where('producto_id', $producto->id)->get();
                $variante = !empty($li['producto_variante_id'])
                    ? $variantes->firstWhere('id', (int) $li['producto_variante_id'])
                    : ($variantes->count() === 1 ? $variantes->first() : null);
                if (!$variante) {
                    throw new OperacionException("Elegí la variante de «{$producto->nombre}».");
                }
                $varianteId = (int) $variante->id;
                $this->stockPendiente[] = [$varianteId, -$cant, $producto->nombre];
            }

            // Descuento por línea: en colones o en porcentaje; se guarda siempre en colones
            $brutoLinea = $precio * $cant;
            $tipoDesc = $li['descuento_tipo'] ?? 'monto';
            $valorDesc = (float) ($li['descuento'] ?? 0);
            if ($valorDesc < 0) {
                throw new OperacionException("El descuento de «{$producto->nombre}» no puede ser negativo.");
            }
            if ($tipoDesc === 'porcentaje') {
                if ($valorDesc > 100) {
                    throw new OperacionException("El descuento de «{$producto->nombre}» no puede ser mayor a 100%.");
                }
                $dLinea = (int) round($brutoLinea * $valorDesc / 100);
            } else {
                $dLinea = $this->c($valorDesc);
            }
            if ($dLinea > $brutoLinea) {
                throw new OperacionException("El descuento de «{$producto->nombre}» no puede ser mayor al valor de la línea.");
            }

            $bruto += $brutoLinea;
            $desc += $dLinea;

            $lineas[] = [
                'producto_id' => $producto->id,
                'producto_variante_id' => $varianteId,
                'descripcion' => mb_substr($producto->nombre, 0, 255),
                'precio' => $precio,
                'cantidad' => $cant,
                'descuento' => $dLinea,
            ];
        }

        $total = $bruto - $desc;

        if ($tipo === 'credito') {
            $plazo = (int) ($venta['plazo'] ?? 0);
            if (!in_array($plazo, [15, 30, 45], true)) {
                throw new OperacionException('Elegí el plazo del crédito: 15, 30 o 45 días.');
            }

            $max = $this->c($cliente->maximocredito);
            if ($max > 0 && $saldo + $total > $max) {
                throw new OperacionException(
                    'El cliente alcanzó su máximo de crédito (' . $this->fmt($max) . '). Su saldo quedaría en ' . $this->fmt($saldo + $total) . '.'
                );
            }

            $estado = EstadoFactura::Credito;
            $favor = 0;
            $efectivo = $sinpe = $pago = $vuelto = 0;
            $saldoNuevo = $saldo + $total;
            $rutaFoto = null;
        } else {
            // Contado: consume saldo a favor (nunca deuda)
            $plazo = 0;
            $favor = min($total, max(0, -$saldo));
            $pagoRecibir = $total - $favor;

            $efectivo = $this->c($venta['efectivo'] ?? 0);
            $sinpe = $this->c($venta['sinpe'] ?? 0);
            if ($efectivo < 0 || $sinpe < 0) {
                throw new OperacionException('Los montos de pago no pueden ser negativos.');
            }
            if ($sinpe > $pagoRecibir) {
                throw new OperacionException('El monto de sinpe no puede ser mayor a lo que debe pagar (' . $this->fmt($pagoRecibir) . ').');
            }
            if ($efectivo + $sinpe < $pagoRecibir) {
                throw new OperacionException('El pago no alcanza. Faltan ' . $this->fmt($pagoRecibir - $efectivo - $sinpe) . '.');
            }

            $pago = $efectivo + $sinpe;
            $vuelto = $pago - $pagoRecibir; // solo puede salir del efectivo
            $estado = EstadoFactura::Contado;
            $saldoNuevo = $saldo + $favor;
            $rutaFoto = $sinpe > 0 ? $this->guardarFoto($foto, 'ventas') : null;
        }

        $factura = Factura::create([
            'operacion_id' => $op->id,
            'sucursal_id' => $sucursal->id,
            'cliente_id' => $cliente->id,
            'estado' => $estado->value,
            'plazo' => $plazo,
            'montototal' => $this->m($bruto),
            'impuesto' => 0,
            'descuento' => $this->m($desc),
            'flete' => 0,
            'total' => $this->m($total),
            'pago' => $this->m($pago),
            'vuelto' => $this->m($vuelto),
            'efectivo' => $this->m($efectivo),
            'sinpe' => $this->m($sinpe),
            'saldo_favor_aplicado' => $this->m($favor),
            'comprobante_sinpe' => $rutaFoto,
        ]);

        foreach ($lineas as $l) {
            FacturaLinea::create([
                'factura_id' => $factura->id,
                'producto_id' => $l['producto_id'],
                'producto_variante_id' => $l['producto_variante_id'],
                'descripcion' => $l['descripcion'],
                'costo_unit' => 0,
                'precio_unit' => $this->m($l['precio']),
                'cantidad' => $l['cantidad'],
                'descuento' => $this->m($l['descuento']),
            ]);
        }

        return [$factura, $saldoNuevo];
    }

    // ------------------------------------------------------------------
    // Abono
    // ------------------------------------------------------------------

    /** @return int saldo en céntimos después del abono */
    private function registrarAbono(Operacion $op, Cliente $cliente, array $abono, ?UploadedFile $foto, int $saldo, array &$facturasTocadas, ?Factura $facturaVentaActual = null): int
    {
        $efectivo = $this->c($abono['efectivo'] ?? 0);
        $sinpe = $this->c($abono['sinpe'] ?? 0);
        if ($efectivo < 0 || $sinpe < 0) {
            throw new OperacionException('Los montos del abono no pueden ser negativos.');
        }

        $monto = $efectivo + $sinpe;
        if ($monto <= 0) {
            throw new OperacionException('Escribí el monto del abono.');
        }

        $max = max(0, $saldo);
        if ($max === 0) {
            throw new OperacionException('El cliente no tiene deuda para abonar.');
        }
        if ($monto > $max) {
            throw new OperacionException('No se puede abonar más de lo que debe (' . $this->fmt($max) . ').');
        }

        $rutaFoto = $sinpe > 0 ? $this->guardarFoto($foto, 'abonos') : null;

        // Cuentas activas: facturas nuevas de crédito con pendiente, de la más vieja a la más nueva.
        // Si en esta misma visita se acaba de crear una venta a crédito, ya quedó
        // guardada en la BD (dentro de esta misma transacción) por registrarVenta(),
        // así que esta consulta ya la incluye sola.
        $pendientes = [];
        $sumaPendientes = 0;
                $facturas = $this->facturasCreditoAbiertas($cliente->id);
        foreach ($facturas as $f) {
            $p = $this->pendiente($f);
            if ($p > 0) {
                $pendientes[$f->id] = $p;
                $sumaPendientes += $p;
            }
        }
        // Deuda antigua (migrada): lo que el saldo tiene de más respecto a las facturas nuevas
        $residuoAntiguo = max(0, $saldo - $sumaPendientes);

        $facturaIdSolicitado = $abono['factura_id'] ?? null;
        $esVentaActual = $facturaIdSolicitado === 'venta_actual';

        $plan = []; // [factura_id|null, monto]
        if ($esVentaActual || !empty($facturaIdSolicitado)) {
            if ($esVentaActual) {
                if (!$facturaVentaActual) {
                    throw new OperacionException('No hay ninguna venta a crédito confirmada en esta visita para abonarle directamente.');
                }
                $f = $facturaVentaActual;
                $tope = $pendientes[$f->id] ?? 0;
            } else {
                $f = Factura::find($facturaIdSolicitado);
                if (!$f || (int) $f->cliente_id !== (int) $cliente->id) {
                    throw new OperacionException('La factura elegida no pertenece a este cliente.');
                }
               $tope = $pendientes[$f->id] ?? 0;
            }
            if ($tope <= 0) {
                throw new OperacionException('Esa cuenta no tiene deuda pendiente.');
            }
            if ($monto > $tope) {
                throw new OperacionException('Esa cuenta solo debe ' . $this->fmt($tope) . '.');
            }
            $plan[] = [$f->id, $monto];
        } else {
            // Automático: primero la deuda antigua, luego las facturas nuevas de la más vieja a la más nueva
            $resto = $monto;
            if ($residuoAntiguo > 0) {
                $t = min($resto, $residuoAntiguo);
                $plan[] = [null, $t];
                $resto -= $t;
            }
            foreach ($pendientes as $facturaId => $p) {
                if ($resto <= 0) {
                    break;
                }
                $t = min($resto, $p);
                $plan[] = [$facturaId, $t];
                $resto -= $t;
            }
            if ($resto > 0) {
                throw new OperacionException('No se pudo repartir el abono entre las cuentas activas.');
            }
        }

        $efectivoRestante = $efectivo;
        $actual = $saldo;
        foreach ($plan as [$facturaId, $m]) {
            $e = min($efectivoRestante, $m);
            $s = $m - $e;
            $efectivoRestante -= $e;

            Abono::create([
                'operacion_id' => $op->id,
                'cliente_id' => $cliente->id,
                'factura_id' => $facturaId,
                'saldo_inicial' => $this->m($actual),
                'saldo_final' => $this->m($actual - $m),
                'efectivo' => $this->m($e),
                'sinpe' => $this->m($s),
                'comprobante_sinpe' => $s > 0 ? $rutaFoto : null,
                'monto_abono' => $this->m($m),
            ]);

            $actual -= $m;
            if ($facturaId) {
                $facturasTocadas[] = $facturaId;
            }
        }

        return $actual;
    }

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------


    /** @return array{0: array, 1: int, 2: int} [línea, bruto, descuento] en céntimos */
    private function lineaLibre(array $li): array
    {
        $nombre = trim((string) ($li['nombre_libre'] ?? ''));
        if ($nombre === '') {
            throw new OperacionException('Escribí el nombre del producto libre.');
        }
        if (mb_strlen($nombre) > 255) {
            throw new OperacionException('El nombre del producto libre no puede pasar de 255 caracteres.');
        }

        $cant = (float) ($li['cantidad'] ?? 0);
        if ($cant < 1 || floor($cant) != $cant) {
            throw new OperacionException("La cantidad de «{$nombre}» debe ser un entero mayor a 0.");
        }
        $cant = (int) $cant;

        $precio = $this->c($li['precio_unit'] ?? 0);
        if ($precio <= 0) {
            throw new OperacionException("«{$nombre}» necesita un precio mayor a 0.");
        }

        $brutoLinea = $precio * $cant;
        $tipoDesc = $li['descuento_tipo'] ?? 'monto';
        $valorDesc = (float) ($li['descuento'] ?? 0);
        if ($valorDesc < 0) {
            throw new OperacionException("El descuento de «{$nombre}» no puede ser negativo.");
        }
        if ($tipoDesc === 'porcentaje') {
            if ($valorDesc > 100) {
                throw new OperacionException("El descuento de «{$nombre}» no puede ser mayor a 100%.");
            }
            $dLinea = (int) round($brutoLinea * $valorDesc / 100);
        } else {
            $dLinea = $this->c($valorDesc);
        }
        if ($dLinea > $brutoLinea) {
            throw new OperacionException("El descuento de «{$nombre}» no puede ser mayor al valor de la línea.");
        }

        return [[
            'producto_id' => null,
            'producto_variante_id' => null,
            'descripcion' => $nombre,
            'precio' => $precio,
            'cantidad' => $cant,
            'descuento' => $dLinea,
        ], $brutoLinea, $dLinea];
    }

private function facturasCreditoAbiertas(int $clienteId)
{
    return Factura::query()
        ->where('cliente_id', $clienteId)
        ->where('estado', EstadoFactura::Credito->value)
        ->orderByRaw('COALESCE(facturas.fecha, facturas.created_at)')
        ->orderBy('facturas.id')
        ->get();
}
    /** Pendiente (céntimos) de una factura NUEVA: total − abonos − devoluciones. */
   private function pendiente(Factura $f): int
{
    $abonos = Abono::where('factura_id', $f->id);
    $devs = Devolucion::where('factura_id', $f->id);

    if ($f->operacion_id === null) {
        // Migrada: saldo_migrado ya refleja lo abonado/devuelto en el sistema viejo
        $abonos->whereNotNull('operacion_id');
        $devs->whereNotNull('operacion_id');
        $base = $this->c($f->saldo_migrado);
    } else {
        $base = $this->c($f->total);
    }

    return max(0, $base - $this->c($abonos->sum('monto_abono')) - $this->c($devs->sum('total')));
}

    private function actualizarEstados(array $facturaIds): void
    {
        if (!$facturaIds) {
            return;
        }

               $facturas = Factura::query()
            ->whereIn('id', $facturaIds)
            ->whereIn('estado', [EstadoFactura::Credito->value, EstadoFactura::Saldada->value])
            ->get();

        foreach ($facturas as $f) {
            $nuevo = $this->pendiente($f) === 0 ? EstadoFactura::Saldada : EstadoFactura::Credito;
            if ($f->estado !== $nuevo->value) {
                $f->update(['estado' => $nuevo->value]);
            }
        }
    }

    private function guardarFoto(?UploadedFile $foto, string $carpeta): string
    {
        if (!$foto || !$foto->isValid()) {
            throw new OperacionException('Subí la foto del comprobante de sinpe.');
        }
        if (!in_array($foto->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new OperacionException('El comprobante debe ser una imagen JPG, PNG o WEBP.');
        }
        if ($foto->getSize() > 5 * 1024 * 1024) {
            throw new OperacionException('La foto del comprobante no puede pesar más de 5 MB.');
        }

        $ruta = Storage::disk('comprobantes')->putFile($carpeta, $foto);
        if (!$ruta) {
            throw new OperacionException('No se pudo guardar la foto del comprobante. Intentá de nuevo.');
        }

        $this->archivosGuardados[] = $ruta;

        return $ruta;
    }

    /** Stock en la BD del catálogo: primero ingresos (devoluciones), luego salidas (ventas). */
    private function aplicarStock(): void
    {
        $ops = $this->stockPendiente;
        usort($ops, fn($a, $b) => $b[1] <=> $a[1]);

        foreach ($ops as [$varianteId, $delta, $nombre]) {
            $tabla = fn() => DB::connection('catalogo')->table('producto_variantes')->where('id', $varianteId);

            if ($delta > 0) {
                $tabla()->increment('stock', $delta);
            } else {
                $pide = -$delta;
                $filas = $tabla()->where('stock', '>=', $pide)->decrement('stock', $pide);
                if ($filas === 0) {
                    $hay = (int) $tabla()->value('stock');
                    throw new OperacionException("Stock insuficiente de «{$nombre}»: hay {$hay} y se piden {$pide}.");
                }
            }

            $this->stockAplicado[] = [$varianteId, $delta];
        }
    }

    private function revertirStock(): void
    {
        foreach (array_reverse($this->stockAplicado) as [$varianteId, $delta]) {
            try {
                $q = DB::connection('catalogo')->table('producto_variantes')->where('id', $varianteId);
                $delta > 0 ? $q->decrement('stock', $delta) : $q->increment('stock', -$delta);
            } catch (Throwable $e) {
                report($e);
            }
        }
        $this->stockAplicado = [];
    }

    /** Precios escritos al facturar para productos sin precio: quedan guardados en Inventario. */
    private function guardarPreciosNuevos(): void
    {
        foreach ($this->preciosNuevos as $productoId => $centimos) {
            try {
                Producto::where('id', $productoId)
                    ->where(fn($q) => $q->whereNull('precio')->orWhere('precio', 0))
                    ->update(['precio' => $this->m($centimos)]);
            } catch (Throwable $e) {
                report($e);
            }
        }
    }


    /** Mueve al cliente a la ruta (Activos/Cancelados) que corresponde a su saldo. Nunca debe romper la operación ya guardada. */
private function sincronizarRutas(int ...$clienteIds): void
{
    foreach (array_unique($clienteIds) as $id) {
        try {
            app(RutaSaldoService::class)->sincronizar($id);
        } catch (Throwable $e) {
            report($e);
        }
    }
}

private function sacarDeRutas(int ...$clienteIds): void
    {
        foreach (array_unique($clienteIds) as $id) {
            try {
                app(RutaSaldoService::class)->sincronizar($id, true);
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
    private function c($valor): int
    {
        return (int) round(((float) $valor) * 100);
    }

    private function m(int $centimos): string
    {
        return number_format($centimos / 100, 2, '.', '');
    }

    private function fmt(int $centimos): string
    {
        return '₡' . number_format($centimos / 100, 2);
    }
}
