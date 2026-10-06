<?php
// app/Http/Controllers/ReporteController.php

namespace App\Http\Controllers;

use App\Exports\ReporteExport;
use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Factura;
use App\Models\FacturaLinea;
use App\Models\Gasto;
use App\Models\Operacion;
use App\Models\Proveedor;
use App\Models\ReporteRuta;
use App\Models\Sucursal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Devolucion;
use App\Models\User;
use App\Services\AtrasadosService;
use Illuminate\Pagination\LengthAwarePaginator;

class ReporteController extends Controller
{
       private const TIPOS = [
        'ventas' => 'Ventas',
        'abonos' => 'Abonos',
        'gastos' => 'Gastos',
        'compras' => 'Compras',
        'inventario' => 'Inventario',
        'rutas' => 'Rutas',
        'cancelados' => 'Cancelados',
        'atrasados' => 'Atrasados',
    ];

    private const COLUMNAS_ATRASADOS = [
        ['key' => 'codigo', 'label' => 'Código', 'tipo' => 'text'],
        ['key' => 'cliente', 'label' => 'Cliente', 'tipo' => 'text'],
        ['key' => 'telefono', 'label' => 'Teléfono', 'tipo' => 'text'],
        ['key' => 'saldo', 'label' => 'Saldo', 'tipo' => 'money'],
        ['key' => 'dias', 'label' => 'Días sin abonar', 'tipo' => 'num'],
    ];

    // ------------------------------------------------------------------
    // PANTALLA DE ENTRADA: solo tarjetas
    // ------------------------------------------------------------------

    public function index(Request $request)
    {
        $ctx = $this->contexto($request, 'ventas');

        return view('admin.reportes', [
            'tipos' => self::TIPOS,
            'nombreSel' => $this->nombreSeleccion($ctx),
        ]);
    }

    // ------------------------------------------------------------------
    // PANTALLA DE UN REPORTE
    // ------------------------------------------------------------------

    public function ver(Request $request, string $tipo)
    {
        abort_unless(array_key_exists($tipo, self::TIPOS), 404);

        $ctx = $this->contexto($request, $tipo);

        $vista = $ctx + [
            'tipos' => self::TIPOS,
            'nombreSel' => $this->nombreSeleccion($ctx),
            'data' => null,
            'reportes' => collect(),
        ];

        if ($tipo === 'rutas') {
            $vista['reportes'] = $this->consultaRutas($ctx)->paginate(1)->withQueryString();
            $vista['resumenRutas'] = $this->resumenRutas($ctx);
            // Cobrado por administrador de cada ruta de la página (se calcula en vivo)
            $vista['cobroAdmins'] = $vista['reportes']->getCollection()
                ->mapWithKeys(fn($r) => [$r->id => $this->resumenAdmins($this->idsOperaciones($r))])
                ->all();
            return view('admin.reportes_ver', $vista);
        }
        
        if ($tipo === 'atrasados') {
            $datos = $this->datosAtrasados($ctx);
            $porPagina = 50;
            $pagina = LengthAwarePaginator::resolveCurrentPage();

            $vista['data'] = [
                'columnas' => self::COLUMNAS_ATRASADOS,
                'filas' => new LengthAwarePaginator(
                    $datos['filas']->forPage($pagina, $porPagina)->values(),
                    $datos['filas']->count(),
                    $porPagina,
                    $pagina,
                    ['path' => $request->url(), 'query' => $request->query()]
                ),
                'totales' => $datos['totales'],
            ];

            return view('admin.reportes_ver', $vista);
        }

        $def = $this->definicion($tipo, $ctx);

        $vista['data'] = [
            'columnas' => $def['columnas'],
            'filas' => $this->consultaListado($def)->paginate(50)->withQueryString(),
            'totales' => $this->calcularTotales($def),
        ];

        return view('admin.reportes_ver', $vista);
    }

    public function excel(Request $request, string $tipo)
    {
        abort_unless(array_key_exists($tipo, self::TIPOS), 404);

        $ctx = $this->contexto($request, $tipo);
        $nombre = $tipo . '_' . now()->format('Ymd_His') . '.xlsx';

        if ($tipo === 'rutas') {
            $headings = ['Sucursal', 'Ruta', 'Tipo', 'Filtro', 'Generado', 'Orden', 'Cliente', 'Estado', 'Efectivo', 'Sinpe'];
            $rows = [];
            foreach ($this->consultaRutas($ctx)->get() as $r) {
                foreach (($r->datos ?? []) as $d) {
                    $rows[] = [
                        $r->sucursal?->nombre,
                        $r->nombre_ruta ?? ('Ruta #' . $r->ruta_id),
                        $r->tipo,
                        $r->filtro,
                        $r->generado_en?->format('d/m/Y H:i'),
                        $d['orden'] ?? null,
                        $d['nombre'] ?? null,
                        ($d['estado'] ?? '') === 'no_abono' ? 'No abonó' : 'Finalizado',
                        (float) ($d['efectivo'] ?? 0),
                        (float) ($d['sinpe'] ?? 0),
                    ];
                }
            }
            return Excel::download(new ReporteExport('Rutas', $headings, $rows, [], [8, 9]), $nombre);
        }
        
                if ($tipo === 'atrasados') {
            $datos = $this->datosAtrasados($ctx);

            // En el Excel salen todas las columnas menos el código
            $colsExcel = array_values(array_filter(
                self::COLUMNAS_ATRASADOS,
                fn ($col) => in_array($col['key'], ['cliente', 'telefono', 'saldo', 'dias'], true)
            ));

            $headings = array_column($colsExcel, 'label');
            $money = [];
            foreach ($colsExcel as $i => $col) {
                if ($col['tipo'] === 'money') {
                    $money[] = $i;
                }
            }

            $rows = $datos['filas']->map(function ($fila) use ($colsExcel) {
                $out = [];
                foreach ($colsExcel as $col) {
                    $out[] = $this->valorCelda($fila->{$col['key']} ?? null, $col['tipo']);
                }
                return $out;
            })->all();

            $resumen = [];
            $resumenMoney = [];
            $i = 0;
            foreach ($datos['totales'] as $label => $t) {
                $resumen[] = [$label, $t['valor']];
                if ($t['tipo'] === 'money') {
                    $resumenMoney[] = $i;
                }
                $i++;
            }

            return Excel::download(
                new ReporteExport('Atrasados', $headings, $rows, $resumen, $money, $resumenMoney),
                $nombre
            );
        }
        $def = $this->definicion($tipo, $ctx);

                // Si el reporte define 'excel', solo salen esas columnas; si no, salen todas las de la tabla
        $colsExcel = $def['columnas'];
        if (!empty($def['excel'])) {
            $colsExcel = array_values(array_filter(
                $def['columnas'],
                fn ($c) => in_array($c['key'], $def['excel'], true)
            ));
        }

        $headings = array_column($colsExcel, 'label');
        $money = [];
        foreach ($colsExcel as $i => $col) {
            if ($col['tipo'] === 'money') {
                $money[] = $i;
            }
        }

        $rows = $this->consultaListado($def)->get()
            ->map(function ($fila) use ($colsExcel) {
                $out = [];
                foreach ($colsExcel as $col) {
                    $out[] = $this->valorCelda($fila->{$col['key']} ?? null, $col['tipo']);
                }
                return $out;
            })
            ->all();

        $resumen = [];
        $resumenMoney = [];
        $i = 0;
        foreach ($this->calcularTotales($def) as $label => $t) {
            $resumen[] = [$label, $t['valor']];
            if ($t['tipo'] === 'money') {
                $resumenMoney[] = $i;
            }
            $i++;
        }

        return Excel::download(
            new ReporteExport(self::TIPOS[$tipo], $headings, $rows, $resumen, $money, $resumenMoney),
            $nombre
        );
    }

    // ------------------------------------------------------------------
    // CONTEXTO (sucursal, fechas, tipo)
    // ------------------------------------------------------------------

    /**
     * - Admin fijo: siempre su sucursal (ignora ?sucursal=).
     * - Admin libre: 'todas' o el id de una sucursal existente; si no, la activa en sesión.
     */
    private function contexto(Request $request, string $tipo): array
    {
        // Sin validate(): no redirige ni deja errores en sesión. Un valor inválido
        // se descarta y, si corresponde, se avisa con SweetAlert desde la vista.
        $alerta = null;
        $desde = $this->fechaValida($request->query('desde'));
        $hasta = $this->fechaValida($request->query('hasta'));

        if ($desde && $hasta && $hasta < $desde) {
            $alerta = 'La fecha "Hasta" no puede ser anterior a la fecha "Desde".';
            $hasta = null;
        }

        $orden = $request->query('orden') === 'menos' ? 'menos' : 'mas';

        $esLibre = $request->user()->sucursal_id === null;
        $sucursales = $esLibre ? Sucursal::orderBy('id')->get() : collect();

        $sel = (string) session('sucursal_id');
        if ($esLibre) {
            $pedida = $request->query('sucursal');
            if ($pedida === 'todas') {
                $sel = 'todas';
            } elseif ($pedida !== null && ctype_digit($pedida) && $sucursales->contains('id', (int) $pedida)) {
                $sel = (string) (int) $pedida;
            }
        }

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'esLibre' => $esLibre,
            'sucursales' => $sucursales,
            'sel' => $sel,
            'tipo' => $tipo,
            'orden' => $orden,
            'alerta' => $alerta,
        ];
    }

    /** Devuelve la fecha (Y-m-d) si es válida; null si viene vacía o malformada. */
    private function fechaValida($valor): ?string
    {
        if (!is_string($valor) || $valor === '') {
            return null;
        }
        try {
            $f = Carbon::createFromFormat('Y-m-d', $valor);
        } catch (\Throwable $e) {
            return null;
        }
        return ($f && $f->format('Y-m-d') === $valor) ? $valor : null;
    }

    private function nombreSeleccion(array $ctx): string
    {
        if ($ctx['sel'] === 'todas') {
            return 'Todas las sucursales';
        }
        return Sucursal::find((int) $ctx['sel'])?->nombre ?? ('Sucursal #' . $ctx['sel']);
    }

    // ------------------------------------------------------------------
    // HELPERS DE CONSULTA (query builder puro: no depende del Global Scope)
    // ------------------------------------------------------------------

    private function t(string $modelo): string
    {
        return (new $modelo)->getTable();
    }

    /** Expresión para la columna nombre; si la tabla no la tiene, NULL (no rompe). */
    private function nombreCol(string $tabla): string
    {
        return Schema::hasColumn($tabla, 'nombre') ? "$tabla.nombre" : 'NULL';
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
            $q->where($col, '>=', $desde . ' 00:00:00');
        }
        if ($hasta) {
            $q->where($col, '<', Carbon::parse($hasta)->addDay()->format('Y-m-d') . ' 00:00:00');
        }
    }

    private function valorCelda($v, string $tipo)
    {
        if ($v === null) {
            return null;
        }
        return match ($tipo) {
            'money', 'num' => (float) $v,
            'fecha' => Carbon::parse($v)->format('d/m/Y'),
            'fechahora' => Carbon::parse($v)->format('d/m/Y H:i'),
            default => $v,
        };
    }

    /** Listado: base + select + (group by) + orden. */
    private function consultaListado(array $def)
    {
        $q = (clone $def['base'])->select($def['select']);

        if (!empty($def['grupo'])) {
            $q->groupBy(...$def['grupo']);
        }
        foreach ($def['orden'] as $o) {
            $q->orderBy($o[0], $o[1]);
        }
        return $q;
    }

    /** Totales sobre TODO el conjunto filtrado (sin group by, sin paginar). */
    private function calcularTotales(array $def): array
    {
        $exprs = [];
        $i = 0;
        foreach ($def['totales'] as [$sql, $tipo]) {
            $exprs[] = "$sql as t$i";
            $i++;
        }
        $fila = (clone $def['base'])->select(DB::raw(implode(', ', $exprs)))->first();

        $out = [];
        $i = 0;
        foreach ($def['totales'] as $label => [$sql, $tipo]) {
            $out[$label] = ['valor' => (float) ($fila->{"t$i"} ?? 0), 'tipo' => $tipo];
            $i++;
        }
        return $out;
    }

    // ------------------------------------------------------------------
    // DEFINICIÓN DE CADA REPORTE
    // ------------------------------------------------------------------

    private function definicion(string $tipo, array $ctx): array
    {
        $sel = $ctx['sel'];
        $d = $ctx['desde'];
        $h = $ctx['hasta'];

        $s = $this->t(Sucursal::class);
        $c = $this->t(Cliente::class);
        $sNom = $this->nombreCol($s);
        $cNom = $this->nombreCol($c);

        switch ($tipo) {
            case 'ventas':
                $f = $this->t(Factura::class);
                $o = $this->t(Operacion::class);
                $base = DB::table($f)
                    ->leftJoin($c, "$c.id", '=', "$f.cliente_id")
                    ->leftJoin($s, "$s.id", '=', "$f.sucursal_id")
                    ->whereNotNull("$f.operacion_id")
                    // Las facturas que crea un traspaso no son ventas reales
                    ->whereNotIn("$f.operacion_id", fn($sub) => $sub->select("$o.id")->from($o)->where("$o.tipo", 'traspaso'));
                $this->porSucursal($base, $sel, "$f.sucursal_id");
                $this->porFecha($base, "$f.created_at", $d, $h);

                return [
                    'base' => $base,
                    'select' => [
                        "$f.id as id",
                        "$f.created_at as fecha",
                        DB::raw("$sNom as sucursal"),
                        DB::raw("$cNom as cliente"),
                        "$f.estado as estado",
                        "$f.descuento as descuento",
                        "$f.total as total",
                        "$f.efectivo as efectivo",
                        "$f.sinpe as sinpe",
                    ],
                    'orden' => [["$f.created_at", 'desc'], ["$f.id", 'desc']],
                    'columnas' => [
                        ['key' => 'id', 'label' => 'Factura #', 'tipo' => 'text'],
                        ['key' => 'fecha', 'label' => 'Fecha', 'tipo' => 'fechahora'],
                        ['key' => 'sucursal', 'label' => 'Sucursal', 'tipo' => 'text'],
                        ['key' => 'cliente', 'label' => 'Cliente', 'tipo' => 'text'],
                        ['key' => 'estado', 'label' => 'Estado', 'tipo' => 'text'],
                        ['key' => 'descuento', 'label' => 'Descuento', 'tipo' => 'money'],
                        ['key' => 'total', 'label' => 'Total', 'tipo' => 'money'],
                        ['key' => 'efectivo', 'label' => 'Efectivo', 'tipo' => 'money'],
                        ['key' => 'sinpe', 'label' => 'Sinpe', 'tipo' => 'money'],
                    ],
                    // Las anuladas se listan (con su estado) pero no suman
                    'totales' => [
                        'Facturas' => ["COALESCE(SUM(CASE WHEN $f.estado <> 'anulada' THEN 1 ELSE 0 END),0)", 'num'],
                        'Anuladas' => ["COALESCE(SUM(CASE WHEN $f.estado = 'anulada' THEN 1 ELSE 0 END),0)", 'num'],
                        'Total vendido' => ["COALESCE(SUM(CASE WHEN $f.estado <> 'anulada' THEN $f.total ELSE 0 END),0)", 'money'],
                        'Descuentos' => ["COALESCE(SUM(CASE WHEN $f.estado <> 'anulada' THEN $f.descuento ELSE 0 END),0)", 'money'],
                        'Efectivo' => ["COALESCE(SUM(CASE WHEN $f.estado <> 'anulada' THEN $f.efectivo ELSE 0 END),0)", 'money'],
                        'Sinpe' => ["COALESCE(SUM(CASE WHEN $f.estado <> 'anulada' THEN $f.sinpe ELSE 0 END),0)", 'money'],
                    ],
                ];

            case 'abonos':
                $a = $this->t(Abono::class);
                $o = $this->t(Operacion::class);
                $base = DB::table($a)
                    ->join($c, "$c.id", '=', "$a.cliente_id")
                    ->leftJoin($s, "$s.id", '=', "$c.sucursal_id")
                    // Los abonos de traspaso no son plata cobrada. Los migrados (operacion_id nulo) se quedan.
                    ->where(fn($w) => $w->whereNull("$a.operacion_id")
                        ->orWhereNotIn("$a.operacion_id", fn($sub) => $sub->select("$o.id")->from($o)->where("$o.tipo", 'traspaso')))
                    // Registros del sistema viejo sin monto ni cobro no son pagos: no se cuentan ni se listan
                    ->where(fn($w) => $w->where("$a.monto_abono", '<>', 0)->orWhere("$a.efectivo", '<>', 0)->orWhere("$a.sinpe", '<>', 0));
                $this->porSucursal($base, $sel, "$c.sucursal_id");
                $this->porFecha($base, "$a.fecha", $d, $h);

                return [
                    'base' => $base,
                    'select' => [
                        "$a.id as id",
                        "$a.fecha as fecha",
                        DB::raw("$sNom as sucursal"),
                        DB::raw("$cNom as cliente"),
                        "$a.monto_abono as monto",
                        "$a.efectivo as efectivo",
                        "$a.sinpe as sinpe",
                    ],
                    'orden' => [["$a.fecha", 'desc'], ["$a.id", 'desc']],
                    'columnas' => [
                        ['key' => 'id', 'label' => 'Abono #', 'tipo' => 'text'],
                        ['key' => 'fecha', 'label' => 'Fecha', 'tipo' => 'fecha'],
                        ['key' => 'sucursal', 'label' => 'Sucursal', 'tipo' => 'text'],
                        ['key' => 'cliente', 'label' => 'Cliente', 'tipo' => 'text'],
                        ['key' => 'monto', 'label' => 'Monto', 'tipo' => 'money'],
                        ['key' => 'efectivo', 'label' => 'Efectivo', 'tipo' => 'money'],
                        ['key' => 'sinpe', 'label' => 'Sinpe', 'tipo' => 'money'],
                    ],
                    'totales' => [
                        'Abonos' => ['COUNT(*)', 'num'],
                        'Total abonado' => ["COALESCE(SUM($a.monto_abono),0)", 'money'],
                        'Efectivo' => ["COALESCE(SUM($a.efectivo),0)", 'money'],
                        'Sinpe' => ["COALESCE(SUM($a.sinpe),0)", 'money'],
                    ],
                ];

            case 'gastos':
                $g = $this->t(Gasto::class);
                $base = DB::table($g)->leftJoin($s, "$s.id", '=', "$g.sucursal_id");
                $this->porSucursal($base, $sel, "$g.sucursal_id");
                $this->porFecha($base, "$g.fecha", $d, $h);

                return [
                    'base' => $base,
                    'select' => [
                        "$g.id as id",
                        "$g.fecha as fecha",
                        DB::raw("$sNom as sucursal"),
                        "$g.categoria as categoria",
                        "$g.descripcion as descripcion",
                        "$g.monto as monto",
                    ],
                    'orden' => [["$g.fecha", 'desc'], ["$g.id", 'desc']],
                    'columnas' => [
                        ['key' => 'fecha', 'label' => 'Fecha', 'tipo' => 'fecha'],
                        ['key' => 'sucursal', 'label' => 'Sucursal', 'tipo' => 'text'],
                        ['key' => 'categoria', 'label' => 'Categoría', 'tipo' => 'text'],
                        ['key' => 'descripcion', 'label' => 'Descripción', 'tipo' => 'text'],
                        ['key' => 'monto', 'label' => 'Monto', 'tipo' => 'money'],
                    ],
                    'totales' => [
                        'Gastos' => ['COUNT(*)', 'num'],
                        'Total gastado' => ["COALESCE(SUM($g.monto),0)", 'money'],
                    ],
                ];

            case 'compras':
                $co = $this->t(Compra::class);
                $p = $this->t(Proveedor::class);
                $pNom = $this->nombreCol($p);
                $base = DB::table($co)
                    ->leftJoin($s, "$s.id", '=', "$co.sucursal_id")
                    ->leftJoin($p, "$p.id", '=', "$co.proveedor_id");
                $this->porSucursal($base, $sel, "$co.sucursal_id");
                $this->porFecha($base, "$co.fecha", $d, $h);

                return [
                    'base' => $base,
                    'select' => [
                        "$co.id as id",
                        "$co.fecha as fecha",
                        DB::raw("$sNom as sucursal"),
                        DB::raw("$pNom as proveedor"),
                        "$co.descripcion as descripcion",
                        "$co.tipo as tipo",
                        "$co.estado as estado",
                        "$co.monto_total as monto",
                        "$co.saldo_pendiente as pendiente",
                    ],
                    'orden' => [["$co.fecha", 'desc'], ["$co.id", 'desc']],
                    'columnas' => [
                        ['key' => 'fecha', 'label' => 'Fecha', 'tipo' => 'fecha'],
                        ['key' => 'sucursal', 'label' => 'Sucursal', 'tipo' => 'text'],
                        ['key' => 'proveedor', 'label' => 'Proveedor', 'tipo' => 'text'],
                        ['key' => 'descripcion', 'label' => 'Descripción', 'tipo' => 'text'],
                        ['key' => 'tipo', 'label' => 'Tipo', 'tipo' => 'text'],
                        ['key' => 'estado', 'label' => 'Estado', 'tipo' => 'text'],
                        ['key' => 'monto', 'label' => 'Monto', 'tipo' => 'money'],
                        ['key' => 'pendiente', 'label' => 'Saldo pendiente', 'tipo' => 'money'],
                    ],
                    'totales' => [
                        'Compras' => ['COUNT(*)', 'num'],
                        'Total comprado' => ["COALESCE(SUM($co.monto_total),0)", 'money'],
                        'Saldo pendiente' => ["COALESCE(SUM($co.saldo_pendiente),0)", 'money'],
                    ],
                ];

            case 'cancelados':
                $a = $this->t(Abono::class);
                $f = $this->t(Factura::class);

                // Último abono real (sin registros en cero) y última factura no anulada de cada cliente
                $ultAbono = DB::table($a)
                    ->selectRaw('cliente_id, MAX(COALESCE(fecha, created_at)) as ult')
                    ->where(fn($w) => $w->where('monto_abono', '<>', 0)->orWhere('efectivo', '<>', 0)->orWhere('sinpe', '<>', 0))
                    ->groupBy('cliente_id');
                $ultFactura = DB::table($f)
                    ->selectRaw('cliente_id, MAX(COALESCE(fecha, created_at)) as ult')
                    ->where('estado', '<>', 'anulada')
                    ->groupBy('cliente_id');

                // Fecha del último movimiento: la más reciente de las dos (puede ser NULL si nunca tuvo)
                $ultimo = 'CASE WHEN ua.ult IS NULL THEN uf.ult WHEN uf.ult IS NULL THEN ua.ult WHEN ua.ult >= uf.ult THEN ua.ult ELSE uf.ult END';

                // Cancelado = saldo en cero o a favor
                $base = DB::table($c)
                    ->leftJoin($s, "$s.id", '=', "$c.sucursal_id")
                    ->leftJoinSub($ultAbono, 'ua', 'ua.cliente_id', '=', "$c.id")
                    ->leftJoinSub($ultFactura, 'uf', 'uf.cliente_id', '=', "$c.id")
                    ->where("$c.saldo_actual", '<=', 0);
                $this->porSucursal($base, $sel, "$c.sucursal_id");
                if ($d) {
                    $base->whereRaw("($ultimo) >= ?", [$d . ' 00:00:00']);
                }
                if ($h) {
                    $base->whereRaw("($ultimo) < ?", [Carbon::parse($h)->addDay()->format('Y-m-d') . ' 00:00:00']);
                }

                return [
                    'base' => $base,
                                        'select' => [
                        "$c.codigo as codigo", "$c.nombre as cliente",
                        "$c.telefono as telefono", "$c.saldo_actual as saldo",
                        DB::raw("($ultimo) as ultimo_mov"), // solo para ordenar, no se muestra
                    ],
                    'orden' => [['ultimo_mov', 'desc'], ["$c.nombre", 'asc']],
                    'columnas' => [
                        ['key' => 'codigo', 'label' => 'Código', 'tipo' => 'text'],
                        ['key' => 'cliente', 'label' => 'Cliente', 'tipo' => 'text'],
                        ['key' => 'telefono', 'label' => 'Teléfono', 'tipo' => 'text'],
                        ['key' => 'saldo', 'label' => 'Saldo', 'tipo' => 'money'],
                    ],
                    'excel' => ['cliente', 'telefono', 'saldo'], // columnas que salen en el Excel
                    'totales' => [
                        'Clientes cancelados' => ['COUNT(*)', 'num'],
                        'Con saldo a favor' => ["COALESCE(SUM(CASE WHEN $c.saldo_actual < 0 THEN 1 ELSE 0 END),0)", 'num'],
                        'Total a favor' => ["COALESCE(-SUM(CASE WHEN $c.saldo_actual < 0 THEN $c.saldo_actual ELSE 0 END),0)", 'money'],
                    ],
                ];
            case 'inventario':
                $l = $this->t(FacturaLinea::class);
                $f = $this->t(Factura::class);
                $base = DB::table($l)
                    ->join($f, "$f.id", '=', "$l.factura_id")
                    ->whereNotNull("$f.operacion_id")
                    ->whereNotNull("$l.producto_id") // excluye líneas libres (fuera de catálogo)
                    ->where("$f.estado", '!=', 'anulada');
                $this->porSucursal($base, $sel, "$f.sucursal_id");
                $this->porFecha($base, "$f.created_at", $d, $h);

                $dir = $ctx['orden'] === 'menos' ? 'asc' : 'desc';

                return [
                    'base' => $base,
                    'grupo' => ["$l.producto_id"],
                    'select' => [
                        "$l.producto_id as producto_id",
                        DB::raw("MAX($l.descripcion) as producto"),
                        DB::raw("SUM($l.cantidad) as unidades"),
                        DB::raw("SUM($l.cantidad * $l.precio_unit) as monto"),
                    ],
                    'orden' => [['unidades', $dir], ['monto', $dir], ["$l.producto_id", $dir]],
                    'columnas' => [
                        ['key' => 'producto_id', 'label' => 'ID producto', 'tipo' => 'text'],
                        ['key' => 'producto', 'label' => 'Producto', 'tipo' => 'text'],
                        ['key' => 'unidades', 'label' => 'Unidades vendidas', 'tipo' => 'num'],
                        ['key' => 'monto', 'label' => 'Monto vendido', 'tipo' => 'money'],
                    ],
                    'totales' => [
                        'Productos distintos' => ["COUNT(DISTINCT $l.producto_id)", 'num'],
                        'Unidades vendidas' => ["COALESCE(SUM($l.cantidad),0)", 'num'],
                        'Monto vendido' => ["COALESCE(SUM($l.cantidad * $l.precio_unit),0)", 'money'],
                    ],
                ];
        }

        abort(404);
    }

    // ------------------------------------------------------------------
    // RUTAS
    // ------------------------------------------------------------------



        // ------------------------------------------------------------------
    // ATRASADOS (la regla vive en AtrasadosService, igual que en Rutas)
    // ------------------------------------------------------------------

    /**
     * Clientes con deuda (saldo > 0) que llevan 4 semanas o más sin abonar.
     * @return array{filas: \Illuminate\Support\Collection, totales: array}
     */
    private function datosAtrasados(array $ctx): array
    {
        $c = $this->t(Cliente::class);

        // Query builder puro: no depende del Global Scope de Cliente
        $q = DB::table($c)
            ->where("$c.saldo_actual", '>', 0)
            ->select("$c.id", "$c.sucursal_id", "$c.codigo", "$c.nombre", "$c.telefono", "$c.saldo_actual", "$c.created_at");
        $this->porSucursal($q, $ctx['sel'], "$c.sucursal_id");
        $clientes = $q->get();

        // El servicio consulta Factura con Global Scope (sucursal en sesión): se calcula por
        // sucursal, y la sesión original se restaura siempre al terminar.
        $servicio = app(AtrasadosService::class);
        $sesionOriginal = session('sucursal_id');
        $referencias = collect();
        try {
            foreach ($clientes->groupBy('sucursal_id') as $sucursalId => $grupo) {
                session(['sucursal_id' => $sucursalId]);
                foreach ($servicio->referencias($grupo->values()) as $clienteId => $fecha) {
                    $referencias[$clienteId] = $fecha;
                }
            }
        } finally {
            session(['sucursal_id' => $sesionOriginal]);
        }

        $hoy = now();
        $filas = $clientes
            ->filter(fn ($cli) => $referencias->has($cli->id))
            ->map(fn ($cli) => (object) [
                'codigo' => $cli->codigo,
                'cliente' => $cli->nombre,
                'telefono' => $cli->telefono,
                'saldo' => (float) $cli->saldo_actual,
                'dias' => (int) floor(abs($referencias[$cli->id]->diffInDays($hoy))),
            ])
            ->sortBy([['dias', 'desc'], ['cliente', 'asc']])
            ->values();

        $totales = [
            'Clientes atrasados' => ['valor' => (float) $filas->count(), 'tipo' => 'num'],
            'Saldo atrasado' => ['valor' => round((float) $filas->sum('saldo'), 2), 'tipo' => 'money'],
            'Más atrasado (días)' => ['valor' => (float) ($filas->max('dias') ?? 0), 'tipo' => 'num'],
        ];

        return ['filas' => $filas, 'totales' => $totales];
    }
    private function consultaRutas(array $ctx)
    {
        return ReporteRuta::todasLasSucursales()
            ->with('sucursal')
            ->when($ctx['sel'] !== 'todas', fn($q) => $q->where('reportes_ruta.sucursal_id', (int) $ctx['sel']))
            ->when($ctx['desde'], fn($q) => $q->where('generado_en', '>=', $ctx['desde'] . ' 00:00:00'))
            ->when($ctx['hasta'], fn($q) => $q->where('generado_en', '<', Carbon::parse($ctx['hasta'])->addDay()->format('Y-m-d') . ' 00:00:00'))
            ->orderByDesc('generado_en')
            ->orderByDesc('reportes_ruta.id');
    }


    /** Ids de las operaciones guardadas en un reporte de ruta (los reportes viejos no las tienen). */
    private function idsOperaciones(ReporteRuta $r): array
    {
        $ids = [];
        foreach (($r->datos ?? []) as $d) {
            foreach (($d['operaciones'] ?? []) as $op) {
                if (!empty($op['id'])) {
                    $ids[] = (int) $op['id'];
                }
            }
        }
        return array_values(array_unique($ids));
    }

    /**
     * Lo hecho por cada administrador en esas operaciones. Sin facturas anuladas y con el
     * efectivo neto del vuelto. Devuelve null si no hay operaciones (reporte viejo).
     */
    private function resumenAdmins(array $operacionIds): ?array
    {
        if (!$operacionIds) {
            return null;
        }

        $o = $this->t(Operacion::class);
        $u = $this->t(User::class);

        $ops = DB::table($o)
            ->leftJoin($u, "$u.id", '=', "$o.user_id")
            ->whereIn("$o.id", $operacionIds)
            ->get(["$o.id as id", "$o.user_id as user_id", "$o.no_abono as no_abono", "$u.name as admin"]);

        $ventas = DB::table($this->t(Factura::class))
            ->whereIn('operacion_id', $operacionIds)
            ->where('estado', '!=', 'anulada')
            ->groupBy('operacion_id')
            ->selectRaw('operacion_id, COUNT(*) as cant, SUM(total) as total, SUM(efectivo - COALESCE(vuelto, 0)) as efectivo, SUM(sinpe) as sinpe')
            ->get()->keyBy('operacion_id');

        $abonos = DB::table($this->t(Abono::class))
            ->whereIn('operacion_id', $operacionIds)
            ->groupBy('operacion_id')
            ->selectRaw('operacion_id, SUM(efectivo) as efectivo, SUM(sinpe) as sinpe')
            ->get()->keyBy('operacion_id');

        $devs = DB::table($this->t(Devolucion::class))
            ->whereIn('operacion_id', $operacionIds)
            ->groupBy('operacion_id')
            ->selectRaw('operacion_id, SUM(total) as total')
            ->get()->keyBy('operacion_id');

        $out = [];
        foreach ($ops as $op) {
            $k = $op->user_id ?? 0;
            $out[$k] ??= [
                'admin' => $op->admin ?? 'Sin usuario',
                'efectivo' => 0.0,
                'sinpe' => 0.0,
                'ventas' => 0,
                'vendido' => 0.0,
                'devuelto' => 0.0,
                'sin_abono' => 0,
            ];

            if ($op->no_abono) {
                $out[$k]['sin_abono']++;
            }
            if ($v = $ventas[$op->id] ?? null) {
                $out[$k]['ventas'] += (int) $v->cant;
                $out[$k]['vendido'] += (float) $v->total;
                $out[$k]['efectivo'] += (float) $v->efectivo;
                $out[$k]['sinpe'] += (float) $v->sinpe;
            }
            if ($a = $abonos[$op->id] ?? null) {
                $out[$k]['efectivo'] += (float) $a->efectivo;
                $out[$k]['sinpe'] += (float) $a->sinpe;
            }
            if ($d = $devs[$op->id] ?? null) {
                $out[$k]['devuelto'] += (float) $d->total;
            }
        }

        foreach ($out as &$fila) {
            foreach (['efectivo', 'sinpe', 'vendido', 'devuelto'] as $campo) {
                $fila[$campo] = round($fila[$campo], 2);
            }
        }
        unset($fila);

        usort($out, fn($x, $y) => strcmp($x['admin'], $y['admin']));

        return $out;
    }
    /** Totales de TODAS las rutas del filtro (no solo la página visible). */
    private function resumenRutas(array $ctx): array
    {
        $reportes = $this->consultaRutas($ctx)
            ->setEagerLoads([])
            ->reorder()
            ->select('reportes_ruta.id', 'reportes_ruta.datos')
            ->get();

        $efectivo = 0.0;
        $sinpe = 0.0;
        $finalizados = 0;
        $noAbono = 0;

        foreach ($reportes as $r) {
            foreach (($r->datos ?? []) as $d) {
                $efectivo += (float) ($d['efectivo'] ?? 0);
                $sinpe += (float) ($d['sinpe'] ?? 0);
                if (($d['estado'] ?? '') === 'finalizado') {
                    $finalizados++;
                } elseif (($d['estado'] ?? '') === 'no_abono') {
                    $noAbono++;
                }
            }
        }

        return [
            'reportes' => $reportes->count(),
            'efectivo' => $efectivo,
            'sinpe' => $sinpe,
            'finalizados' => $finalizados,
            'noAbono' => $noAbono,
        ];
    }
}
