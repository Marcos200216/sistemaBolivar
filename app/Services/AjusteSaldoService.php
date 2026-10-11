<?php
// app/Services/AjusteSaldoService.php

namespace App\Services;

use App\Enums\EstadoFactura;
use App\Exceptions\OperacionException;
use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Operacion;
use App\Models\Sucursal;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Ajustes de monto hechos por un superadmin sobre clientes de CUALQUIER sucursal.
 * Nada depende de la sucursal en sesión: todo va con withoutGlobalScopes().
 *
 *  - bajarTotal:     baja la deuda del cliente repartiendo de la factura más vieja a la más nueva.
 *  - editarFactura:  cambia el pendiente de UNA factura (bajar = rebaja sin dinero; subir = sube la base).
 *  - anularFactura / anularTodas: la factura queda 'anulada' y deja de contar en el saldo.
 *
 * Reglas:
 *  - Toda escritura es una transacción y deja una fila en ajustes_saldo (motivo obligatorio).
 *  - Bajar crea una Operacion tipo 'rebaja' con Abonos sin dinero (efectivo = sinpe = 0).
 *  - Anular NO mueve stock ni borra abonos ya cobrados.
 *  - No envía nada por WhatsApp.
 *  - Montos en céntimos (enteros) por dentro; en colones hacia afuera.
 */
class AjusteSaldoService
{
    // ------------------------------------------------------------------
    // Lectura (para la pantalla)
    // ------------------------------------------------------------------

    /** Cliente + facturas de crédito abiertas (más vieja primero) con su pendiente. */
    public function detalleCliente(int $clienteId): array
    {
        $cliente = Cliente::withoutGlobalScopes()->findOrFail($clienteId);
        $facturas = $this->facturasAbiertas($clienteId);
        $nums = $this->numerosOperacion($facturas);
        $servicio = app(OperacionService::class);

        $lista = [];
        $suma = 0;
        foreach ($facturas as $f) {
            $p = $this->c($servicio->pendienteFactura($f));
            if ($p <= 0) {
                continue;
            }
            $suma += $p;
            $lista[] = [
                'id' => $f->id,
                'numero' => $this->numeroFactura($f, $nums),
                'migrada' => $f->operacion_id === null,
                'fecha' => ($f->fecha ?? $f->created_at)?->format('d/m/Y'),
                'total' => (float) $f->total,
                'pendiente' => $this->col($p),
            ];
        }

        $saldo = $this->c($cliente->saldo_actual);

        return [
            'cliente' => [
                'id' => $cliente->id,
                'codigo' => $cliente->codigo,
                'nombre' => $cliente->nombre,
                'sucursal_id' => $cliente->sucursal_id,
                'sucursal' => Sucursal::find($cliente->sucursal_id)?->nombre,
                'saldo' => $this->col($saldo),
            ],
            'facturas' => $lista,
            'deuda_sin_factura' => $this->col(max(0, $saldo - $suma)),
            // true = el saldo no coincide con la suma de sus facturas: hay que revisarlo antes de ajustar
            'descuadrado' => $saldo < $suma,
        ];
    }

    /** Vista previa del reparto de una baja de $monto (colones). No escribe nada. */
    public function simularBaja(int $clienteId, float $monto): array
    {
        $montoC = $this->c($monto);
        [, $saldo, $lineas] = $this->planBaja($clienteId, $montoC, false);

        return [
            'saldo_antes' => $this->col($saldo),
            'saldo_despues' => $this->col($saldo - $montoC),
            'lineas' => array_map(fn ($l) => [
                'factura_id' => $l['factura_id'],
                'numero' => $l['numero'],
                'etiqueta' => $l['etiqueta'],
                'antes' => $this->col($l['antes']),
                'rebaja' => $this->col($l['rebaja']),
                'despues' => $this->col($l['despues']),
            ], $lineas),
        ];
    }

    // ------------------------------------------------------------------
    // Escritura
    // ------------------------------------------------------------------

    /** Baja la deuda total del cliente en $monto (colones), de la más vieja a la más nueva. */
    public function bajarTotal(int $clienteId, float $monto, string $motivo, int $userId): array
    {
        $motivo = $this->motivoValido($motivo);
        $montoC = $this->c($monto);

        $resultado = DB::transaction(function () use ($clienteId, $montoC, $motivo, $userId) {
            [$cliente, $saldo, $lineas] = $this->planBaja($clienteId, $montoC, true);
            $saldoFinal = $saldo - $montoC;

            $operacion = $this->crearOperacionRebaja($cliente, $userId, $saldo, $saldoFinal);

            $actual = $saldo;
            $tocadas = [];
            $reparto = [];
            foreach ($lineas as $l) {
                if ($l['rebaja'] <= 0) {
                    continue;
                }
                $this->crearAbonoRebaja($operacion, $cliente, $l['factura_id'], $actual, $l['rebaja']);
                $actual -= $l['rebaja'];
                if ($l['factura_id']) {
                    $tocadas[] = $l['factura_id'];
                }
                $reparto[] = [
                    'factura_id' => $l['factura_id'],
                    'numero' => $l['numero'],
                    'antes' => $this->col($l['antes']),
                    'rebaja' => $this->col($l['rebaja']),
                    'despues' => $this->col($l['despues']),
                ];
            }
            $this->actualizarEstados($tocadas);

            $cliente->saldo_actual = $this->m($saldoFinal);
            $cliente->save();

            $this->auditar($cliente, $userId, 'bajar_total', $motivo, $saldo, $saldoFinal, null, $operacion->id, $saldo, $saldoFinal, [
                'monto_bajado' => $this->col($montoC),
                'reparto' => $reparto,
            ]);

            return [
                'operacion_id' => $operacion->id,
                'numero' => $operacion->numero,
                'saldo_antes' => $this->col($saldo),
                'saldo_despues' => $this->col($saldoFinal),
            ];
        });

        $this->sincronizarRuta($clienteId);

        return $resultado;
    }

    /**
     * Deja el pendiente de UNA factura de crédito en $nuevoPendiente (colones).
     * Bajar: rebaja sin dinero. Subir: sube la base de la factura (total en las nuevas,
     * saldo_migrado en las migradas) y el saldo del cliente en el mismo monto.
     */
    public function editarFactura(int $facturaId, float $nuevoPendiente, string $motivo, int $userId): array
    {
        $motivo = $this->motivoValido($motivo);
        $nuevoC = $this->c($nuevoPendiente);
        if ($nuevoC < 0) {
            throw new OperacionException('El pendiente no puede ser negativo.');
        }

        $resultado = DB::transaction(function () use ($facturaId, $nuevoC, $motivo, $userId) {
            $f = Factura::withoutGlobalScopes()->lockForUpdate()->findOrFail($facturaId);
            $cliente = Cliente::withoutGlobalScopes()->lockForUpdate()->findOrFail($f->cliente_id);

            if ($f->estado !== EstadoFactura::Credito->value) {
                throw new OperacionException('Solo se puede editar una factura de crédito abierta.');
            }

            $p = $this->c(app(OperacionService::class)->pendienteFactura($f));
            if ($nuevoC === $p) {
                throw new OperacionException('El pendiente ya es ese monto: no hay nada que cambiar.');
            }

            $saldo = $this->c($cliente->saldo_actual);
            $nums = $this->numerosOperacion(collect([$f]));
            $detalle = ['numero' => $this->numeroFactura($f, $nums), 'migrada' => $f->operacion_id === null];
            $operacionId = null;

            if ($nuevoC < $p) {
                $m = $p - $nuevoC;
                if ($saldo < $m) {
                    throw new OperacionException('El saldo del cliente (' . $this->fmt($saldo) . ') es menor que lo que se quiere bajar. Hay que revisarlo antes.');
                }
                $saldoFinal = $saldo - $m;

                $operacion = $this->crearOperacionRebaja($cliente, $userId, $saldo, $saldoFinal);
                $this->crearAbonoRebaja($operacion, $cliente, $f->id, $saldo, $m);
                $this->actualizarEstados([$f->id]);
                $operacionId = $operacion->id;
                $accion = 'bajar_factura';
            } else {
                $m = $nuevoC - $p;
                $saldoFinal = $saldo + $m;

                // saldo_migrado no está en $fillable de Factura: se escribe directo
                if ($f->operacion_id === null) {
                    DB::table('facturas')->where('id', $f->id)->update([
                        'saldo_migrado' => $this->m($this->c($f->saldo_migrado) + $m),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('facturas')->where('id', $f->id)->update([
                        'total' => $this->m($this->c($f->total) + $m),
                        'montototal' => $this->m($this->c($f->montototal) + $m),
                        'updated_at' => now(),
                    ]);
                }
                $accion = 'subir_factura';
            }

            $cliente->saldo_actual = $this->m($saldoFinal);
            $cliente->save();

            $this->auditar($cliente, $userId, $accion, $motivo, $saldo, $saldoFinal, $f->id, $operacionId, $p, $nuevoC, $detalle);

            return [
                'accion' => $accion,
                'operacion_id' => $operacionId,
                'pendiente_antes' => $this->col($p),
                'pendiente_despues' => $this->col($nuevoC),
                'saldo_antes' => $this->col($saldo),
                'saldo_despues' => $this->col($saldoFinal),
            ];
        });

        $this->sincronizarRuta((int) Factura::withoutGlobalScopes()->whereKey($facturaId)->value('cliente_id'));

        return $resultado;
    }

    /** Anula una factura de crédito abierta: queda 'anulada' y su pendiente sale del saldo del cliente. */
    public function anularFactura(int $facturaId, string $motivo, int $userId): array
    {
        $motivo = $this->motivoValido($motivo);

        $resultado = DB::transaction(function () use ($facturaId, $motivo, $userId) {
            $f = Factura::withoutGlobalScopes()->lockForUpdate()->findOrFail($facturaId);
            $cliente = Cliente::withoutGlobalScopes()->lockForUpdate()->findOrFail($f->cliente_id);

            $saldoAntes = $this->c($cliente->saldo_actual);
            $nums = $this->numerosOperacion(collect([$f]));
            $numero = $this->numeroFactura($f, $nums);

            $p = $this->anularUna($f, $cliente, $userId, $motivo);
            $saldoDespues = $this->c($cliente->saldo_actual);

            $this->auditar($cliente, $userId, 'anular_factura', $motivo, $saldoAntes, $saldoDespues, $f->id, null, $p, 0, [
                'numero' => $numero,
                'migrada' => $f->operacion_id === null,
            ]);

            return [
                'cliente_id' => $cliente->id,
                'pendiente_anulado' => $this->col($p),
                'saldo_antes' => $this->col($saldoAntes),
                'saldo_despues' => $this->col($saldoDespues),
            ];
        });

        $this->sincronizarRuta($resultado['cliente_id']);

        return $resultado;
    }

    /**
     * Anula TODAS las facturas de crédito abiertas del cliente. Si le queda deuda sin factura
     * (saldo mayor que la suma de sus facturas), esa deuda no se toca: se baja con bajarTotal().
     */
    public function anularTodas(int $clienteId, string $motivo, int $userId): array
    {
        $motivo = $this->motivoValido($motivo);

        $resultado = DB::transaction(function () use ($clienteId, $motivo, $userId) {
            $cliente = Cliente::withoutGlobalScopes()->lockForUpdate()->findOrFail($clienteId);
            $facturas = $this->facturasAbiertas($clienteId, true);
            if ($facturas->isEmpty()) {
                throw new OperacionException('El cliente no tiene facturas de crédito abiertas.');
            }

            $nums = $this->numerosOperacion($facturas);
            $saldoAntes = $this->c($cliente->saldo_actual);

            $total = 0;
            $detalle = [];
            foreach ($facturas as $f) {
                $p = $this->anularUna($f, $cliente, $userId, $motivo);
                $total += $p;
                $detalle[] = [
                    'factura_id' => $f->id,
                    'numero' => $this->numeroFactura($f, $nums),
                    'pendiente' => $this->col($p),
                ];
            }
            $saldoDespues = $this->c($cliente->saldo_actual);

            $this->auditar($cliente, $userId, 'anular_todas', $motivo, $saldoAntes, $saldoDespues, null, null, $total, 0, [
                'facturas' => $detalle,
            ]);

            return [
                'facturas_anuladas' => count($detalle),
                'pendiente_anulado' => $this->col($total),
                'saldo_antes' => $this->col($saldoAntes),
                'saldo_despues' => $this->col($saldoDespues),
                'queda_sin_factura' => $this->col(max(0, $saldoDespues)),
            ];
        });

        $this->sincronizarRuta($clienteId);

        return $resultado;
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    /**
     * Calcula el reparto de una baja: primero la deuda sin factura (si hay), luego las
     * facturas de la más vieja a la más nueva (mismo orden que el abono automático).
     * Con $bloquear = true toma locks (para usar dentro de la transacción).
     *
     * @return array{0: Cliente, 1: int, 2: array} [cliente, saldo en céntimos, líneas en céntimos]
     */
    private function planBaja(int $clienteId, int $montoC, bool $bloquear): array
    {
        $cliente = Cliente::withoutGlobalScopes()
            ->when($bloquear, fn ($q) => $q->lockForUpdate())
            ->findOrFail($clienteId);

        $saldo = $this->c($cliente->saldo_actual);
        if ($montoC <= 0) {
            throw new OperacionException('Escribí un monto mayor a 0.');
        }
        if ($saldo <= 0) {
            throw new OperacionException('El cliente no tiene deuda para bajar.');
        }
        if ($montoC > $saldo) {
            throw new OperacionException('No se puede bajar más de lo que debe (' . $this->fmt($saldo) . ').');
        }

        $facturas = $this->facturasAbiertas($clienteId, $bloquear);
        $nums = $this->numerosOperacion($facturas);
        $servicio = app(OperacionService::class);

        $abiertas = [];
        $suma = 0;
        foreach ($facturas as $f) {
            $p = $this->c($servicio->pendienteFactura($f));
            if ($p > 0) {
                $abiertas[] = [$f, $p];
                $suma += $p;
            }
        }

        if ($saldo < $suma) {
            throw new OperacionException(
                'El saldo del cliente (' . $this->fmt($saldo) . ') es menor que la suma de sus facturas abiertas ('
                . $this->fmt($suma) . '). Hay que revisarlo antes de ajustar.'
            );
        }

        $residuo = $saldo - $suma; // deuda sin factura
        $resto = $montoC;
        $lineas = [];

        if ($residuo > 0) {
            $t = min($resto, $residuo);
            $lineas[] = [
                'factura_id' => null,
                'numero' => null,
                'etiqueta' => 'Deuda sin factura',
                'antes' => $residuo,
                'rebaja' => $t,
                'despues' => $residuo - $t,
            ];
            $resto -= $t;
        }

        foreach ($abiertas as [$f, $p]) {
            $t = min($resto, $p);
            $numero = $this->numeroFactura($f, $nums);
            $lineas[] = [
                'factura_id' => $f->id,
                'numero' => $numero,
                'etiqueta' => $numero ? 'Factura N° ' . $numero : 'Factura',
                'antes' => $p,
                'rebaja' => $t,
                'despues' => $p - $t,
            ];
            $resto -= $t;
        }

        if ($resto > 0) {
            throw new OperacionException('No se pudo repartir la baja entre las cuentas del cliente.');
        }

        return [$cliente, $saldo, $lineas];
    }

    /** Anula una factura y baja su pendiente del saldo del cliente (sin tocar stock ni abonos). Devuelve el pendiente en céntimos. */
    private function anularUna(Factura $f, Cliente $cliente, int $userId, string $motivo): int
    {
        if ($f->estado !== EstadoFactura::Credito->value) {
            throw new OperacionException('Solo se puede anular una factura de crédito abierta.');
        }

        $p = $this->c(app(OperacionService::class)->pendienteFactura($f));
        $saldo = $this->c($cliente->saldo_actual);
        if ($p > $saldo) {
            throw new OperacionException('El saldo del cliente (' . $this->fmt($saldo) . ') es menor que el pendiente de la factura (' . $this->fmt($p) . '). Hay que revisarlo antes.');
        }

        $f->update([
            'estado' => EstadoFactura::Anulada->value,
            'anulada_at' => now(),
            'anulada_por' => $userId,
            'motivo_anulacion' => $motivo,
        ]);

        $cliente->saldo_actual = $this->m($saldo - $p);
        $cliente->save();

        return $p;
    }

    /** Crea la Operacion tipo 'rebaja' en la sucursal DEL CLIENTE (no la de la sesión). */
    private function crearOperacionRebaja(Cliente $cliente, int $userId, int $saldoInicial, int $saldoFinal): Operacion
    {
        // Mismo orden de bloqueo que OperacionService (cliente → sucursal) para el consecutivo
        Sucursal::lockForUpdate()->findOrFail($cliente->sucursal_id);

        $numero = (int) Operacion::withoutGlobalScopes()->where('sucursal_id', $cliente->sucursal_id)->max('numero') + 1;

        return Operacion::create([
            'sucursal_id' => $cliente->sucursal_id,
            'cliente_id' => $cliente->id,
            'user_id' => $userId,
            'numero' => $numero,
            'saldo_inicial' => $this->m($saldoInicial),
            'saldo_final' => $this->m($saldoFinal),
            'no_abono' => false,
            'tipo' => 'rebaja',
            'fecha' => now(),
        ]);
    }

    private function crearAbonoRebaja(Operacion $op, Cliente $cliente, ?int $facturaId, int $saldoInicial, int $monto): void
    {
        Abono::create([
            'operacion_id' => $op->id,
            'cliente_id' => $cliente->id,
            'factura_id' => $facturaId,
            'saldo_inicial' => $this->m($saldoInicial),
            'saldo_final' => $this->m($saldoInicial - $monto),
            'efectivo' => $this->m(0),
            'sinpe' => $this->m(0),
            'monto_abono' => $this->m($monto),
            'fecha' => now(),
        ]);
    }

    /** Pasa a 'saldada' las facturas de crédito cuyo pendiente llegó a 0. */
    private function actualizarEstados(array $facturaIds): void
    {
        if (!$facturaIds) {
            return;
        }

        $servicio = app(OperacionService::class);
        $facturas = Factura::withoutGlobalScopes()
            ->whereIn('id', array_unique($facturaIds))
            ->where('estado', EstadoFactura::Credito->value)
            ->get();

        foreach ($facturas as $f) {
            if ($this->c($servicio->pendienteFactura($f)) === 0) {
                $f->update(['estado' => EstadoFactura::Saldada->value]);
            }
        }
    }

    private function auditar(
        Cliente $cliente,
        int $userId,
        string $accion,
        string $motivo,
        int $saldoAntes,
        int $saldoDespues,
        ?int $facturaId,
        ?int $operacionId,
        ?int $montoAntes,
        ?int $montoDespues,
        array $detalle = []
    ): void {
        DB::table('ajustes_saldo')->insert([
            'user_id' => $userId,
            'sucursal_id' => $cliente->sucursal_id,
            'cliente_id' => $cliente->id,
            'cliente_nombre' => mb_substr((string) $cliente->nombre, 0, 255),
            'factura_id' => $facturaId,
            'operacion_id' => $operacionId,
            'accion' => $accion,
            'monto_antes' => $montoAntes === null ? null : $this->m($montoAntes),
            'monto_despues' => $montoDespues === null ? null : $this->m($montoDespues),
            'saldo_cliente_antes' => $this->m($saldoAntes),
            'saldo_cliente_despues' => $this->m($saldoDespues),
            'detalle' => $detalle ? json_encode($detalle, JSON_UNESCAPED_UNICODE) : null,
            'motivo' => $motivo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** Facturas de crédito del cliente, de la más vieja a la más nueva (mismo orden que el abono automático). */
    private function facturasAbiertas(int $clienteId, bool $bloquear = false)
    {
        return Factura::withoutGlobalScopes()
            ->where('cliente_id', $clienteId)
            ->where('estado', EstadoFactura::Credito->value)
            ->orderByRaw('COALESCE(facturas.fecha, facturas.created_at)')
            ->orderBy('facturas.id')
            ->when($bloquear, fn ($q) => $q->lockForUpdate())
            ->get();
    }

    /** operacion_id => numero, para mostrar el N° de las facturas nuevas. */
    private function numerosOperacion($facturas): array
    {
        $ids = collect($facturas)->pluck('operacion_id')->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        return Operacion::withoutGlobalScopes()->whereIn('id', $ids)->pluck('numero', 'id')->all();
    }

    /** N° visible: el de la operación (nuevas) o el del sistema anterior (migradas). */
    private function numeroFactura(Factura $f, array $nums)
    {
        return $f->operacion_id ? ($nums[$f->operacion_id] ?? null) : $f->factura_id_legacy;
    }

    private function motivoValido(string $motivo): string
    {
        $motivo = trim($motivo);
        if (mb_strlen($motivo) < 3) {
            throw new OperacionException('Escribí el motivo del ajuste (mínimo 3 letras).');
        }

        return mb_substr($motivo, 0, 500);
    }

    /** Mueve al cliente a la ruta (Activos/Cancelados) que corresponde a su saldo. Nunca debe romper lo ya guardado. */
    private function sincronizarRuta(int $clienteId): void
    {
        try {
            app(RutaSaldoService::class)->sincronizar($clienteId);
        } catch (Throwable $e) {
            report($e);
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

    private function col(int $centimos): float
    {
        return round($centimos / 100, 2);
    }

    private function fmt(int $centimos): string
    {
        return '₡' . number_format($centimos / 100, 2);
    }
}