{{-- resources/views/admin/facturacion.blade.php --}}
@extends('layouts.app')

@section('titulo', 'Facturación')

@push('estilos')
    <style>
        .btn {
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            font-family: inherit;
        }

        .btn-primario {
            background: var(--azul-medio);
            color: #fff;
        }

        .btn-primario:hover {
            background: var(--azul-600);
        }

        .btn-secundario {
            background: #fff;
            border: 1px solid var(--borde);
            color: var(--texto);
        }

        .btn-peligro {
            background: none;
            color: #a30000;
        }

        .btn-texto {
            background: none;
            color: var(--azul-medio);
            padding: 4px 8px;
        }

        .btn:disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        .btn-bloque {
            width: 100%;
        }

        .campo {
            margin-bottom: 14px;
        }

        .campo:last-child {
            margin-bottom: 0;
        }

        .campo label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--texto-tenue);
            margin-bottom: 6px;
        }

        .campo input,
        .campo select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--borde);
            border-radius: 8px;
            font-size: max(13.5px, 16px);
            font-family: inherit;
            color: var(--texto);
            background: #fff;
        }

        .campo small {
            display: block;
            color: var(--texto-tenue);
            font-size: 12px;
            margin-top: 4px;
        }

        .campo small.aviso {
            color: #b95000;
        }

        .campo small.error {
            color: #a30000;
        }

        .fila-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .fila-2.desc {
            grid-template-columns: 90px 1fr;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-verde {
            background: #e7f7ee;
            color: #16794f;
        }

        .badge-rojo {
            background: #fdecec;
            color: #a30000;
        }

        .badge-ambar {
            background: #fff7e6;
            color: #8a5a00;
        }

        .badge-gris {
            background: #eef1f6;
            color: var(--texto-tenue);
        }

        .badge-azul {
            background: var(--azul-100);
            color: var(--azul-oscuro);
        }

        .tarjeta {
            background: var(--superficie);
            border: 1px solid var(--borde);
            border-radius: 12px;
            padding: 16px;
            box-shadow: var(--sombra-card);
            margin-bottom: 16px;
        }

        .tarjeta h3 {
            margin: 0 0 12px;
            font-size: 15px;
            color: var(--azul-oscuro);
        }

        /* Buscador con sugerencias (cliente / producto) */
        .buscador-caja {
            position: relative;
        }

        .buscador-caja input {
            width: 100%;
        }

        .sugerencias {
            display: none;
            position: absolute;
            left: 0;
            right: 0;
            top: 100%;
            margin-top: 4px;
            background: #fff;
            border: 1px solid var(--borde);
            border-radius: 8px;
            box-shadow: var(--sombra-hover);
            max-height: 220px;
            overflow-y: auto;
            z-index: 20;
        }

        .sugerencias.abierto {
            display: block;
        }

        .sugerencia-item {
            padding: 10px 12px;
            font-size: 14px;
            cursor: pointer;
        }

        .sugerencia-item:hover {
            background: var(--azul-50);
        }

        .sugerencia-item .sug-nombre {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .sugerencia-item .sec {
            font-size: 12px;
            color: var(--texto-tenue);
        }

        /* Cliente seleccionado */
        .cliente-chip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            background: var(--azul-50);
            border: 1px solid var(--azul-100);
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .cliente-chip .nombre {
            font-weight: 700;
            font-size: 15px;
            color: var(--azul-oscuro);
        }

        .cliente-chip .meta {
            font-size: 12px;
            color: var(--texto-tenue);
            margin-top: 2px;
        }

        .cliente-chip .saldo {
            text-align: right;
        }

        .cliente-chip .saldo strong {
            font-size: 16px;
        }

        .saldo-deuda {
            color: #b42318;
        }

        .saldo-favor {
            color: #067647;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 8px;
            background: #eef1f6;
            border-radius: 10px;
            padding: 4px;
            overflow-x: auto;
        }

        .tab-btn {
            flex: 1;
            text-align: center;
            padding: 9px 10px;
            border: none;
            background: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--texto-tenue);
            cursor: pointer;
            white-space: nowrap;
            font-family: inherit;
            position: relative;
        }

        .tab-btn.activo {
            background: #fff;
            color: var(--azul-oscuro);
            box-shadow: var(--sombra-card);
        }

        /* Indicador numérico: cuántas cosas ya se agregaron en esa pestaña */
        .tab-btn .punto {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            background: var(--azul-medio);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            margin-left: 6px;
            line-height: 1;
        }

        .tab-btn:disabled {
            opacity: .4;
            cursor: not-allowed;
        }

        .ayuda-tabs {
            font-size: 12px;
            color: var(--texto-tenue);
            margin: 0 0 16px;
        }

        .seccion {
            display: none;
        }

        .seccion.activa {
            display: block;
        }

        .fila-linea {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid var(--borde);
        }

        .fila-linea:last-child {
            border-bottom: none;
        }

        .fila-linea .desc {
            font-size: 14px;
            font-weight: 600;
        }

        .fila-linea .sub {
            font-size: 12px;
            color: var(--texto-tenue);
        }

        .fila-linea .monto {
            font-weight: 700;
            white-space: nowrap;
        }

        .radio-lista {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .radio-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border: 1px solid var(--borde);
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        .radio-item input {
            width: auto;
        }

        .radio-item .der {
            margin-left: auto;
            font-weight: 600;
        }

        .radio-item.bloqueada {
            opacity: .5;
            cursor: not-allowed;
            background: #f6f7f9;
        }

        .radio-item .nota-bloqueo {
            font-size: 12px;
            color: var(--texto-tenue);
        }

        .fila-dev {
            align-items: center;
        }

        .fila-dev .dev-info {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            min-width: 160px;
            cursor: pointer;
        }

        .fila-dev .dev-info input[type="checkbox"] {
            width: 19px;
            height: 19px;
            flex-shrink: 0;
        }

        .stepper-dev {
            align-items: center;
            gap: 8px;
        }

        .btn-stepper {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            border: 1px solid var(--borde);
            background: #fff;
            color: var(--azul-medio);
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .btn-stepper:active {
            background: var(--azul-50);
        }

        .dev-cant-visible {
            min-width: 22px;
            text-align: center;
            font-weight: 700;
            font-size: 14px;
        }

        .resumen-final {
            background: var(--azul-oscuro);
            color: #fff;
            border-radius: 12px;
            padding: 16px;
            margin-top: 8px;
        }

        .resumen-final .fila {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            padding: 3px 0;
            opacity: .85;
        }

        .resumen-final .fila.total {
            font-size: 17px;
            font-weight: 700;
            opacity: 1;
            border-top: 1px solid rgba(255, 255, 255, .25);
            margin-top: 8px;
            padding-top: 10px;
        }

        .estado-vacio {
            text-align: center;
            color: var(--texto-tenue);
            padding: 24px 10px;
            font-size: 14px;
        }

        .acciones-flotantes {
            display: flex;
            gap: 10px;
            margin-top: 16px;
        }

        .acciones-flotantes .btn {
            flex: 1;
        }

        .tarjeta-venta-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        .tarjeta-venta-header h3 {
            margin: 0;
        }

        @media (max-width: 720px) {
            .fila-2 {
                grid-template-columns: 1fr;
            }

            .tabs {
                position: sticky;
                top: 0;
                z-index: 15;
            }
        }

        /* Buscadores (cliente y producto): mismo estilo que los demás campos */
        .buscador-caja input {
            width: 100%;
            padding: 9px 12px 9px 36px;
            border: 1px solid var(--borde);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            color: var(--texto);
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2398a2b3' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='7'/%3E%3Cpath d='m20 20-3.5-3.5'/%3E%3C/svg%3E") no-repeat 11px center / 16px;
        }

        .buscador-caja input:focus {
            outline: none;
            border-color: var(--azul-medio);
        }

        /* El buscador de cliente es más angosto */
        .buscador-cliente {
            max-width: 420px;
            margin-bottom: 4px;
        }

        /* Botones principales más pequeños y centrados */
        .acciones-flotantes {
            justify-content: flex-end;
        }

        .acciones-flotantes .btn {
            flex: 0 1 190px;
        }

        .seccion>.btn-bloque {
            display: block;
            width: auto;
            min-width: 240px;
            margin: 0 0 0 auto;
            padding: 10px 28px;
        }

        @media (max-width: 720px) {
            .buscador-caja input {
                font-size: 16px;
                /* evita el zoom automático de iOS */
            }

            .buscador-cliente {
                max-width: 100%;
            }

            .acciones-flotantes .btn {
                flex: 1;
            }

            .seccion>.btn-bloque {
                width: 100%;
            }
        }

        /* Buscador y mensaje inicial en la misma fila */
        .fila-buscador {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .fila-buscador .buscador-cliente {
            flex: 0 1 420px;
            margin-bottom: 0;
        }

        #sin-cliente {
            text-align: left;
            padding: 0;
        }
    </style>
@endpush

@section('contenido')

    <div class="fila-buscador">
        <div class="buscador-caja buscador-cliente">
            <input type="text" id="buscar-cliente" placeholder="Buscar cliente por nombre, código o teléfono"
                autocomplete="off">
            <div class="sugerencias" id="sug-cliente"></div>
        </div>

        <div id="sin-cliente" class="estado-vacio">Buscá y elegí un cliente para empezar.</div>
    </div>

    <div id="con-cliente" style="display:none;">
        <div class="cliente-chip">
            <div>
                <div class="nombre" id="cli-nombre"></div>
                <div class="meta" id="cli-meta"></div>
            </div>
            <div class="saldo">
                <div id="cli-saldo" style="font-size:12px;color:var(--texto-tenue);">Saldo</div>
                <strong id="cli-saldo-valor"></strong>
            </div>
            <button type="button" class="btn btn-texto" onclick="quitarCliente()">Cambiar</button>
        </div>

        <div class="tabs">
            <button type="button" class="tab-btn activo" data-tab="principal"
                onclick="irATab('principal')">Principal</button>
            <button type="button" class="tab-btn" data-tab="venta" id="tab-venta" onclick="irATab('venta')">Venta<span
                    class="punto" id="punto-venta" style="display:none;"></span></button>
            <button type="button" class="tab-btn" data-tab="abono" onclick="irATab('abono')">Abono<span class="punto"
                    id="punto-abono" style="display:none;"></span></button>
            <button type="button" class="tab-btn" data-tab="devolucion" onclick="irATab('devolucion')">Devolución<span
                    class="punto" id="punto-devolucion" style="display:none;"></span></button>
        </div>
        <p class="ayuda-tabs">El número azul indica lo que ya agregaste en esa pestaña. Todo se guarda junto desde
            Principal.</p>

        {{-- ===================== PRINCIPAL ===================== --}}
        <div class="seccion activa" id="seccion-principal">
            <div id="lista-principal"></div>

            <div class="tarjeta">
                <label style="display:flex; align-items:center; gap:10px; font-size:14px; cursor:pointer;">
                    <input type="checkbox" id="chk-no-abono" style="width:auto;" onchange="alternarNoAbono()">
                    El cliente no abonó ni compró ni devolvió nada en esta visita
                </label>
                <div class="campo" id="campo-no-abono-descripcion" style="display:none; margin-top:12px;">
                    <label>Descripción (opcional, se incluye en el comprobante)</label>
                    <input type="text" id="no-abono-descripcion" maxlength="255"
                        placeholder="Ej: no había nadie en el local">
                </div>
            </div>

            <div class="resumen-final">
                <div class="fila"><span>Saldo inicial</span><span id="r-inicial">₡0.00</span></div>
                <div class="fila"><span>Devoluciones</span><span id="r-devolucion">− ₡0.00</span></div>
                <div class="fila"><span>Venta a crédito</span><span id="r-credito">+ ₡0.00</span></div>
                <div class="fila"><span>Saldo a favor aplicado</span><span id="r-favor">₡0.00</span></div>
                <div class="fila"><span>Abono</span><span id="r-abono">− ₡0.00</span></div>
                <div class="fila total"><span>Saldo final estimado</span><span id="r-final">₡0.00</span></div>
            </div>

            <div class="acciones-flotantes">
                <button type="button" class="btn btn-secundario" onclick="reiniciarTodo(true)">Cancelar visita</button>
                <button type="button" class="btn btn-primario" id="btn-guardar"
                    onclick="guardarOperacion()">Guardar</button>
            </div>
        </div>

        {{-- ===================== VENTA ===================== --}}
        <div class="seccion" id="seccion-venta">
            <div class="tarjeta" id="nota-apartado" style="display:none;">
    <h3>Nota del apartado</h3>
    <div id="nota-apartado-texto" style="font-size:14px; overflow-wrap:anywhere;"></div>
</div>
            <div class="tarjeta">
                <h3>Producto</h3>
                <div class="buscador-caja">
                    <input type="text" id="buscar-producto" placeholder="Buscar por nombre, código o subcategoría"
                        autocomplete="off">
                    <div class="sugerencias" id="sug-producto"></div>
                </div>

                <div id="producto-elegido" style="display:none; margin-top:14px;">
                    <p style="margin:0 0 10px; font-size:14px;"><strong id="pe-nombre"></strong> <span id="pe-stock"
                            class="badge badge-verde" style="margin-left:6px;"></span></p>
                    <div class="fila-2">
                        <div class="campo">
                            <label>Cantidad</label>
                            <input type="number" id="pe-cantidad" min="1" step="1" value="1">
                        </div>
                        <div class="campo" id="campo-pe-precio" style="display:none;">
                            <label>Precio (no tiene precio cargado)</label>
                            <input type="number" id="pe-precio" min="0.01" step="0.01">
                        </div>
                    </div>
                    <div class="campo">
                        <label>Descuento</label>
                        <div class="fila-2 desc">
                            <select id="pe-descuento-tipo">
                                <option value="monto">₡</option>
                                <option value="porcentaje">%</option>
                            </select>
                            <input type="number" id="pe-descuento-valor" min="0" step="0.01" value="0">
                        </div>
                    </div>
                    <button type="button" class="btn btn-secundario btn-bloque" onclick="agregarLineaVenta()">Agregar
                        producto</button>
                </div>
            </div>

            <div class="tarjeta" id="tarjeta-lineas-venta" style="display:none;">
                <h3>Productos agregados</h3>
                <div id="lista-lineas-venta"></div>
                <p style="text-align:right; margin:10px 0 0; font-weight:700;">Total: <span id="venta-total">₡0.00</span>
                </p>
            </div>

            <div class="tarjeta">
                <h3>Forma de pago</h3>
                <div class="campo">
                    <label>Dirección de entrega</label>
                    <input type="text" id="venta-direccion" placeholder="Dirección">
                </div>
                <div class="fila-2">
                    <label class="radio-item"><input type="radio" name="venta-tipo" value="contado" checked
                            onchange="cambiarTipoVenta()"> Contado</label>
                    <label class="radio-item"><input type="radio" name="venta-tipo" value="credito"
                            onchange="cambiarTipoVenta()"> Crédito</label>
                </div>

                <div id="venta-campos-credito" style="margin-top:14px; display:none;">
                    <div class="campo">
                        <label>Plazo</label>
                        <select id="venta-plazo">
                            <option value="15">15 días</option>
                            <option value="30">30 días</option>
                            <option value="45">45 días</option>
                        </select>
                    </div>
                </div>

                <div id="venta-campos-contado" style="margin-top:14px;">
                    <div class="fila-2">
                        <div class="campo">
                            <label>Efectivo</label>
                            <input type="number" id="venta-efectivo" min="0" step="0.01" value="0"
                                oninput="recalcularVentaContado()">
                        </div>
                        <div class="campo">
                            <label>Sinpe</label>
                            <input type="number" id="venta-sinpe" min="0" step="0.01" value="0"
                                oninput="recalcularVentaContado()">
                        </div>
                    </div>
                    <div class="campo" id="campo-foto-venta" style="display:none;">
                        <label>Foto del comprobante sinpe</label>
                        <input type="file" id="venta-foto" accept="image/*"
                            onchange="fotoVenta = this.files[0] || null">
                        <small>Si ya hiciste otra venta de contado con sinpe en esta visita, no hace falta volver a subirla:
                            una sola foto cubre el total acumulado.</small>
                    </div>
                    <p id="venta-hint-pago" style="font-size:13px; color:var(--texto-tenue); margin:10px 0 0;"></p>
                </div>
            </div>

            <button type="button" class="btn btn-primario btn-bloque" onclick="confirmarVenta()">Agregar venta a la
                lista</button>
        </div>

        {{-- ===================== ABONO ===================== --}}
        <div class="seccion" id="seccion-abono">
            <div class="tarjeta">
                <h3>¿A qué cuenta abonar?</h3>
                <div class="radio-lista" id="lista-cuentas-abono"></div>
            </div>

            <div class="tarjeta">
                <h3>Monto</h3>
                <div class="fila-2">
                    <div class="campo">
                        <label>Efectivo</label>
                        <input type="number" id="abono-efectivo" min="0" step="0.01" value="0"
                            oninput="recalcularAbono()">
                    </div>
                    <div class="campo">
                        <label>Sinpe</label>
                        <input type="number" id="abono-sinpe" min="0" step="0.01" value="0"
                            oninput="recalcularAbono()">
                    </div>
                </div>
                <div class="campo" id="campo-foto-abono" style="display:none;">
                    <label>Foto del comprobante sinpe</label>
                    <input type="file" id="abono-foto" accept="image/*" onchange="fotoAbono = this.files[0] || null">
                </div>
                <p id="abono-hint" style="font-size:13px; color:var(--texto-tenue); margin:10px 0 0;"></p>
            </div>

            <button type="button" class="btn btn-primario btn-bloque" onclick="confirmarAbono()">Agregar abono a la
                lista</button>
        </div>

        {{-- ===================== DEVOLUCIÓN ===================== --}}
        <div class="seccion" id="seccion-devolucion">
            <div class="tarjeta">
                <h3>Factura</h3>
                <div class="campo">
                    <select id="dev-factura" onchange="elegirFacturaDevolucion()">
                        <option value="">Elegí una factura</option>
                    </select>
                </div>
                <div class="campo">
                    <label>Motivo</label>
                    <select id="dev-motivo">
                        <option value="nota_credito">Nota de crédito</option>
                        <option value="baja_rotacion">Baja rotación</option>
                        <option value="producto_mal_estado">Producto en mal estado</option>
                        <option value="no_pedido">No era lo pedido</option>
                    </select>
                </div>
            </div>

            <div class="tarjeta" id="tarjeta-lineas-dev" style="display:none;">
                <h3>Productos de esa factura</h3>
                <div id="lista-lineas-dev"></div>
            </div>

            <button type="button" class="btn btn-primario btn-bloque" onclick="confirmarDevolucion()">Agregar devolución
                a la lista</button>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const token = document.querySelector('meta[name="csrf-token"]').content;
        const CANAL_SUCURSAL = @json($canalSucursal);
        const MUEVE_STOCK = CANAL_SUCURSAL === 'normal';
        const MOTIVOS_LABEL = {
            nota_credito: 'Nota de crédito',
            baja_rotacion: 'Baja rotación',
            producto_mal_estado: 'Producto en mal estado',
            no_pedido: 'No era lo pedido',
        };

        // URL de "cuentas de cliente" armada con route(); '__ID__' se reemplaza en JS
        // porque en este punto todavía no sabemos qué cliente se va a elegir.
        const URL_CUENTAS = @json(route('facturacion.cuentas', ['cliente' => '__ID__']));

        // Parámetros con los que se pudo haber entrado desde Rutas
        const PARAM_CLIENTE_ID = @json(request()->query('cliente_id'));
        const PARAM_RUTA_CLIENTE_ID = @json(request()->query('ruta_cliente_id'));
        const PARAM_OPERACION = @json(request()->query('operacion'));
        const PARAM_APARTADO_ID = @json(request()->query('apartado_id'));

        // 17.4 — URL del comprobante; '__ID__' se reemplaza con el id de la
        // operación recién guardada.
        const URL_COMPROBANTE = @json(route('operaciones.comprobante', ['operacion' => '__ID__']));
        const URL_RECIBO = @json(route('operaciones.recibo', ['operacion' => '__ID__']));
        const URL_APARTADO = @json(route('apartados.show', ['apartado' => '__ID__']));
        const BORRADOR_KEY = 'facturacion_borrador';

        let cliente = null;
        let cuentas = null;
        let venta = null; // acumula todas las confirmaciones de venta de la visita (17.2)
        let ventaBloqueada = false; // true cuando ya se confirmó una venta a crédito
        let abono = null;
        let devoluciones = [];
        let noAbono = false;
        let noAbonoDescripcion = '';

        // Bloque 5 #1 — ruta_cliente_id de ESTA visita. Solo vale para el cliente con
        // el que se entró desde Rutas; si se cambia de cliente se descarta. Antes se
        // mandaba siempre el de la URL, aunque la visita fuera de otro cliente.
        let rutaClienteVisita = null;

        let productoElegido = null;
        let colaApartadoProductos = []; // productos pendientes de cargar uno por uno al Comprar desde Apartados
        let lineasVentaTmp = [];
        let fotoVenta = null;
        let fotoAbono = null;
        let facturaDevActual = null;
        let cantidadesDevolucion = {}; // índice de línea -> cantidad a devolver (17.4b: checkbox + stepper)

        const $ = (id) => document.getElementById(id);
        const colones = (v) => '₡' + Number(v || 0).toLocaleString('es-CR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        // ---------------- Borrador (localStorage) ----------------
        // Guarda lo agregado en la visita para que un refresh accidental (muy común
        // en celular en ruta) no borre todo. Las fotos de sinpe NO se pueden guardar
        // (no son serializables), así que si había una venta/abono con sinpe > 0 se
        // avisa al restaurar que hay que volver a adjuntarla antes de guardar.

        function guardarBorrador() {
            if (!cliente || !(venta || abono || devoluciones.length || noAbono)) {
                localStorage.removeItem(BORRADOR_KEY);
                return;
            }
            try {
                localStorage.setItem(BORRADOR_KEY, JSON.stringify({
                    clienteId: cliente.id,
                    rutaClienteId: rutaClienteVisita,
                    venta,
                    abono,
                    devoluciones,
                    noAbono,
                    noAbonoDescripcion,
                }));
            } catch (e) {
                // localStorage lleno o bloqueado (modo incógnito, etc.): seguimos sin borrador
            }
        }

        function borrarBorrador() {
            localStorage.removeItem(BORRADOR_KEY);
        }

        async function intentarRestaurarBorrador() {
            if (PARAM_CLIENTE_ID) return false; // si vino desde Rutas, esa visita manda

            let borrador;
            try {
                borrador = JSON.parse(localStorage.getItem(BORRADOR_KEY) || 'null');
            } catch (e) {
                borrarBorrador();
                return false;
            }
            if (!borrador || !borrador.clienteId) return false;

            const r = await Swal.fire({
                title: 'Visita sin guardar',
                text: 'Quedó una visita a medio hacer de la última vez. ¿Querés continuarla?',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Continuar',
                cancelButtonText: 'Descartar',
            });
            if (!r.isConfirmed) {
                borrarBorrador();
                return false;
            }

            const ok = await seleccionarCliente(borrador.clienteId, true);
            if (!ok) {
                borrarBorrador();
                return false;
            }

            rutaClienteVisita = borrador.rutaClienteId ? parseInt(borrador.rutaClienteId, 10) : null;
            venta = borrador.venta ?? null;
            abono = borrador.abono ?? null;
            devoluciones = borrador.devoluciones ?? [];
            noAbono = borrador.noAbono ?? false;
            noAbonoDescripcion = borrador.noAbonoDescripcion ?? '';

            $('chk-no-abono').checked = noAbono;
            $('campo-no-abono-descripcion').style.display = noAbono ? 'block' : 'none';
            $('no-abono-descripcion').value = noAbonoDescripcion;
            document.querySelectorAll('.tab-btn[data-tab]:not([data-tab="principal"])').forEach(b => b.disabled =
                noAbono);
            $('btn-guardar').textContent = noAbono ? 'Guardar visita sin movimientos' : 'Guardar';

            // 17.2 — si la venta restaurada ya era a crédito, la pestaña Venta sigue bloqueada
            if (venta && venta.tipo === 'credito') {
                ventaBloqueada = true;
                $('tab-venta').disabled = true;
            }

            pintarPrincipal();

            const faltaFoto = (venta && venta.sinpe > 0) || (abono && abono.sinpe > 0);
            if (faltaFoto) {
                Swal.fire('Falta una foto',
                    'La visita restaurada tenía un pago con sinpe. Volvé a subir la foto del comprobante en esa pestaña antes de guardar.',
                    'warning');
            }
            return true;
        }

        // ---------------- Cliente ----------------

        let temporizadorCliente = null;
        $('buscar-cliente').addEventListener('input', (e) => {
            clearTimeout(temporizadorCliente);
            const q = e.target.value.trim();
            if (q.length < 2) {
                $('sug-cliente').classList.remove('abierto');
                return;
            }
            temporizadorCliente = setTimeout(async () => {
                const r = await fetch(
                    `{{ route('facturacion.clientes') }}?buscar=${encodeURIComponent(q)}`, {
                        headers: {
                            Accept: 'application/json'
                        }
                    });
                const lista = await r.json();
                $('sug-cliente').innerHTML = lista.map(c => `
                <div class="sugerencia-item" onclick="seleccionarCliente(${c.id})">
                    ${c.nombre}<div class="sec">${c.codigo ?? 'sin código'} · ${c.telefono ?? 'sin teléfono'}</div>
                </div>`).join('') || '<div class="sugerencia-item sec">Sin resultados</div>';
                $('sug-cliente').classList.add('abierto');
            }, 300);
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.buscador-caja')) {
                document.querySelectorAll('.sugerencias').forEach(s => s.classList.remove('abierto'));
            }
        });

        $('no-abono-descripcion').addEventListener('input', (e) => {
            noAbonoDescripcion = e.target.value;
            guardarBorrador();
        });

        /**
         * @param {number} id
         * @param {boolean} esRestauracion Si viene de restaurar un borrador: no
         *   pregunta si se quiere cambiar de cliente ni vacía nada, y no redirige
         *   de pestaña según el parámetro de Rutas.
         */
        async function seleccionarCliente(id, esRestauracion = false) {
            if (!esRestauracion) {
                if (venta || abono || devoluciones.length) {
                    const r = await Swal.fire({
                        title: '¿Cambiar de cliente?',
                        text: 'Se va a vaciar todo lo agregado en esta visita.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, cambiar',
                        cancelButtonText: 'Cancelar',
                    });
                    if (!r.isConfirmed) return false;
                }
                reiniciarTodo(false);
                rutaClienteVisita = null; // un cliente nuevo no hereda la ruta de la visita anterior
            }

            const r = await fetch(URL_CUENTAS.replace('__ID__', id), {
                headers: {
                    Accept: 'application/json'
                }
            });
            if (!r.ok) {
                Swal.fire('Error', 'No se pudo cargar el cliente.', 'error');
                return false;
            }
            const datos = await r.json();

            cliente = datos.cliente;
            cuentas = datos;
            $('buscar-cliente').value = '';
            $('sug-cliente').classList.remove('abierto');
            pintarCliente();
            pintarCuentasAbono();
            pintarFacturasDevolucion();
            $('sin-cliente').style.display = 'none';
            $('con-cliente').style.display = 'block';
            $('venta-direccion').value = cliente.direccion ?? '';

            if (!esRestauracion) {
                if (PARAM_OPERACION === 'abonar') irATab('abono');
                else if (PARAM_OPERACION === 'compra') irATab('venta');
                else if (PARAM_OPERACION === 'devolucion') irATab('devolucion');
                guardarBorrador();
            }
            return true;
        }


        /** Quita ?cliente_id=...&operacion=... de la URL para que F5 no vuelva a cargar esa visita. */
        function limpiarUrl() {
            if (location.search) history.replaceState(null, '', location.pathname);
        }

        function quitarCliente() {
            cliente = null;
            cuentas = null;
            rutaClienteVisita = null;
            reiniciarTodo(false);
            $('sin-cliente').style.display = 'block';
            $('con-cliente').style.display = 'none';
            borrarBorrador();
            limpiarUrl(); // ← nueva
        }

        function pintarCliente() {
            $('cli-nombre').textContent = cliente.nombre;
            $('cli-meta').textContent = `${cliente.codigo ?? 'sin código'} · ${cliente.telefono ?? 'sin teléfono'}` +
                (cliente.maximocredito > 0 ? ` · Límite ${colones(cliente.maximocredito)}` : '');
            const saldo = cliente.saldo_actual;
            $('cli-saldo').textContent = saldo < 0 ? 'A favor' : 'Debe';
            $('cli-saldo-valor').textContent = colones(Math.abs(saldo));
            $('cli-saldo-valor').className = saldo < 0 ? 'saldo-favor' : (saldo > 0 ? 'saldo-deuda' : '');
            actualizarResumen();
        }

        // ---------------- Tabs ----------------

        function irATab(nombre) {
            if (noAbono && nombre !== 'principal') return;
            if (nombre === 'venta' && ventaBloqueada) return;
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('activo', b.dataset.tab === nombre));
            document.querySelectorAll('.seccion').forEach(s => s.classList.toggle('activa', s.id === `seccion-${nombre}`));
            if (nombre === 'abono' && cliente) recalcularAbono();
        }

        function alternarNoAbono() {
            if (venta || abono || devoluciones.length) {
                $('chk-no-abono').checked = false;
                Swal.fire('No se puede', 'Ya hay una venta, abono o devolución agregada en esta visita.', 'info');
                return;
            }
            noAbono = $('chk-no-abono').checked;
            $('campo-no-abono-descripcion').style.display = noAbono ? 'block' : 'none';
            if (!noAbono) {
                noAbonoDescripcion = '';
                $('no-abono-descripcion').value = '';
            }
            document.querySelectorAll('.tab-btn[data-tab]:not([data-tab="principal"])').forEach(b => b.disabled = noAbono);
            $('btn-guardar').textContent = noAbono ? 'Guardar visita sin movimientos' : 'Guardar';
            actualizarResumen();
            guardarBorrador();
        }

        /**
         * Bloque 5 #4 — indicador numérico de cada pestaña (antes era un punto sin
         * significado claro): cuántos productos hay en la venta, si hay abono y
         * cuántas devoluciones. Se recalcula cada vez que se repinta Principal.
         */
        function actualizarIndicadores() {
            const items = [
                ['punto-venta', venta?._filas?.length ?? 0, 'producto(s) en la venta'],
                ['punto-abono', abono ? 1 : 0, 'abono agregado'],
                ['punto-devolucion', devoluciones.length, 'devolución(es) agregada(s)'],
            ];
            items.forEach(([id, n, texto]) => {
                const el = $(id);
                el.textContent = n > 0 ? n : '';
                el.title = n > 0 ? `${n} ${texto}` : '';
                el.style.display = n > 0 ? 'inline-flex' : 'none';
            });
        }

        // ---------------- Venta: buscar producto ----------------
        // 17.3 — buscador normal con autocompletado; cada sugerencia ya muestra
        // subcategoría y talla (esta última solo aparece cuando el producto es de
        // Azur, porque solo esos tienen variante con talla).

        let temporizadorProducto = null;
        $('buscar-producto').addEventListener('input', (e) => {
            clearTimeout(temporizadorProducto);
            const q = e.target.value.trim();
            if (q.length < 2) {
                $('sug-producto').classList.remove('abierto');
                return;
            }
            temporizadorProducto = setTimeout(async () => {
                const r = await fetch(
                    `{{ route('facturacion.productos') }}?buscar=${encodeURIComponent(q)}`, {
                        headers: {
                            Accept: 'application/json'
                        }
                    });
                const lista = await r.json();
                $('sug-producto').innerHTML = lista.map(p => {
                    // Categoría - Subcategoría / Talla: X (talla solo si el producto
                    // tiene variante con talla, o sea, en Azur)
                    const catSub = [p.categoria, p.subcategoria].filter(Boolean).join(' - ') ||
                        'Sin categoría';
                    const talla = p.variantes[0]?.talla ? ` / Talla: ${p.variantes[0].talla}` :
                        '';
                    const precio = p.precio !== null ? colones(p.precio) : 'sin precio';
                    return `
                <div class="sugerencia-item" onclick='elegirProducto(${JSON.stringify(p).replace(/'/g, "&#39;")})'>
                    <div class="sug-nombre">${p.nombre}</div>
                    <div class="sec">${catSub}${talla}</div>
                    <div class="sec" style="opacity:.75;">${p.codigo ?? 'sin código'} · ${precio}</div>
                </div>`;
                }).join('') || '<div class="sugerencia-item sec">Sin resultados</div>';
                $('sug-producto').classList.add('abierto');
            }, 300);
        });

        function elegirProducto(p) {
            productoElegido = p;
            $('buscar-producto').value = '';
            $('sug-producto').classList.remove('abierto');
            $('pe-nombre').textContent = p.nombre;
            const variante = p.variantes[0] ?? null;
            const stock = variante ? variante.stock : null;

            if (MUEVE_STOCK) {
                $('pe-stock').style.display = 'inline-flex';
                $('pe-stock').textContent = stock > 0 ? `${stock} en stock` : 'Sin stock';
                $('pe-stock').className = 'badge ' + (stock > 0 ? 'badge-verde' : 'badge-rojo');
                $('pe-cantidad').max = stock || 0;
            } else {
                $('pe-stock').style.display = 'none';
            }

            $('campo-pe-precio').style.display = p.precio === null ? 'block' : 'none';
            $('pe-precio').value = '';
            $('pe-cantidad').value = 1;
            $('pe-descuento-tipo').value = 'monto';
            $('pe-descuento-valor').value = 0;
            $('producto-elegido').style.display = 'block';
        }

        function agregarLineaVenta() {
            if (!productoElegido) return;
            const cantidad = parseInt($('pe-cantidad').value || '0', 10);
            if (cantidad < 1) {
                Swal.fire('Cantidad inválida', 'Escribí una cantidad mayor a 0.', 'error');
                return;
            }

            const variante = productoElegido.variantes[0] ?? null;
            if (MUEVE_STOCK) {
                if (!variante) {
                    Swal.fire('Sin variante', 'Este producto no tiene variante configurada en el catálogo.', 'error');
                    return;
                }
                if (cantidad > variante.stock) {
                    Swal.fire('Stock insuficiente', `Hay ${variante.stock} unidades disponibles.`, 'error');
                    return;
                }
            }

            let precio = productoElegido.precio;
            if (precio === null) {
                precio = parseFloat($('pe-precio').value || '0');
                if (precio <= 0) {
                    Swal.fire('Precio requerido', 'Este producto no tiene precio. Escribilo para poder agregarlo.',
                        'error');
                    return;
                }
            }

            const descuentoTipo = $('pe-descuento-tipo').value;
            const descuentoValor = parseFloat($('pe-descuento-valor').value || '0');

            lineasVentaTmp.push({
                producto_id: productoElegido.id,
                producto_variante_id: variante ? variante.id : null,
                nombre: productoElegido.nombre,
                cantidad,
                precio_unit: precio,
                descuento_tipo: descuentoTipo,
                descuento: descuentoValor,
            });

            productoElegido = null;
            $('producto-elegido').style.display = 'none';
            pintarLineasVenta();
            productoElegido = null;
            $('producto-elegido').style.display = 'none';
            pintarLineasVenta();
            avanzarColaApartado(); // ← esta línea nueva al final
        }

        function quitarLineaVenta(i) {
            lineasVentaTmp.splice(i, 1);
            pintarLineasVenta();
        }

        function totalLinea(l) {
            const bruto = l.cantidad * l.precio_unit;
            const desc = l.descuento_tipo === 'porcentaje' ? bruto * l.descuento / 100 : l.descuento;
            return Math.max(0, bruto - Math.min(desc, bruto));
        }

        function pintarLineasVenta() {
            $('tarjeta-lineas-venta').style.display = lineasVentaTmp.length ? 'block' : 'none';
            $('lista-lineas-venta').innerHTML = lineasVentaTmp.map((l, i) => `
            <div class="fila-linea">
                <div>
                    <div class="desc">${l.nombre}</div>
                    <div class="sub">${l.cantidad} × ${colones(l.precio_unit)}${l.descuento > 0 ? ' · desc. ' + (l.descuento_tipo === 'porcentaje' ? l.descuento + '%' : colones(l.descuento)) : ''}</div>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <span class="monto">${colones(totalLinea(l))}</span>
                    <button type="button" class="btn btn-texto" onclick="quitarLineaVenta(${i})">Quitar</button>
                </div>
            </div>`).join('');
            $('venta-total').textContent = colones(lineasVentaTmp.reduce((a, l) => a + totalLinea(l), 0));
        }

        function cambiarTipoVenta() {
            const tipo = document.querySelector('input[name="venta-tipo"]:checked').value;
            $('venta-campos-credito').style.display = tipo === 'credito' ? 'block' : 'none';
            $('venta-campos-contado').style.display = tipo === 'contado' ? 'block' : 'none';
            recalcularVentaContado();
        }

        function totalVentaTmp() {
            return lineasVentaTmp.reduce((a, l) => a + totalLinea(l), 0);
        }

        /**
         * Saldo del cliente descontando las devoluciones ya agregadas en esta
         * visita (s1 de la fórmula de OperacionService, sección 9.6). La venta
         * de contado se calcula sobre este saldo, no sobre cliente.saldo_actual
         * a secas, porque si ya hay una devolución cargada el saldo a favor
         * disponible cambió.
         */
        function saldoTrasDevoluciones() {
            if (!cliente) return 0;
            const devolucionTotal = devoluciones.reduce((a, d) => a + (d._estimado || 0), 0);
            return cliente.saldo_actual - devolucionTotal;
        }

        /**
         * 17.2 — saldo a favor todavía disponible para aplicar a una NUEVA
         * confirmación de venta de contado, descontando lo que ya se aplicó en
         * confirmaciones de contado previas de esta misma visita.
         */
        function favorDisponible() {
            const s1 = saldoTrasDevoluciones();
            const favorTotal = Math.max(0, -s1);
            const yaAplicado = (venta && venta.tipo === 'contado') ? (venta._favorAplicado || 0) : 0;
            return Math.max(0, favorTotal - yaAplicado);
        }

        /** s2 de la fórmula de 9.6: saldo tras devoluciones + venta (crédito suma, contado ya trae su favor aplicado acumulado). */
        function calcularS2() {
            const s1 = saldoTrasDevoluciones();
            let favorAplicado = 0,
                ventaCredito = 0;
            if (venta) {
                if (venta.tipo === 'credito') ventaCredito = venta._total;
                else favorAplicado = venta._favorAplicado || 0;
            }
            return {
                s1,
                s2: s1 + ventaCredito + favorAplicado,
                favorAplicado,
                ventaCredito
            };
        }

        function recalcularVentaContado() {
            if (!cliente) return;
            const favorDisp = favorDisponible();
            const total = totalVentaTmp();
            const favorAplicado = Math.min(total, favorDisp);
            const aPagar = total - favorAplicado;
            const efectivo = parseFloat($('venta-efectivo').value || '0');
            const sinpe = parseFloat($('venta-sinpe').value || '0');
            const pagado = efectivo + sinpe;

            $('campo-foto-venta').style.display = sinpe > 0 ? 'block' : 'none';

            let texto = `A pagar: ${colones(aPagar)}`;
            if (favorAplicado > 0) texto += ` (se aplicó ${colones(favorAplicado)} de saldo a favor)`;
            if (pagado > aPagar) texto += ` · Vuelto: ${colones(pagado - aPagar)}`;
            else if (pagado < aPagar) texto += ` · Falta ${colones(aPagar - pagado)}`;
            $('venta-hint-pago').textContent = texto;
        }

        /**
         * 17.2 — Confirma una "tanda" de la pestaña Venta. Si es contado y ya
         * había una venta de contado confirmada antes en esta visita, se
         * ACUMULA (suma líneas, monto, efectivo/sinpe) en vez de reemplazarla.
         * Si es a crédito, queda como la única venta de la visita y bloquea la
         * pestaña. No se puede mezclar contado con crédito en la misma visita
         * porque la factura final tiene un solo método de pago.
         */
        function confirmarVenta() {
            if (!lineasVentaTmp.length) {
                Swal.fire('Sin productos', 'Agregá al menos un producto.', 'error');
                return;
            }
            const tipo = document.querySelector('input[name="venta-tipo"]:checked').value;
            const totalNuevo = totalVentaTmp();

            if (venta && venta.tipo !== tipo) {
                Swal.fire('No se puede mezclar',
                    `Ya hay una venta de ${venta.tipo === 'credito' ? 'crédito' : 'contado'} en esta visita. No se puede combinar con ${tipo === 'credito' ? 'crédito' : 'contado'}.`,
                    'error');
                return;
            }
            if (ventaBloqueada) {
                Swal.fire('Venta bloqueada', 'Ya se confirmó una venta a crédito en esta visita.', 'error');
                return;
            }

            const nuevasLineas = lineasVentaTmp.map(l => ({
                producto_id: l.producto_id,
                producto_variante_id: l.producto_variante_id,
                cantidad: l.cantidad,
                precio_unit: l.precio_unit,
                descuento_tipo: l.descuento_tipo,
                descuento: l.descuento,
            }));
            const nuevasFilas = lineasVentaTmp.map(l => ({
                nombre: l.nombre,
                cantidad: l.cantidad,
                precio_unit: l.precio_unit,
                descuento_tipo: l.descuento_tipo,
                descuento: l.descuento,
                monto: totalLinea(l),
            }));

            if (tipo === 'credito') {
                const plazo = parseInt($('venta-plazo').value, 10);
                const max = cliente.maximocredito;
                if (max > 0 && (cliente.saldo_actual + totalNuevo) > max) {
                    Swal.fire('Límite de crédito', `El cliente alcanzó su máximo de crédito (${colones(max)}).`, 'error');
                    return;
                }
                venta = {
                    tipo,
                    direccion: $('venta-direccion').value.trim(),
                    plazo,
                    lineas: nuevasLineas,
                    _filas: nuevasFilas,
                    _total: totalNuevo,
                };
                ventaBloqueada = true;
                $('tab-venta').disabled = true;
            } else {
                const efectivoAhora = parseFloat($('venta-efectivo').value || '0');
                const sinpeAhora = parseFloat($('venta-sinpe').value || '0');
                const favorDisp = favorDisponible();
                const favorAplicadoAhora = Math.min(totalNuevo, favorDisp);
                const aPagarAhora = totalNuevo - favorAplicadoAhora;

                if (efectivoAhora + sinpeAhora < aPagarAhora) {
                    Swal.fire('El pago no alcanza', `Faltan ${colones(aPagarAhora - efectivoAhora - sinpeAhora)}.`,
                        'error');
                    return;
                }
                if (sinpeAhora > 0 && !fotoVenta) {
                    Swal.fire('Falta la foto', 'Subí la foto del comprobante de sinpe.', 'error');
                    return;
                }

                if (venta) {
                    // ya había ventas de contado confirmadas: se acumula
                    venta.lineas = venta.lineas.concat(nuevasLineas);
                    venta._filas = venta._filas.concat(nuevasFilas);
                    venta._total += totalNuevo;
                    venta.efectivo += efectivoAhora;
                    venta.sinpe += sinpeAhora;
                    venta._favorAplicado = (venta._favorAplicado || 0) + favorAplicadoAhora;
                    const dir = $('venta-direccion').value.trim();
                    if (dir) venta.direccion = dir;
                } else {
                    venta = {
                        tipo,
                        direccion: $('venta-direccion').value.trim(),
                        lineas: nuevasLineas,
                        _filas: nuevasFilas,
                        efectivo: efectivoAhora,
                        sinpe: sinpeAhora,
                        _total: totalNuevo,
                        _favorAplicado: favorAplicadoAhora,
                    };
                }
            }

            lineasVentaTmp = [];
            $('buscar-producto').value = '';
            $('producto-elegido').style.display = 'none';
            pintarLineasVenta();

            // limpiar campos de pago para la próxima confirmación (si sigue en contado)
            $('venta-efectivo').value = 0;
            $('venta-sinpe').value = 0;
            recalcularVentaContado();

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Venta agregada',
                showConfirmButton: false,
                timer: 1800
            });
            irATab('principal');
            pintarPrincipal();
        }

        // ---------------- Abono ----------------

        /**
         * Solo "Automático" (reparte primero a la deuda vieja, sección 11) o una
         * factura de crédito específica. Bloque 5 #2: una factura que ya tiene una
         * devolución en esta visita aparece deshabilitada; para abonar en ese caso
         * se usa el abono automático.
         *
         * @param {boolean} resetear Si true, deja los montos en 0 (carga inicial de
         *   un cliente). Si false, solo refresca las opciones y conserva lo tipeado.
         */
        function pintarCuentasAbono(resetear = true) {
            const conDevolucion = devoluciones.map(d => d.factura_id);
            const previo = document.querySelector('input[name="abono-destino"]:checked')?.value;

            const opciones = [];
            opciones.push(
                `<label class="radio-item"><input type="radio" name="abono-destino" value="auto" onchange="recalcularAbono()"> Automático (deuda más vieja primero)</label>`
            );

            // Item 13 (reunión) — la venta a crédito recién confirmada en ESTA visita
            // todavía no tiene id real (no se guardó), así que se ofrece como opción
            // aparte con el valor especial "venta_actual". Solo aplica en Guana,
            // igual que el resto del selector por factura específica (item 3);
            // en Azur el abono siempre es automático y ya la contempla solo.
            // Ítem 3 (reunión) — el selector por factura específica solo existe en
            // Guana. En Azur el abono siempre es automático, así que ni las facturas
            // de crédito ya guardadas ni la venta recién confirmada se ofrecen como
            // destino puntual.
            if (!MUEVE_STOCK) {
                if (venta && venta.tipo === 'credito') {
                    opciones.push(`<label class="radio-item">
                <input type="radio" name="abono-destino" value="venta_actual" onchange="recalcularAbono()">
                <span>Venta de esta visita (recién confirmada)</span>
                <span class="der">${colones(venta._total)}</span>
            </label>`);
                }

                cuentas.facturas_credito.forEach(f => {
                    const bloqueada = conDevolucion.includes(f.id);
                    opciones.push(`<label class="radio-item ${bloqueada ? 'bloqueada' : ''}">
                <input type="radio" name="abono-destino" value="${f.id}" ${bloqueada ? 'disabled' : ''} onchange="recalcularAbono()">
                <span>Factura #${f.numero ?? f.id} (${f.fecha})${bloqueada ? '<br><span class="nota-bloqueo">Ya tiene una devolución en esta visita. Usá el abono automático.</span>' : ''}</span>
                <span class="der">${colones(f.pendiente)}</span>
            </label>`);
                });
            }
            $('lista-cuentas-abono').innerHTML = opciones.join('');

            const previa = previo ? document.querySelector(
                `input[name="abono-destino"][value="${previo}"]:not(:disabled)`) : null;
            (previa || document.querySelector('input[name="abono-destino"][value="auto"]')).checked = true;

            if (resetear) {
                $('abono-efectivo').value = 0;
                $('abono-sinpe').value = 0;
            }
            recalcularAbono();
        }

        /** Pendiente de una factura de crédito, descontando devoluciones ya cargadas en esta visita. */
        function pendienteFacturaAbono(facturaId) {
            const f = cuentas.facturas_credito.find(x => x.id === facturaId);
            if (!f) return 0;
            const devuelto = devoluciones
                .filter(d => d.factura_id === facturaId)
                .reduce((a, d) => a + (d._estimado || 0), 0);
            return Math.max(0, f.pendiente - devuelto);
        }

        /** Tope del abono según el destino elegido: la factura específica o el saldo total. */
        function maximoAbonable() {
            const sel = document.querySelector('input[name="abono-destino"]:checked');
            const totalCliente = Math.max(0, calcularS2().s2);
            if (!sel || sel.value === 'auto') return totalCliente;
            if (sel.value === 'venta_actual') return Math.min(totalCliente, venta ? venta._total : 0);
            return Math.min(totalCliente, pendienteFacturaAbono(parseInt(sel.value, 10)));
        }

        function recalcularAbono() {
            const efectivo = parseFloat($('abono-efectivo').value || '0');
            const sinpe = parseFloat($('abono-sinpe').value || '0');
            $('campo-foto-abono').style.display = sinpe > 0 ? 'block' : 'none';

            const max = maximoAbonable();
            const sel = document.querySelector('input[name="abono-destino"]:checked');
            const esFactura = sel && sel.value !== 'auto';
            const monto = efectivo + sinpe;
            const hint = $('abono-hint');

            if (monto > max + 0.005) {
                hint.style.color = '#a30000';
                hint.textContent =
                    `Te pasás por ${colones(monto - max)}. Máximo ${esFactura ? 'a esta factura' : 'a abonar'}: ${colones(max)}`;
            } else {
                hint.style.color = '';
                hint.textContent = max > 0 ?
                    `Máximo ${esFactura ? 'a abonar a esta factura' : 'a abonar'}: ${colones(max)}` :
                    'No hay deuda pendiente para abonar.';
            }
        }

        function confirmarAbono() {
            const efectivo = parseFloat($('abono-efectivo').value || '0');
            const sinpe = parseFloat($('abono-sinpe').value || '0');
            const monto = efectivo + sinpe;
            if (monto <= 0) {
                Swal.fire('Monto requerido', 'Escribí el monto del abono.', 'error');
                return;
            }

            const max = maximoAbonable();
            const selDestino = document.querySelector('input[name="abono-destino"]:checked');
            const esFactura = selDestino && selDestino.value !== 'auto';
            if (monto > max + 0.005) {
                if (esFactura) {
                    Swal.fire('Supera lo que debe la factura', `Esa factura solo tiene pendiente ${colones(max)}.`,
                        'error');
                } else {
                    Swal.fire('Supera la deuda', `No se puede abonar más de ${colones(max)}.`, 'error');
                }
                return;
            }

            if (sinpe > 0 && !fotoAbono) {
                Swal.fire('Falta la foto', 'Subí la foto del comprobante de sinpe.', 'error');
                return;
            }

            const destino = selDestino.value;

            // Bloque 5 #2 — no se abona directo a una factura que ya tiene devolución en esta visita
            if (destino !== 'auto' && devoluciones.some(d => d.factura_id === parseInt(destino, 10))) {
                Swal.fire('Factura con devolución',
                    'Esa factura ya tiene una devolución en esta visita. Para abonar usá el abono automático.', 'error');
                return;
            }

            abono = {
                efectivo,
                sinpe,
                factura_id: destino === 'auto' ? null : (destino === 'venta_actual' ? 'venta_actual' : parseInt(destino,
                    10)),
                _automatico: destino === 'auto',
                _monto: monto,
            };

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Abono agregado',
                showConfirmButton: false,
                timer: 1800
            });
            irATab('principal');
            pintarPrincipal();
        }

        // ---------------- Devolución ----------------

        /**
         * Bloque 5 #2 — las facturas que ya tienen una devolución en esta visita, o
         * que ya son el destino del abono, aparecen deshabilitadas. Conserva la
         * factura elegida si sigue siendo válida (para no perder lo que el admin
         * está marcando cuando se repinta Principal).
         */
        function pintarFacturasDevolucion() {
            const yaDevueltas = devoluciones.map(d => d.factura_id);
            const idAbono = abono && abono.factura_id ? abono.factura_id : null;
            const previo = facturaDevActual ? facturaDevActual.id : null;

            const opciones = cuentas.facturas_devolucion.map(f => {
                let bloqueo = '';
                if (yaDevueltas.includes(f.id)) bloqueo = 'ya tiene una devolución en esta visita';
                else if (f.id === idAbono) bloqueo = 'ya tiene un abono en esta visita';
                const etiqueta = f.nueva ? 'Factura #' + (f.numero ?? f.id) : 'Factura vieja #' + f
                    .factura_id_legacy;
                return `<option value="${f.id}" ${bloqueo ? 'disabled' : ''}>${etiqueta} — ${f.fecha}${bloqueo ? ' (' + bloqueo + ')' : ''}</option>`;
            }).join('');
            $('dev-factura').innerHTML = `<option value="">Elegí una factura</option>${opciones}`;

            const sigueValida = previo !== null &&
                cuentas.facturas_devolucion.some(f => f.id === previo) &&
                !yaDevueltas.includes(previo) &&
                previo !== idAbono;

            if (sigueValida) {
                $('dev-factura').value = String(previo);
            } else {
                $('dev-factura').value = '';
                $('tarjeta-lineas-dev').style.display = 'none';
                facturaDevActual = null;
                cantidadesDevolucion = {};
            }
        }

        function elegirFacturaDevolucion() {
            const id = parseInt($('dev-factura').value, 10);
            facturaDevActual = cuentas.facturas_devolucion.find(f => f.id === id) || null;
            cantidadesDevolucion = {};
            if (!facturaDevActual) {
                $('tarjeta-lineas-dev').style.display = 'none';
                return;
            }

            // 17.4b — cada línea se activa con un checkbox; al marcarla se
            // prellena al máximo disponible (el caso típico es devolver todo lo
            // que quedó), y un stepper +/- permite ajustar para devoluciones
            // parciales sin tener que escribir un número a mano.
            const puedeRegresarStock = MUEVE_STOCK && facturaDevActual.nueva;
            $('lista-lineas-dev').innerHTML = facturaDevActual.lineas.map((l, i) => `
            <div class="fila-linea fila-dev" style="flex-wrap:wrap;">
                <label class="dev-info" onclick="event.stopPropagation()">
                    <input type="checkbox" id="dev-check-${i}" onchange="toggleLineaDevolucion(${i})">
                    <div>
                        <div class="desc">${l.descripcion}</div>
                        <div class="sub">Disponible: ${l.disponible} · ${colones(l.precio_unit)} c/u</div>
                    </div>
                </label>
                <div class="stepper-dev" id="stepper-dev-${i}" style="display:none;">
                    <button type="button" class="btn-stepper" onclick="ajustarCantidadDevolucion(${i}, -1)">−</button>
                    <span class="dev-cant-visible" id="dev-cant-visible-${i}">0</span>
                    <button type="button" class="btn-stepper" onclick="ajustarCantidadDevolucion(${i}, 1)">+</button>
                </div>
                ${puedeRegresarStock ? `<label style="font-size:12px; display:flex; align-items:center; gap:4px;"><input type="checkbox" id="dev-stock-${i}" style="width:auto;"> Regresar a stock</label>` : ''}
            </div>`).join('');
            $('tarjeta-lineas-dev').style.display = 'block';
        }

        function toggleLineaDevolucion(i) {
            const marcado = $(`dev-check-${i}`).checked;
            const disponible = facturaDevActual.lineas[i].disponible;
            cantidadesDevolucion[i] = marcado ? disponible : 0;
            $(`stepper-dev-${i}`).style.display = marcado ? 'flex' : 'none';
            $(`dev-cant-visible-${i}`).textContent = cantidadesDevolucion[i];
        }

        function ajustarCantidadDevolucion(i, delta) {
            const disponible = facturaDevActual.lineas[i].disponible;
            let nueva = (cantidadesDevolucion[i] || 0) + delta;
            nueva = Math.max(0, Math.min(disponible, nueva));
            cantidadesDevolucion[i] = nueva;
            $(`dev-cant-visible-${i}`).textContent = nueva;

            // Si baja a 0 se desmarca solo; si sube desde 0 (con el "+") se marca solo.
            const marcarComo = nueva > 0;
            $(`dev-check-${i}`).checked = marcarComo;
            $(`stepper-dev-${i}`).style.display = marcarComo ? 'flex' : 'none';
        }

        function confirmarDevolucion() {
            if (!facturaDevActual) {
                Swal.fire('Elegí una factura', 'Elegí la factura de la que se va a devolver.', 'error');
                return;
            }

            // Bloque 5 #2 — una factura no puede tener dos devoluciones ni una devolución
            // si ya es el destino del abono de esta visita
            if (devoluciones.some(d => d.factura_id === facturaDevActual.id)) {
                Swal.fire('Factura ya usada',
                    'Esa factura ya tiene una devolución en esta visita. Quitá la anterior si querés cambiarla.',
                    'error');
                return;
            }
            if (abono && abono.factura_id === facturaDevActual.id) {
                Swal.fire('Factura ya usada',
                    'Esa factura ya tiene un abono en esta visita. Quitá el abono si querés hacerle una devolución.',
                    'error');
                return;
            }

            const lineas = [];
            let estimado = 0;
            facturaDevActual.lineas.forEach((l, i) => {
                const cant = cantidadesDevolucion[i] || 0;
                if (cant > 0) {
                    const regresaStock = MUEVE_STOCK && facturaDevActual.nueva && $(`dev-stock-${i}`) ? $(
                        `dev-stock-${i}`).checked : false;
                    lineas.push({
                        factura_linea_id: l.factura_linea_id,
                        cantidad: cant,
                        regresa_stock: regresaStock
                    });
                    estimado += cant * l.precio_unit;
                }
            });

            if (!lineas.length) {
                Swal.fire('Elegí productos', 'Marcá al menos un producto para devolver.', 'error');
                return;
            }

            devoluciones.push({
                factura_id: facturaDevActual.id,
                motivo: $('dev-motivo').value,
                lineas,
                _label: facturaDevActual.nueva ? `Factura #${facturaDevActual.numero ?? facturaDevActual.id}` :
                    `Factura vieja #${facturaDevActual.factura_id_legacy}`,
                _estimado: estimado,
            });

            $('dev-factura').value = '';
            $('tarjeta-lineas-dev').style.display = 'none';
            facturaDevActual = null;
            cantidadesDevolucion = {};
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Devolución agregada',
                showConfirmButton: false,
                timer: 1800
            });
            irATab('principal');
            pintarPrincipal();
        }

        function quitarDevolucion(i) {
            devoluciones.splice(i, 1);
            pintarPrincipal();
        }

        function quitarVenta() {
            venta = null;
            ventaBloqueada = false;
            $('tab-venta').disabled = false;
            if (abono && abono.factura_id === 'venta_actual') {
                abono = null;
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: 'Se quitó también el abono que apuntaba a esa venta',
                    showConfirmButton: false,
                    timer: 2500
                });
            }
            pintarPrincipal();
        }

        function quitarAbono() {
            abono = null;
            pintarPrincipal();
        }

        // ---------------- Principal / resumen ----------------

        function pintarPrincipal() {
            const bloques = [];
            if (venta) {
                // 17.2 — cada producto de la venta se pinta como su propia fila,
                // igual que ya se hacía con las devoluciones.
                const filasHtml = venta._filas.map(f => `
                <div class="fila-linea">
                    <div>
                        <div class="desc">${f.nombre}</div>
                        <div class="sub">${f.cantidad} × ${colones(f.precio_unit)}${f.descuento > 0 ? ' · desc. ' + (f.descuento_tipo === 'porcentaje' ? f.descuento + '%' : colones(f.descuento)) : ''}</div>
                    </div>
                    <span class="monto">${colones(f.monto)}</span>
                </div>`).join('');
                bloques.push(`<div class="tarjeta">
                <div class="tarjeta-venta-header">
                    <h3>Venta (${venta.tipo === 'credito' ? 'Crédito ' + venta.plazo + ' días' : 'Contado'})</h3>
                    <button type="button" class="btn btn-texto" onclick="quitarVenta()">Quitar</button>
                </div>
                ${filasHtml}
                <p style="text-align:right; margin:10px 0 0; font-weight:700;">Total: ${colones(venta._total)}</p>
            </div>`);
            }
            if (abono) {
                bloques.push(`<div class="tarjeta">
                <div class="fila-linea">
                                        <div><div class="desc">Abono</div><div class="sub">${abono._automatico ? 'Automático' : (abono.factura_id === 'venta_actual' ? 'Venta de esta visita' : ('Factura #' + abono.factura_id))}</div></div>
                    <div style="display:flex; align-items:center; gap:10px;"><span class="monto">${colones(abono._monto)}</span>
                    <button type="button" class="btn btn-texto" onclick="quitarAbono()">Quitar</button></div>
                </div></div>`);
            }
            devoluciones.forEach((d, i) => {
                bloques.push(`<div class="tarjeta">
                <div class="fila-linea">
                    <div><div class="desc">Devolución</div><div class="sub">${d._label} · ${MOTIVOS_LABEL[d.motivo]}</div></div>
                    <div style="display:flex; align-items:center; gap:10px;"><span class="monto">${colones(d._estimado)}</span>
                    <button type="button" class="btn btn-texto" onclick="quitarDevolucion(${i})">Quitar</button></div>
                </div></div>`);
            });
            $('lista-principal').innerHTML = bloques.join('') ||
                '<div class="estado-vacio">Todavía no agregaste nada en esta visita.</div>';

            actualizarIndicadores();
            // Bloque 5 #2 — refresca qué facturas quedan disponibles en Abono y Devolución
            if (cuentas) {
                pintarCuentasAbono(false);
                pintarFacturasDevolucion();
            }

            actualizarResumen();
            guardarBorrador();
        }

        function actualizarResumen() {
            if (!cliente) return;
            const s0 = cliente.saldo_actual;
            const devolucionTotal = devoluciones.reduce((a, d) => a + d._estimado, 0);
            const {
                s1,
                s2,
                favorAplicado,
                ventaCredito
            } = calcularS2();
            const montoAbono = abono ? abono._monto : 0;
            const s3 = s2 - montoAbono;

            $('r-inicial').textContent = colones(s0);
            $('r-devolucion').textContent = '− ' + colones(devolucionTotal);
            $('r-credito').textContent = '+ ' + colones(ventaCredito);
            $('r-favor').textContent = colones(favorAplicado);
            $('r-abono').textContent = '− ' + colones(montoAbono);
            $('r-final').textContent = colones(s3);
        }

        // ---------------- Guardar / reiniciar ----------------

        /**
         * @param {boolean} preguntar Si true, confirma con el usuario antes de
         *   borrar cuando hay algo cargado (botón "Cancelar visita"). Si false, no
         *   pregunta (se usa internamente al cambiar o quitar cliente).
         */
        async function reiniciarTodo(preguntar = true) {
            if (preguntar && (venta || abono || devoluciones.length || noAbono)) {
                const r = await Swal.fire({
                    title: '¿Cancelar la visita?',
                    text: 'Se va a perder todo lo agregado hasta ahora.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cancelar',
                    cancelButtonText: 'Volver',
                });
                if (!r.isConfirmed) return;
            }

            venta = null;
            ventaBloqueada = false;
            abono = null;
            devoluciones = [];
            noAbono = false;
            noAbonoDescripcion = '';
            lineasVentaTmp = [];
            fotoVenta = null;
            fotoAbono = null;
            productoElegido = null;
            mostrarNotaApartado('');
            facturaDevActual = null;
            cantidadesDevolucion = {};

            $('chk-no-abono').checked = false;
            $('campo-no-abono-descripcion').style.display = 'none';
            $('no-abono-descripcion').value = '';
            $('tab-venta').disabled = false;
            document.querySelectorAll('.tab-btn[data-tab]:not([data-tab="principal"])').forEach(b => b.disabled =
                false);
            $('btn-guardar').textContent = 'Guardar';

            // Reset de campos de Venta y Abono para que no quede nada tipeado de una
            // visita anterior (bug: cambiar de cliente a mitad de una visita dejaba
            // efectivo/sinpe con el valor viejo).
            $('venta-efectivo').value = 0;
            $('venta-sinpe').value = 0;
            $('venta-plazo').value = '15';
            document.querySelector('input[name="venta-tipo"][value="contado"]').checked = true;
            $('venta-foto').value = '';
            $('campo-foto-venta').style.display = 'none';
            $('venta-hint-pago').textContent = '';
            cambiarTipoVenta();

            $('abono-efectivo').value = 0;
            $('abono-sinpe').value = 0;
            $('abono-foto').value = '';
            $('campo-foto-abono').style.display = 'none';

            pintarLineasVenta();
            if (cliente) {
                pintarPrincipal();
            } else {
                actualizarIndicadores();
                guardarBorrador();
            }
        }

        async function guardarOperacion() {
            if (!noAbono && !venta && !abono && !devoluciones.length) {
                Swal.fire('Nada para guardar', 'Agregá algo o marcá que el cliente no abonó.', 'error');
                return;
            }

            const confirmar = await Swal.fire({
                title: '¿Guardar esta visita?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar',
            });
            if (!confirmar.isConfirmed) return;

            const payload = {
                cliente_id: cliente.id,
                ruta_cliente_id: rutaClienteVisita,
                no_abono: noAbono,
            };
            if (noAbono && noAbonoDescripcion.trim() !== '') {
                payload.no_abono_descripcion = noAbonoDescripcion.trim();
            }
            if (venta) {
                payload.venta = {
                    tipo: venta.tipo,
                    plazo: venta.plazo,
                    efectivo: venta.efectivo,
                    sinpe: venta.sinpe,
                    direccion: venta.direccion,
                    lineas: venta.lineas,
                };
            }
            if (abono) {
                payload.abono = {
                    efectivo: abono.efectivo,
                    sinpe: abono.sinpe,
                    factura_id: abono.factura_id
                };
            }
            if (devoluciones.length) {
                payload.devoluciones = devoluciones.map(d => ({
                    factura_id: d.factura_id,
                    motivo: d.motivo,
                    lineas: d.lineas
                }));
            }

            const formData = new FormData();
            formData.append('payload', JSON.stringify(payload));
            if (fotoVenta) formData.append('foto_venta', fotoVenta);
            if (fotoAbono) formData.append('foto_abono', fotoAbono);

            $('btn-guardar').disabled = true;
            try {
                const r = await fetch(`{{ route('facturacion.guardar') }}`, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: formData,
                });
                const datos = await r.json();
                if (!r.ok) {
                    Swal.fire('No se pudo guardar', datos.mensaje ?? 'Revisá los datos.', 'error');
                    return;
                }
                borrarBorrador();
                // Recibo por WhatsApp en una petición aparte, para que guardar no espere a Meta.
                // keepalive hace que el navegador la termine aunque se salgan de la pantalla.
                fetch(URL_RECIBO.replace('__ID__', datos.operacion.id), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    keepalive: true,
                }).catch(() => {});

                // Bloque 5 #1 — dice si la visita quedó marcada en una ruta, o por qué no
                let extraRuta = '';
                if (datos.ruta_vinculada) {
                    extraRuta = '<br><small>La visita quedó registrada en la ruta del cliente.</small>';
                } else if (datos.rutas_ambiguas) {
                    extraRuta =
                        '<br><small style="color:#b95000;">El cliente está en más de una ruta pendiente, así que no se marcó en ninguna. Marcalo a mano desde Rutas.</small>';
                }

                const r2 = await Swal.fire({
                    title: 'Guardado',
                    html: `Operación #${datos.operacion.numero} guardada correctamente.${extraRuta}`,
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'Ver comprobante',
                    cancelButtonText: 'Cerrar',
                });
                if (r2.isConfirmed) {
                    window.open(URL_COMPROBANTE.replace('__ID__', datos.operacion.id), '_blank');
                }
                quitarCliente();
            } catch (e) {
                Swal.fire('Error de conexión', 'Intentá de nuevo.', 'error');
            } finally {
                $('btn-guardar').disabled = false;
            }
        }

        // ---------------- Carga inicial ------------------

       async function cargarDesdeApartado(apartadoId) {
    let apartado;
    try {
        const rApartado = await fetch(URL_APARTADO.replace('__ID__', apartadoId), {
            headers: { Accept: 'application/json' }
        });
        if (!rApartado.ok) throw new Error('no encontrado');
        apartado = await rApartado.json();
    } catch (e) {
        Swal.fire('No se pudo cargar el apartado', 'Puede que ya se haya usado o eliminado.', 'error');
        return;
    }

    const ok = await seleccionarCliente(apartado.cliente_id);
    if (!ok) return;

    irATab('venta');
    mostrarNotaApartado(apartado.nota);

    // Carga los datos completos de cada producto (necesitamos precio/stock actual,
    // no lo que había cuando se apartó) y arma la cola.
    colaApartadoProductos = [];
    for (const l of apartado.lineas) {
        const rProd = await fetch(`{{ route('facturacion.producto', ['producto' => '__ID__']) }}`.replace(
            '__ID__', l.producto_id), {
            headers: { Accept: 'application/json' }
        });
        if (rProd.ok) colaApartadoProductos.push(await rProd.json());
    }

    await fetch(`/apartados/${apartadoId}`, {
        method: 'DELETE',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': token
        }
    });

    avanzarColaApartado();
}

function mostrarNotaApartado(texto) {
    const t = (texto || '').trim();
    $('nota-apartado-texto').textContent = t;
    $('nota-apartado').style.display = t ? 'block' : 'none';
}

        /** Carga el siguiente producto pendiente de la cola en el buscador de Venta,
         *  como si el admin lo hubiera elegido a mano. La cantidad la define el admin. */
        function avanzarColaApartado() {
            if (!colaApartadoProductos.length) return;
            const p = colaApartadoProductos.shift();
            elegirProducto(p);
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: 'Producto del apartado cargado, ajustá la cantidad',
                showConfirmButton: false,
                timer: 2500
            });
        }
        // ---------------- Carga inicial ------------------

        cambiarTipoVenta();

        if (PARAM_APARTADO_ID) {
            cargarDesdeApartado(parseInt(PARAM_APARTADO_ID, 10)).then(limpiarUrl);
        } else if (PARAM_CLIENTE_ID) {
            seleccionarCliente(parseInt(PARAM_CLIENTE_ID, 10)).then(ok => {
                if (ok && PARAM_RUTA_CLIENTE_ID) {
                    rutaClienteVisita = parseInt(PARAM_RUTA_CLIENTE_ID, 10);
                    guardarBorrador();
                }
                limpiarUrl(); // ← nueva
            });
        } else {
            intentarRestaurarBorrador();
        }
    </script>
@endpush
