<?php
// Uso (en la raíz del proyecto):
//   php artisan tinker
//   include base_path('pruebas_reportes.php');
//
// Solo LEE datos (no inserta, no borra, no toca la BD).

use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Devolucion;
use App\Models\Factura;
use App\Models\FacturaLinea;
use App\Models\Gasto;
use App\Models\Operacion;
use App\Models\Proveedor;
use App\Models\ReporteRuta;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

$fallos = 0;
$chk = function (string $nombre, bool $cond, string $detalle = '') use (&$fallos) {
    if (!$cond) {
        $fallos++;
    }
    echo ($cond ? '  [OK]    ' : '  [FALLA] ') . $nombre . ($detalle !== '' ? "  -> $detalle" : '') . PHP_EOL;
};
$igual = fn ($a, $b) => abs((float) $a - (float) $b) < 0.01;

$ctrl = app(App\Http\Controllers\ReporteController::class);
$call = function (string $metodo, ...$args) use ($ctrl) {
    $r = new ReflectionMethod($ctrl, $metodo);
    $r->setAccessible(true);
    return $r->invoke($ctrl, ...$args);
};

$sesionOriginal = session('sucursal_id');
$ids = Sucursal::orderBy('id')->pluck('id');
$libre = User::whereNull('sucursal_id')->first();

// ------------------------------------------------------------------
echo PHP_EOL . "1) COLUMNAS QUE ASUMÍ EXISTEN" . PHP_EOL;
$esperadas = [
    [(new Cliente)->getTable(), ['nombre', 'sucursal_id', 'saldo_actual']],
    [(new Sucursal)->getTable(), ['nombre']],
    [(new Proveedor)->getTable(), ['nombre']],
    [(new Factura)->getTable(), ['operacion_id', 'estado', 'total', 'descuento', 'efectivo', 'sinpe', 'created_at', 'sucursal_id', 'cliente_id']],
    [(new FacturaLinea)->getTable(), ['factura_id', 'producto_id', 'descripcion', 'cantidad', 'precio_unit']],
    [(new Abono)->getTable(), ['cliente_id', 'fecha', 'monto_abono', 'efectivo', 'sinpe']],
    [(new Devolucion)->getTable(), ['cliente_id', 'fecha', 'total']],
    [(new Gasto)->getTable(), ['sucursal_id', 'categoria', 'descripcion', 'monto', 'fecha']],
    [(new Compra)->getTable(), ['sucursal_id', 'proveedor_id', 'descripcion', 'tipo', 'estado', 'monto_total', 'saldo_pendiente', 'fecha']],
    [(new Operacion)->getTable(), ['sucursal_id', 'fecha']],
    [(new ReporteRuta)->getTable(), ['sucursal_id', 'generado_en', 'datos']],
];
foreach ($esperadas as [$tabla, $cols]) {
    foreach ($cols as $col) {
        $chk("$tabla.$col", Schema::hasColumn($tabla, $col));
    }
}

// ------------------------------------------------------------------
echo PHP_EOL . "2) RESUMEN GENERAL por sucursal vs. cálculo independiente (Eloquent con scope)" . PHP_EOL;
$sumaResumen = [];
foreach ($ids as $id) {
    session(['sucursal_id' => $id]);
    $r = $call('resumen', ['sel' => (string) $id, 'desde' => null, 'hasta' => null]);

    $clienteIds = Cliente::pluck('id');
    $esp = [
        'totalVendido' => (float) Factura::nuevas()->sum('total'),
        'totalAbonado' => (float) Abono::whereIn('cliente_id', $clienteIds)->sum('monto_abono'),
        'totalDevuelto' => (float) Devolucion::whereIn('cliente_id', $clienteIds)->sum('total'),
        'cantidadOperaciones' => Operacion::count(),
        'totalGastos' => (float) Gasto::sum('monto'),
        'totalCompras' => (float) Compra::sum('monto_total'),
        'saldoPendiente' => (float) Cliente::where('saldo_actual', '>', 0)->sum('saldo_actual'),
    ];
    foreach ($esp as $k => $v) {
        $chk("sucursal $id · $k", $igual($r[$k], $v), "pantalla={$r[$k]} esperado=$v");
        $sumaResumen[$k] = ($sumaResumen[$k] ?? 0) + $r[$k];
    }
}

echo PHP_EOL . "3) 'TODAS' = suma de las sucursales" . PHP_EOL;
$todas = $call('resumen', ['sel' => 'todas', 'desde' => null, 'hasta' => null]);
foreach ($sumaResumen as $k => $v) {
    $chk("todas · $k", $igual($todas[$k], $v), "todas={$todas[$k]} suma=$v");
}

// ------------------------------------------------------------------
echo PHP_EOL . "4) CADA TIPO DE REPORTE: listado vs. totales (todas + primera sucursal + con rango de fechas)" . PHP_EOL;
$pares = [ // tipo => [columna a sumar en el listado, etiqueta del total equivalente]
    'ventas' => ['total', 'Total vendido'],
    'abonos' => ['monto', 'Total abonado'],
    'gastos' => ['monto', 'Total gastado'],
    'compras' => ['monto', 'Total comprado'],
    'inventario' => ['unidades', 'Unidades vendidas'],
];
$escenarios = [
    ['sel' => 'todas', 'desde' => null, 'hasta' => null],
    ['sel' => (string) $ids->first(), 'desde' => null, 'hasta' => null],
    ['sel' => 'todas', 'desde' => '2020-01-01', 'hasta' => '2030-12-31'],
];
foreach ($pares as $tipo => [$colSuma, $etiqueta]) {
    foreach ($escenarios as $i => $esc) {
        $ctx = $esc + ['tipo' => $tipo, 'orden' => 'mas'];
        $nombre = "$tipo · sel={$esc['sel']}" . ($esc['desde'] ? ' · con fechas' : '');
        try {
            $def = $call('definicion', $tipo, $ctx);
            $filas = $call('consultaListado', $def)->get();
            $tot = $call('calcularTotales', $def);

            $chk("$nombre · listado suma = total", $igual($filas->sum($colSuma), $tot[$etiqueta]['valor']),
                "listado={$filas->sum($colSuma)} total={$tot[$etiqueta]['valor']} filas={$filas->count()}");

            if ($tipo !== 'inventario') {
                $primero = $tot[array_key_first($tot)]['valor'];
                $chk("$nombre · cantidad de filas = total", (int) $primero === $filas->count(),
                    "filas={$filas->count()} total=$primero");
            }
        } catch (Throwable $e) {
            $chk($nombre, false, get_class($e) . ': ' . $e->getMessage());
        }
    }
}

// Orden de Inventario
try {
    $ctx = ['sel' => 'todas', 'desde' => null, 'hasta' => null, 'tipo' => 'inventario'];
    $mas = $call('consultaListado', $call('definicion', 'inventario', $ctx + ['orden' => 'mas']))->get();
    $menos = $call('consultaListado', $call('definicion', 'inventario', $ctx + ['orden' => 'menos']))->get();
    if ($mas->isNotEmpty() && $menos->isNotEmpty()) {
        $chk('inventario · "más vendidos" arranca por el mayor', (float) $mas->first()->unidades >= (float) $mas->last()->unidades);
        $chk('inventario · "menos vendidos" arranca por el menor', (float) $menos->first()->unidades <= (float) $menos->last()->unidades);
        echo "     top: {$mas->first()->producto} ({$mas->first()->unidades} u.)  |  bottom: {$menos->first()->producto} ({$menos->first()->unidades} u.)" . PHP_EOL;
    } else {
        echo "  [--]    Inventario sin datos para probar el orden" . PHP_EOL;
    }
} catch (Throwable $e) {
    $chk('inventario · orden', false, $e->getMessage());
}

// ------------------------------------------------------------------
echo PHP_EOL . "5) SUCURSAL Y FECHAS (reglas de acceso y validación)" . PHP_EOL;
session(['sucursal_id' => 2]);
$mk = function (array $query, $user) {
    $req = Request::create('/reportes', 'GET', $query);
    $req->setUserResolver(fn () => $user);
    return $req;
};
$admLibre = $libre ?? (new User)->forceFill(['sucursal_id' => null]);

$c = $call('contexto', $mk(['desde' => '2026-09-10', 'hasta' => '2026-09-01'], $admLibre));
$chk('rango invertido → alerta y "hasta" descartado', $c['alerta'] !== null && $c['hasta'] === null);

$c = $call('contexto', $mk(['desde' => 'abc', 'hasta' => '2026-13-45'], $admLibre));
$chk('fechas basura → ignoradas sin error', $c['desde'] === null && $c['hasta'] === null && $c['alerta'] === null);

$c = $call('contexto', $mk(['desde' => '2026-09-01', 'hasta' => '2026-09-10'], $admLibre));
$chk('rango válido → sin alerta', $c['alerta'] === null && $c['desde'] === '2026-09-01' && $c['hasta'] === '2026-09-10');

$c = $call('contexto', $mk(['sucursal' => 'todas'], $admLibre));
$chk('admin libre + sucursal=todas → "todas"', $c['sel'] === 'todas');

$c = $call('contexto', $mk(['sucursal' => (string) $ids->last()], $admLibre));
$chk('admin libre + sucursal válida', $c['sel'] === (string) $ids->last());

$c = $call('contexto', $mk(['sucursal' => '99999'], $admLibre));
$chk('admin libre + sucursal inexistente → cae a la de sesión', $c['sel'] === '2');

$c = $call('contexto', $mk(['sucursal' => "1 OR 1=1"], $admLibre));
$chk('sucursal con texto malicioso → cae a la de sesión', $c['sel'] === '2');

session(['sucursal_id' => 3]);
$fijo = (new User)->forceFill(['sucursal_id' => 3]);
$c = $call('contexto', $mk(['sucursal' => 'todas'], $fijo));
$chk('admin FIJO pidiendo "todas" → sigue en la suya', $c['sel'] === '3' && $c['sucursales']->isEmpty() && !$c['esLibre']);
$c = $call('contexto', $mk(['sucursal' => '2'], $fijo));
$chk('admin FIJO pidiendo sucursal 2 → sigue en la 3', $c['sel'] === '3');

$c = $call('contexto', $mk(['tipo' => 'xxx', 'orden' => 'zzz'], $admLibre));
$chk('tipo/orden inválidos → valores por defecto', $c['tipo'] === 'ventas' && $c['orden'] === 'mas');

// ------------------------------------------------------------------
echo PHP_EOL . "6) EXCEL: genera el archivo y el contenido coincide" . PHP_EOL;
session(['sucursal_id' => 2]);
foreach (['ventas', 'abonos', 'gastos', 'compras', 'inventario', 'rutas'] as $tipo) {
    try {
        $req = Request::create('/reportes/excel', 'GET', ['tipo' => $tipo, 'sucursal' => 'todas']);
        $req->setUserResolver(fn () => $admLibre);
        $resp = $ctrl->excel($req);

        $ok = $resp instanceof Symfony\Component\HttpFoundation\BinaryFileResponse;
        $chk("$tipo · devuelve archivo", $ok);
        if (!$ok) {
            continue;
        }

        $hoja = PhpOffice\PhpSpreadsheet\IOFactory::load($resp->getFile()->getPathname())->getActiveSheet();
        $filasHoja = $hoja->getHighestRow();

        if ($tipo !== 'rutas') {
            $ctx = ['sel' => 'todas', 'desde' => null, 'hasta' => null, 'tipo' => $tipo, 'orden' => 'mas'];
            $def = $call('definicion', $tipo, $ctx);
            $n = $call('consultaListado', $def)->get()->count();
            $esperado = 1 + $n + 2 + count($def['totales']); // encabezado + datos + (vacía + "RESUMEN") + totales
            $chk("$tipo · filas del Excel = datos + resumen", $filasHoja === $esperado, "excel=$filasHoja esperado=$esperado");
        } else {
            echo "     filas en hoja: $filasHoja" . PHP_EOL;
        }
    } catch (Throwable $e) {
        $chk("$tipo · excel", false, get_class($e) . ': ' . $e->getMessage());
    }
}

// ------------------------------------------------------------------
echo PHP_EOL . "7) PANTALLA COMPLETA por HTTP interno (renderiza el blade de verdad)" . PHP_EOL;
echo "   (Si esta sección falla pero 1 a 6 pasan, puede ser una limitación de tinker con la sesión, no de la pantalla.)" . PHP_EOL;
if (!$libre) {
    echo "  [--]    No hay ningún usuario con sucursal_id NULL; se omite." . PHP_EOL;
} else {
    auth()->login($libre);
    session(['sucursal_id' => 2]);
    $kernel = app(Illuminate\Contracts\Http\Kernel::class);
    foreach (['ventas', 'abonos', 'gastos', 'compras', 'inventario', 'rutas'] as $tipo) {
        try {
            $resp = $kernel->handle(Request::create('/reportes', 'GET', ['tipo' => $tipo, 'sucursal' => 'todas']));
            $status = $resp->getStatusCode();
            $html = $resp->getContent();
            $tiene = str_contains($html, 'Elegí un reporte') && str_contains($html, 'Reporte de ' . $tipo);
            $chk("$tipo · HTTP $status y contiene la pantalla", $status === 200 && $tiene,
                $status !== 200 ? ('redirige/error a: ' . ($resp->headers->get('Location') ?? 'n/d')) : '');
        } catch (Throwable $e) {
            $chk("$tipo · render", false, get_class($e) . ': ' . $e->getMessage());
        }
    }
}

// ------------------------------------------------------------------
session(['sucursal_id' => $sesionOriginal]);
echo PHP_EOL . str_repeat('=', 60) . PHP_EOL;
echo $fallos === 0 ? "TODO OK: 0 fallos" : "HAY $fallos FALLO(S): pegame esta salida completa";
echo PHP_EOL . str_repeat('=', 60) . PHP_EOL;