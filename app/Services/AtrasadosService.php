<?php
// app/Services/AtrasadosService.php

namespace App\Services;

use App\Enums\EstadoFactura;
use App\Models\Abono;
use App\Models\Factura;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Clientes con 4 semanas o más sin abonar. La referencia es la fecha MÁS RECIENTE entre
 * el último abono (los migrados usan `fecha`, que es la fecha real; los nuevos usan
 * created_at) y la factura de crédito nueva pendiente más vieja. Sin rastro de ninguna,
 * se usa created_at del cliente.
 * No lee la BD del sistema viejo: los abonos migrados viven en la tabla `abonos`.
 * Requiere sucursal en sesión (Factura::nuevas() usa el Global Scope).
 */
class AtrasadosService
{
    public const SEMANAS_LIMITE = 4;

    /**
     * @param  Collection  $clientes  Modelos Cliente con id y created_at
     * @return Collection  cliente_id => Carbon (fecha de referencia), solo los atrasados,
     *                     del más antiguo al más reciente
     */
    public function referencias(Collection $clientes): Collection
    {
        $limite = now()->subWeeks(self::SEMANAS_LIMITE);
        $atrasados = collect();

        foreach ($clientes->chunk(500) as $grupo) {
            $ids = $grupo->pluck('id')->values();

                        $ultimosAbonos = Abono::whereIn('cliente_id', $ids)
                // Un registro en cero es una visita sin pago, no un abono
                ->where(fn ($w) => $w->where('monto_abono', '<>', 0)->orWhere('efectivo', '<>', 0)->orWhere('sinpe', '<>', 0))
                ->selectRaw('cliente_id, MAX(CASE WHEN operacion_id IS NULL THEN fecha ELSE created_at END) as ultimo')
                ->groupBy('cliente_id')
                ->pluck('ultimo', 'cliente_id');

            $primeraFactura = Factura::nuevas()
                ->whereIn('cliente_id', $ids)
                ->where('estado', EstadoFactura::Credito->value)
                ->selectRaw('cliente_id, MIN(created_at) as primera')
                ->groupBy('cliente_id')
                ->pluck('primera', 'cliente_id');

            foreach ($grupo as $cliente) {
                $id = $cliente->id;

                $candidatas = array_filter([
                    isset($ultimosAbonos[$id]) ? Carbon::parse($ultimosAbonos[$id]) : null,
                    isset($primeraFactura[$id]) ? Carbon::parse($primeraFactura[$id]) : null,
                ]);

                $referencia = $candidatas ? max($candidatas) : $cliente->created_at;

                if ($referencia && Carbon::parse($referencia)->lt($limite)) {
                    $atrasados[$id] = Carbon::parse($referencia);
                }
            }
        }

        return $atrasados->sort();
    }
}