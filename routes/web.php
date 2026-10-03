<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CuentaPorCobrarController;
use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\RutaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\AdministradorController;
use App\Http\Controllers\TwilioController;
use App\Http\Controllers\AbonoController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\TraspasoController;
use App\Http\Controllers\GenerarPdfController;
use App\Http\Controllers\ApartadoController;

Route::get('/', function () {
    /** @disregard P1013 */
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/seleccionar-sucursal', [SucursalController::class, 'selector'])->name('sucursales.selector');
    Route::post('/seleccionar-sucursal', [SucursalController::class, 'elegir'])->name('sucursales.elegir');

    Route::middleware('sucursal')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
        Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
        Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])->name('clientes.update');
        Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');

        // La ruta se conserva aunque ya no esté en el menú (Layout #3).
        Route::get('/cuentas-por-cobrar', [CuentaPorCobrarController::class, 'index'])
            ->name('cuentas-por-cobrar.index');

        Route::get('/abonos', [AbonoController::class, 'index'])->name('abonos.index');
        Route::get('/abonos/clientes/{cliente}/historial', [AbonoController::class, 'historial'])->name('abonos.historial');
        Route::get('/abonos/{abono}/recibo-historico', [ComprobanteController::class, 'reciboHistorico'])->name('abonos.recibo-historico');
        Route::get('/abonos/clientes/{cliente}/facturas', [AbonoController::class, 'facturas'])->name('abonos.facturas');
        Route::get('/abonos/facturas/{factura}/historica', [AbonoController::class, 'facturaHistorica'])->name('abonos.factura-historica');

        // Traspaso de cuenta (pantalla en construcción)
        Route::get('/traspaso', [TraspasoController::class, 'index'])->name('traspaso.index');
        Route::get('/traspaso/clientes', [TraspasoController::class, 'buscarClientes'])->name('traspaso.clientes');
        Route::get('/traspaso/{cliente}/facturas', [TraspasoController::class, 'facturasPendientes'])->name('traspaso.facturas');
        Route::post('/traspaso', [TraspasoController::class, 'store'])->name('traspaso.store');

        // Generar PDF (solo sucursal mayorista; el controlador rechaza las demás)
        Route::get('/generar-pdf', [GenerarPdfController::class, 'index'])->name('generar-pdf.index');
        Route::post('/generar-pdf', [GenerarPdfController::class, 'generar'])->name('generar-pdf.generar');

        // Facturación (etapa 4): pantalla + endpoints de búsqueda y guardado.
        // Los literales (clientes, productos) van antes de cualquier comodín para
        // evitar el mismo problema de orden de matching que ya pasó con /rutas.
        Route::get('/facturacion', [FacturacionController::class, 'index'])->name('facturacion.index');
        Route::get('/facturacion/clientes', [FacturacionController::class, 'buscarClientes'])->name('facturacion.clientes');
        Route::get('/facturacion/clientes/{cliente}/cuentas', [FacturacionController::class, 'cuentas'])->name('facturacion.cuentas');
        Route::get('/facturacion/productos', [FacturacionController::class, 'buscarProductos'])->name('facturacion.productos');
        Route::get('/facturacion/productos/{producto}', [FacturacionController::class, 'producto'])->name('facturacion.producto');
        Route::get('/facturacion/subcategorias', [FacturacionController::class, 'subcategorias'])->name('facturacion.subcategorias');
        Route::post('/facturacion', [FacturacionController::class, 'guardar'])->name('facturacion.guardar');
        Route::post('/facturas/{factura}/anular', [FacturacionController::class, 'anular'])->name('facturas.anular');

        // Comprobante (17.4) + fotos de comprobante de sinpe.
        Route::get('/operaciones/{operacion}/comprobante', [ComprobanteController::class, 'mostrar'])->name('operaciones.comprobante');
        Route::get('/operaciones/{operacion}/comprobante/pdf', [ComprobanteController::class, 'pdf'])->name('operaciones.comprobante.pdf');
        Route::get('/operaciones/{operacion}/foto-venta', [ComprobanteController::class, 'fotoVenta'])->name('operaciones.foto-venta');
        Route::get('/operaciones/{operacion}/foto-abono', [ComprobanteController::class, 'fotoAbono'])->name('operaciones.foto-abono');
        Route::post('/operaciones/{operacion}/recibo', [ComprobanteController::class, 'emitirRecibo'])->name('operaciones.recibo');
        Route::get('/operaciones/{operacion}/recibo-pdf', [ComprobanteController::class, 'verRecibo'])->name('operaciones.recibo.pdf');
        Route::post('/operaciones/{operacion}/recibo/reenviar', [ComprobanteController::class, 'reenviarRecibo'])->name('operaciones.recibo.reenviar');

        Route::get('/gastos', [GastoController::class, 'index'])->name('gastos.index');
        Route::get('/gastos/categorias', [GastoController::class, 'categorias'])->name('gastos.categorias');
        Route::post('/gastos', [GastoController::class, 'store'])->name('gastos.store');
        Route::put('/gastos/{gasto}', [GastoController::class, 'update'])->name('gastos.update');
        Route::delete('/gastos/{gasto}', [GastoController::class, 'destroy'])->name('gastos.destroy');

        // Apartados (pantalla nueva)

        Route::get('/apartados', [ApartadoController::class, 'index'])->name('apartados.index');
        Route::get('/apartados/contador', [ApartadoController::class, 'contador'])->name('apartados.contador');
        Route::get('/apartados/{apartado}', [ApartadoController::class, 'show'])->whereNumber('apartado')->name('apartados.show');
        Route::post('/apartados', [ApartadoController::class, 'store'])->name('apartados.store');
        Route::delete('/apartados/{apartado}', [ApartadoController::class, 'destroy'])->name('apartados.destroy');

        Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
        Route::put('/inventario/productos/{producto}', [InventarioController::class, 'update'])->name('inventario.productos.update');
        Route::put('/inventario/productos/{producto}/activo', [InventarioController::class, 'alternarActivo'])->name('inventario.productos.activo');
        Route::delete('/inventario/productos/{producto}', [InventarioController::class, 'destroy'])->name('inventario.productos.destroy');

        // IMPORTANTE: las rutas con segmento literal (gestionar, administrar, etc.)
        // deben ir ANTES de '/rutas/{tipo}/{filtro}'. Ambas tienen 3 segmentos en la
        // URL, y como Laravel matchea en el orden en que se registran, si el comodín
        // {tipo}/{filtro} queda primero, absorbe también peticiones como
        // GET /rutas/1/gestionar (interpretando tipo=1, filtro="gestionar") y nunca
        // llega a RutaController::gestionar, devolviendo 404.
        Route::get('/rutas', [RutaController::class, 'index'])->name('rutas.index');
        Route::get('/rutas/{ruta}/gestionar', [RutaController::class, 'gestionar'])->name('rutas.gestionar');
        Route::put('/rutas/{ruta}/administrar', [RutaController::class, 'administrar'])->name('rutas.administrar');
        Route::post('/rutas/{ruta}/reordenar', [RutaController::class, 'reordenar'])->name('rutas.reordenar');
        Route::post('/rutas/{ruta}/reiniciar', [RutaController::class, 'reiniciar'])->name('rutas.reiniciar');
        Route::post('/rutas/{ruta}/reportes', [RutaController::class, 'exportarReporte'])->name('rutas.reportes.store');
        Route::get('/rutas/{tipo}/{filtro}', [RutaController::class, 'obtener'])->name('rutas.obtener');
        Route::put('/ruta-clientes/{rutaCliente}', [RutaController::class, 'cambiarEstado'])->name('ruta-clientes.update');
        Route::put('/clientes/{cliente}/ubicacion', [ClienteController::class, 'guardarUbicacion'])->name('clientes.ubicacion');
        Route::post('/ruta-clientes/{rutaCliente}/no-abono', [RutaController::class, 'noAbono'])->name('ruta-clientes.no-abono');

        // Mi cuenta — el admin logueado edita la suya, sin pasar por el CRUD
        Route::put('/mi-cuenta', [AdministradorController::class, 'actualizarMiCuenta'])->name('cuenta.actualizar');

        // Solo superadmin: administradores, compras/proveedores y reportes
        Route::middleware('superadmin')->group(function () {
            Route::get('/administradores', [AdministradorController::class, 'index'])->name('administradores.index');
            Route::post('/administradores', [AdministradorController::class, 'store'])->name('administradores.store');
            Route::put('/administradores/{administrador}', [AdministradorController::class, 'update'])->name('administradores.update');
            Route::delete('/administradores/{administrador}', [AdministradorController::class, 'destroy'])->name('administradores.destroy');

            Route::get('/compras', [CompraController::class, 'index'])->name('compras.index');
            Route::get('/compras/proveedores', [CompraController::class, 'proveedores'])->name('compras.proveedores');
            Route::post('/compras', [CompraController::class, 'store'])->name('compras.store');
            Route::put('/compras/{compra}', [CompraController::class, 'update'])->name('compras.update');
            Route::delete('/compras/{compra}', [CompraController::class, 'destroy'])->name('compras.destroy');
            Route::post('/compras/{compra}/pagos', [CompraController::class, 'registrarPago'])->name('compras.pagos.store');
            Route::put('/compras/proveedores/{proveedor}', [CompraController::class, 'actualizarProveedor'])->name('compras.proveedores.update');
            Route::delete('/compras/proveedores/{proveedor}', [CompraController::class, 'eliminarProveedor'])->name('compras.proveedores.destroy');

            $tiposReporte = 'ventas|abonos|gastos|compras|inventario|rutas';
            Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
            Route::get('/reportes/{tipo}/excel', [ReporteController::class, 'excel'])->where('tipo', $tiposReporte)->name('reportes.excel');
            Route::get('/reportes/{tipo}', [ReporteController::class, 'ver'])->where('tipo', $tiposReporte)->name('reportes.ver');
        });
    });
});
