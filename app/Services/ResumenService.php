<?php
// app/Services/ResumenService.php

namespace App\Services;

use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Devolucion;
use App\Models\Factura;
use App\Models\Gasto;
use App\Models\Operacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cálculos de resumen compartidos por Reportes y Dashboard.
 * Query builder puro: no depende del Global Scope, la sucursal se pasa explícita
 * ($sel = id de sucursal en string, o 'todas').
 */
class ResumenService
{
    public function resumen(string $sel, ?string $desde, ?string $hasta): array
    {
        $c = $this->t(Cliente::class);
        $f = $this->t(Factura::class);
        $a = $this->t(Abono::class);
        $dv = $this->t(Devolucion::class);
        $o = $this->t(Operacion::class);
        $g = $this->t(Gasto::class);
        $co = $this->t(Compra::class);

        // Vendido: sin anuladas y sin las facturas que crea un traspaso (no son ventas reales)
        $q = DB::table($f)->whereNotNull("$f.operacion_id")->where("$f.estado", '!=', 'anulada')
            ->whereNotIn("$f.operacion_id", fn ($sub) => $sub->select("$o.id")->from($o)->where("$o.tipo", 'traspaso'));
        $this->porSucursal($q, $sel, "$f.sucursal_id");
        $this->porFecha($q, "$f.created_at", $desde, $hasta);
        $vendido = $q->sum("$f.total");

        $q = DB::table($a)->join($c, "$c.id", '=', "$a.cliente_id");
        $this->porSucursal($q, $sel, "$c.sucursal_id");
        $this->porFecha($q, "$a.fecha", $desde, $hasta);
        // Los abonos de traspaso no son plata cobrada (efectivo y sinpe en 0)
        $this->sinTraspasos($q, "$a.operacion_id", $o);
        $abonado = (clone $q)->sum("$a.monto_abono");
        $abonadoEfectivo = (clone $q)->sum("$a.efectivo");
        $abonadoSinpe = (clone $q)->sum("$a.sinpe");

        $q = DB::table($dv)->join($c, "$c.id", '=', "$dv.cliente_id");
        $this->porSucursal($q, $sel, "$c.sucursal_id");
        $this->porFecha($q, "$dv.fecha", $desde, $hasta);
        $devuelto = $q->sum("$dv.total");

        $q = DB::table($o);
        $this->porSucursal($q, $sel, "$o.sucursal_id");
        $this->porFecha($q, "$o.fecha", $desde, $hasta);
        // Un traspaso no es una visita. El tipo de las operaciones normales puede ser nulo.
        $q->where(fn ($w) => $w->whereNull("$o.tipo")->orWhere("$o.tipo", '!=', 'traspaso'));
        $operaciones = $q->count();

        $q = DB::table($g);
        $this->porSucursal($q, $sel, "$g.sucursal_id");
        $this->porFecha($q, "$g.fecha", $desde, $hasta);
        $gastos = $q->sum("$g.monto");

        $q = DB::table($co);
        $this->porSucursal($q, $sel, "$co.sucursal_id");
        $this->porFecha($q, "$co.fecha", $desde, $hasta);
        $compras = $q->sum("$co.monto_total");

        // Foto de HOY: no se filtra por fecha.
        $q = DB::table($c)->where("$c.saldo_actual", '>', 0);
        $this->porSucursal($q, $sel, "$c.sucursal_id");
        $saldo = $q->sum("$c.saldo_actual");

        return [
            'totalVendido' => (float) $vendido,
            'totalAbonado' => (float) $abonado,
            'abonadoEfectivo' => (float) $abonadoEfectivo,
            'abonadoSinpe' => (float) $abonadoSinpe,
            'totalDevuelto' => (float) $devuelto,
            'cantidadOperaciones' => (int) $operaciones,
            'totalGastos' => (float) $gastos,
            'totalCompras' => (float) $compras,
            'saldoPendiente' => (float) $saldo,
        ];
    }

    /**
     * Ventas y abonos por día, de $desde a $hasta (ambos inclusive, 'Y-m-d').
     * Devuelve una fila por día, incluso los que no tuvieron movimiento.
     *
     * @return array<int, array{fecha:string, etq:string, ventas:float, abonos:float}>
     */
    public function serieDiaria(string $sel, string $desde, string $hasta): array
    {
        $c = $this->t(Cliente::class);
        $f = $this->t(Factura::class);
        $a = $this->t(Abono::class);
        $o = $this->t(Operacion::class);

        $q = DB::table($f)->whereNotNull("$f.operacion_id")->where("$f.estado", '!=', 'anulada')
            ->whereNotIn("$f.operacion_id", fn ($sub) => $sub->select("$o.id")->from($o)->where("$o.tipo", 'traspaso'));
        $this->porSucursal($q, $sel, "$f.sucursal_id");
        $this->porFecha($q, "$f.created_at", $desde, $hasta);
        $ventas = $q->selectRaw("DATE($f.created_at) as d, SUM($f.total) as t")
            ->groupBy('d')->pluck('t', 'd');

        $q = DB::table($a)->join($c, "$c.id", '=', "$a.cliente_id");
        $this->porSucursal($q, $sel, "$c.sucursal_id");
        $this->porFecha($q, "$a.fecha", $desde, $hasta);
        $this->sinTraspasos($q, "$a.operacion_id", $o);
        $abonos = $q->selectRaw("DATE($a.fecha) as d, SUM($a.monto_abono) as t")
            ->groupBy('d')->pluck('t', 'd');

        $serie = [];
        for ($dia = Carbon::parse($desde); $dia->lte(Carbon::parse($hasta)); $dia->addDay()) {
            $k = $dia->toDateString();
            $serie[] = [
                'fecha' => $k,
                'etq' => $dia->format('d/m'),
                'ventas' => (float) ($ventas[$k] ?? 0),
                'abonos' => (float) ($abonos[$k] ?? 0),
            ];
        }

        return $serie;
    }

    /**
     * Quita las filas cuya operación es un traspaso. Los abonos migrados tienen
     * operacion_id nulo y deben quedarse (un NOT IN solo los descartaría).
     */
    private function sinTraspasos($q, string $colOperacionId, string $tablaOperaciones): void
    {
        $q->where(fn ($w) => $w->whereNull($colOperacionId)
            ->orWhereNotIn($colOperacionId, fn ($sub) => $sub->select("$tablaOperaciones.id")->from($tablaOperaciones)->where("$tablaOperaciones.tipo", 'traspaso')));
    }

    private function t(string $modelo): string
    {
        return (new $modelo)->getTable();
    }

    private function porSucursal($q, string $sel, string $col): void
    {
        if ($sel !== 'todas') {
            $q->where($col, (int) $sel);
        }
    }

    private function porFecha($q, string $col, ?string $desde, ?string $hasta): void
    {
        if ($desde) {
            $q->whereDate($col, '>=', $desde);
        }
        if ($hasta) {
            $q->whereDate($col, '<=', $hasta);
        }
    }
}