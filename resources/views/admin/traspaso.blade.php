@extends('layouts.app')

@section('titulo', 'Traspaso de cuenta')

@push('estilos')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    .btn {
        border: none; border-radius: 8px; padding: 10px 16px; font-size: 14px;
        font-weight: 600; cursor: pointer; white-space: nowrap; font-family: inherit;
    }
    .btn-primario { background: var(--azul-medio); color: #fff; }
    .btn-secundario { background: #fff; border: 1px solid var(--borde); color: var(--texto); }
    .btn-texto { background: none; color: var(--azul-medio); padding: 4px 8px; }
    .btn:disabled { opacity: .45; cursor: not-allowed; }

    .tarjeta {
        background: #fff; border-radius: 12px; box-shadow: var(--sombra-card);
        padding: 20px; margin-bottom: 18px; max-width: 640px;
    }
    .tarjeta h2 { margin: 0 0 4px; font-size: 16px; }
    .tarjeta .sub { font-size: 13px; color: var(--texto-tenue); margin-bottom: 16px; }

    .buscador-caja { position: relative; }
    .sugerencias {
        display: none; margin-top: 4px;
        background: #fff; border: 1px solid var(--borde); border-radius: 8px;
        max-height: 220px; overflow-y: auto; position: absolute; width: 100%; z-index: 20;
    }
    .sugerencias.abierto { display: block; }
    .sugerencia-item { padding: 10px 12px; font-size: 14px; cursor: pointer; }
    .sugerencia-item:hover { background: var(--azul-50); }
    .sugerencia-item .sug-nombre { font-weight: 600; margin-bottom: 2px; }
    .sugerencia-item .sec { font-size: 12px; color: var(--texto-tenue); }

    .campo { margin-bottom: 13px; display: flex; flex-direction: column; gap: 5px; }
    .campo label { font-size: 12.5px; font-weight: 600; color: var(--texto-tenue); }
    .campo input, .campo select {
        padding: 10px 12px; border: 1px solid var(--borde); border-radius: 8px;
        font-size: max(13.5px, 16px); font-family: inherit; background: #fff;
    }
    .campo small { font-size: 12px; color: var(--texto-tenue); }
    .fila-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

    .cliente-elegido {
        background: var(--azul-50); border: 1px solid var(--azul-100); border-radius: 10px;
        padding: 12px 14px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; gap: 10px;
    }
    .cliente-elegido .nombre { font-weight: 700; font-size: 14px; color: var(--azul-oscuro); }
    .cliente-elegido .saldo { font-size: 13px; color: var(--texto-tenue); margin-top: 2px; }

    .radio-opcion { display: flex; align-items: center; gap: 8px; padding: 8px 0; font-size: 14px; cursor: pointer; }

    .lista-facturas { border: 1px solid var(--borde); border-radius: 8px; max-height: 240px; overflow-y: auto; }
    .fila-factura {
        display: flex; align-items: center; gap: 10px; padding: 10px 12px;
        border-bottom: 1px solid var(--borde); cursor: pointer; font-size: 14px;
    }
    .fila-factura:last-child { border-bottom: none; }
    .fila-factura input[type="checkbox"] { width: 18px; height: 18px; flex-shrink: 0; }
    .fila-factura .info { flex: 1; }
    .fila-factura .sub { font-size: 12px; color: var(--texto-tenue); }

    .resumen-monto {
        margin-top: 14px; padding: 12px 14px; background: var(--azul-50); border-radius: 8px;
        font-size: 15px; font-weight: 700; color: var(--azul-oscuro); text-align: right;
    }

    .accion-crear-cliente { margin-top: 10px; }

    .alerta-error {
        background: #fdecec; color: #a30000; border: 1px solid #f3a3a3; border-radius: 8px;
        padding: 10px 12px; font-size: 13px; margin-top: 12px;
    }

    .resumen-final { font-size: 14px; line-height: 1.7; }
    .resumen-final strong { color: var(--azul-oscuro); }

    .bloque-ubicacion { border: 1px solid var(--borde); border-radius: 10px; padding: 12px; margin-bottom: 13px; background: #FAFAF8; }
    .bloque-ubicacion > label { font-size: 12.5px; font-weight: 600; color: var(--texto-tenue); display: block; margin-bottom: 8px; }
    .ubicacion-botones { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 8px; }
    .ubicacion-botones button { padding: 8px 12px; font-size: 13px; }
    .ubicacion-link { display: flex; gap: 8px; margin-bottom: 8px; }
    .ubicacion-link input { flex: 1; min-width: 0; padding: 9px 10px; border: 1px solid var(--borde); border-radius: 8px; font-size: 16px; font-family: inherit; }
    #mapa-cliente-nuevo { height: 220px; border-radius: 8px; border: 1px solid var(--borde); z-index: 0; }
    #ubicacion-estado-nuevo { font-size: 12px; color: var(--texto-tenue); margin-top: 6px; }

    @media (max-width: 720px) {
        .fila-2 { grid-template-columns: 1fr; }
        .tarjeta { max-width: 100%; }
    }
</style>
@endpush

@section('contenido')
@php
    $sucursalTraspaso = \App\Models\Sucursal::find(session('sucursal_id'));
    $canalTraspaso = $sucursalTraspaso->canal ?? null;
    $canalTraspaso = $canalTraspaso instanceof \BackedEnum ? $canalTraspaso->value : $canalTraspaso;
    $esGuanaTraspaso = $canalTraspaso === 'mayorista';
@endphp

    <h1 style="margin:0 0 18px; font-size:20px;">Traspaso de cuenta</h1>

    {{-- ===== PASO 1: cliente origen ===== --}}
    <div class="tarjeta" id="tarjeta-origen">
        <h2>1. Cliente que traspasa la deuda</h2>
        <div class="sub">Buscá al cliente que actualmente debe la plata.</div>

        <div id="paso-buscar-origen">
            <div class="campo buscador-caja">
                <label>Cliente origen</label>
                <input type="text" id="buscar-origen" placeholder="Buscar por nombre o código" autocomplete="off">
                <div class="sugerencias" id="sug-origen"></div>
            </div>
        </div>

        <div id="origen-elegido-caja" style="display:none;">
            <div class="cliente-elegido">
                <div>
                    <div class="nombre" id="origen-nombre"></div>
                    <div class="saldo" id="origen-saldo"></div>
                </div>
                <button type="button" class="btn btn-texto" onclick="quitarOrigen()">Cambiar</button>
            </div>

            <div class="campo" id="caja-modo">
                <label>¿Qué se traspasa?</label>
                <label class="radio-opcion">
                    <input type="radio" name="modo-traspaso" value="total" checked onchange="cambiarModoTraspaso()">
                    Todo el saldo pendiente
                </label>
                <label class="radio-opcion">
                    <input type="radio" name="modo-traspaso" value="facturas" onchange="cambiarModoTraspaso()">
                    Elegir facturas específicas
                </label>
            </div>

            <div id="caja-facturas" style="display:none;">
                <div class="lista-facturas" id="lista-facturas"></div>
            </div>

            <div class="resumen-monto" id="resumen-monto"></div>
        </div>
    </div>

    {{-- ===== PASO 2: cliente destino ===== --}}
    <div class="tarjeta" id="tarjeta-destino" style="display:none;">
        <h2>2. Cliente que recibe la deuda</h2>
        <div class="sub">Buscá al cliente destino, o creá uno nuevo si no existe.</div>

        <div id="paso-buscar-destino">
            <div class="campo buscador-caja">
                <label>Cliente destino</label>
                <input type="text" id="buscar-destino" placeholder="Buscar por nombre o código" autocomplete="off">
                <div class="sugerencias" id="sug-destino"></div>
            </div>
            <div class="accion-crear-cliente">
                <button type="button" class="btn btn-secundario" onclick="abrirFormularioClienteNuevo()">+ Crear cliente nuevo</button>
            </div>
        </div>

        <div id="destino-elegido-caja" style="display:none;">
            <div class="cliente-elegido">
                <div>
                    <div class="nombre" id="destino-nombre"></div>
                    <div class="saldo" id="destino-saldo"></div>
                </div>
                <button type="button" class="btn btn-texto" onclick="quitarDestino()">Cambiar</button>
            </div>
        </div>

        {{-- ===== Formulario de cliente nuevo: idéntico al de la pantalla Clientes ===== --}}
        <div id="form-cliente-nuevo" style="display:none;">
            <div class="campo"><label>Nombre *</label><input type="text" id="nc-nombre" required></div>
            <div class="campo"><label>Teléfono</label><input type="text" id="nc-telefono" inputmode="tel"></div>
            <div class="campo">
                <label>Código</label>
                <input type="text" id="nc-codigo">
                <small>Se llena solo con los últimos 4 dígitos del teléfono. Podés cambiarlo.</small>
            </div>
            <div class="campo"><label>Correo</label><input type="email" id="nc-correo"></div>
            <div class="campo"><label>Dirección</label><input type="text" id="nc-direccion"></div>
            <div class="campo"><label>Máximo de crédito</label><input type="number" step="0.01" id="nc-maximocredito"></div>
            <div class="campo">
                <label>Género</label>
                <select id="nc-genero">
                    <option value="">Sin especificar</option>
                    <option value="F">F</option>
                    <option value="M">M</option>
                </select>
            </div>

            @if ($esGuanaTraspaso)
                <div class="bloque-ubicacion" id="bloque-ubicacion-nuevo">
                    <label>Ubicación del cliente</label>
                    <input type="hidden" id="nc-latitud">
                    <input type="hidden" id="nc-longitud">

                    <div class="ubicacion-botones">
                        <button type="button" class="btn-secundario" onclick="usarMiUbicacionNuevo()">Usar mi ubicación actual</button>
                        <button type="button" class="btn-secundario" onclick="limpiarUbicacionNuevo()">Quitar ubicación</button>
                    </div>
                    <div class="ubicacion-link">
                        <input type="text" id="ubicacion-link-input-nuevo" placeholder="Pegá un link de Google Maps o 'lat, lng'">
                        <button type="button" class="btn-secundario" onclick="leerLinkUbicacionNuevo()">Usar</button>
                    </div>
                    <div id="mapa-cliente-nuevo"></div>
                    <div id="ubicacion-estado-nuevo">Tocá el mapa para poner el pin (se puede arrastrar).</div>
                </div>
            @endif

            <div style="display:flex; gap:10px; margin-top:6px;">
                <button type="button" class="btn btn-secundario" onclick="cerrarFormularioClienteNuevo()">Cancelar</button>
                <button type="button" class="btn btn-primario" onclick="guardarClienteNuevo()">Crear y usar como destino</button>
            </div>
        </div>
    </div>

    {{-- ===== PASO 3: confirmar ===== --}}
    <div class="tarjeta" id="tarjeta-confirmar" style="display:none;">
        <h2>3. Confirmar traspaso</h2>
        <div class="resumen-final" id="resumen-final"></div>
        <div id="error-traspaso"></div>
        <div style="margin-top:16px;">
            <button type="button" class="btn btn-primario" onclick="confirmarTraspaso()">Confirmar traspaso</button>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const ES_GUANA_TRASPASO = @json($esGuanaTraspaso);
    const SUCURSAL_ACTUAL = {{ session('sucursal_id') }};
    const URL_RECIBO = @json(route('operaciones.recibo', ['operacion' => '__ID__']));
    const $ = (id) => document.getElementById(id);
        // Cierra las listas de sugerencias al tocar fuera del buscador
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.buscador-caja')) {
            $('sug-origen').classList.remove('abierto');
            $('sug-destino').classList.remove('abierto');
        }
    });

    let origenElegido = null;
    let destinoElegido = null;
    let modoTraspaso = 'total';
    let facturasOrigen = [];
    let facturaIdsElegidas = [];

    function enviarRecibos(ids) {
    ids.forEach(id => {
        fetch(URL_RECIBO.replace('__ID__', id), {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token },
            keepalive: true,
        }).catch(() => {});
    });
} 
    function formatoColones(monto) {
        return '₡' + Number(monto).toLocaleString('es-CR', { minimumFractionDigits: 2 });
    }

        function textoSaldo(s, prefijoDeuda = 'Debe: ') {
        s = Number(s);
        return s < 0 ? 'Saldo a favor: ' + formatoColones(-s) : prefijoDeuda + formatoColones(s);
    }

    /* ---------- Buscar origen ---------- */
    let tOrigen = null;
    $('buscar-origen').addEventListener('input', (e) => {
        clearTimeout(tOrigen);
        const q = e.target.value.trim();
        if (q.length < 2) { $('sug-origen').classList.remove('abierto'); return; }
        tOrigen = setTimeout(async () => {
            const r = await fetch(`{{ route('traspaso.clientes') }}?buscar=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
            const lista = await r.json();
            $('sug-origen').innerHTML = lista.map(c => `
                <div class="sugerencia-item" onclick='elegirOrigen(${JSON.stringify(c).replace(/'/g, "&#39;")})'>
                    <div class="sug-nombre">${c.nombre}</div>
                    <div class="sec">${c.codigo ?? 'sin código'} · Saldo: ${formatoColones(c.saldo_actual)}</div>
                </div>`).join('') || '<div class="sugerencia-item sec">Sin resultados</div>';
            $('sug-origen').classList.add('abierto');
        }, 300);
    });

           async function elegirOrigen(c) {
        const saldo = Number(c.saldo_actual);
        if (saldo === 0) {
            $('sug-origen').classList.remove('abierto');
            $('sug-origen').innerHTML = '';
            $('buscar-origen').value = '';
            Swal.fire('Sin saldo', 'Ese cliente no tiene deuda ni saldo a favor para traspasar.', 'info')
                .then(() => $('buscar-origen').focus());
            return;
        }
        origenElegido = c;
        $('buscar-origen').value = '';
        $('sug-origen').classList.remove('abierto');
        $('paso-buscar-origen').style.display = 'none';
        $('origen-elegido-caja').style.display = 'block';
        $('origen-nombre').textContent = c.nombre;
        $('origen-saldo').textContent = textoSaldo(saldo);

        modoTraspaso = 'total';
        facturaIdsElegidas = [];
        document.querySelector('input[name="modo-traspaso"][value="total"]').checked = true;
        $('caja-facturas').style.display = 'none';

        // Con saldo a favor no hay facturas que elegir: se traspasa todo el saldo
        $('caja-modo').style.display = saldo < 0 ? 'none' : '';
        if (saldo > 0) { await cargarFacturasOrigen(); } else { facturasOrigen = []; }

        actualizarResumenMonto();
        $('tarjeta-destino').style.display = 'block';
    }

    function quitarOrigen() {
        origenElegido = null;
        $('paso-buscar-origen').style.display = 'block';
        $('origen-elegido-caja').style.display = 'none';
        $('tarjeta-destino').style.display = 'none';
        $('tarjeta-confirmar').style.display = 'none';
        quitarDestino();
    }

    async function cargarFacturasOrigen() {
        const r = await fetch(`{{ url('/traspaso') }}/${origenElegido.id}/facturas`, { headers: { Accept: 'application/json' } });
        facturasOrigen = await r.json();

        $('lista-facturas').innerHTML = facturasOrigen.map(f => `
            <label class="fila-factura">
                <input type="checkbox" value="${f.id}" onchange="toggleFactura(${f.id}, this.checked)">
                <div class="info">
                    <div>Factura #${f.numero ?? '—'}</div>
                    <div class="sub">${f.fecha ?? ''} · Pendiente: ${formatoColones(f.pendiente)}</div>
                </div>
            </label>
        `).join('') || '<div style="padding:12px; font-size:13px; color:var(--texto-tenue);">Este cliente no tiene facturas nuevas de crédito con pendiente.</div>';
    }

    function cambiarModoTraspaso() {
        modoTraspaso = document.querySelector('input[name="modo-traspaso"]:checked').value;
        $('caja-facturas').style.display = modoTraspaso === 'facturas' ? 'block' : 'none';
        if (modoTraspaso === 'total') facturaIdsElegidas = [];
        actualizarResumenMonto();
    }

    function toggleFactura(id, marcado) {
        if (marcado) { if (!facturaIdsElegidas.includes(id)) facturaIdsElegidas.push(id); }
        else { facturaIdsElegidas = facturaIdsElegidas.filter(x => x !== id); }
        actualizarResumenMonto();
    }

      function montoATraspasar() {
        if (modoTraspaso === 'total') return Math.abs(Number(origenElegido.saldo_actual));
        return facturasOrigen
            .filter(f => facturaIdsElegidas.includes(f.id))
            .reduce((s, f) => s + Number(f.pendiente), 0);
    }

    function actualizarResumenMonto() {
        const aFavor = Number(origenElegido.saldo_actual) < 0;
        $('resumen-monto').textContent = (aFavor ? 'Saldo a favor a traspasar: ' : 'Monto a traspasar: ') + formatoColones(montoATraspasar());
        if (destinoElegido) mostrarPasoConfirmar();
    }

    /* ---------- Buscar destino ---------- */
    let tDestino = null;
    $('buscar-destino').addEventListener('input', (e) => {
        clearTimeout(tDestino);
        const q = e.target.value.trim();
        if (q.length < 2) { $('sug-destino').classList.remove('abierto'); return; }
        tDestino = setTimeout(async () => {
            const r = await fetch(`{{ route('traspaso.clientes') }}?buscar=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
            const lista = await r.json();
            $('sug-destino').innerHTML = lista
                .filter(c => c.id !== origenElegido.id)
                .map(c => `
                <div class="sugerencia-item" onclick='elegirDestino(${JSON.stringify(c).replace(/'/g, "&#39;")})'>
                    <div class="sug-nombre">${c.nombre}</div>
                    <div class="sec">${c.codigo ?? 'sin código'} · Saldo: ${formatoColones(c.saldo_actual)}</div>
                </div>`).join('') || '<div class="sugerencia-item sec">Sin resultados</div>';
            $('sug-destino').classList.add('abierto');
        }, 300);
    });

    function elegirDestino(c) {
        destinoElegido = c;
        $('buscar-destino').value = '';
        $('sug-destino').classList.remove('abierto');
        $('paso-buscar-destino').style.display = 'none';
        $('form-cliente-nuevo').style.display = 'none';
        $('destino-elegido-caja').style.display = 'block';
        $('destino-nombre').textContent = c.nombre;
               $('destino-saldo').textContent = textoSaldo(c.saldo_actual, 'Debe actualmente: ');
        mostrarPasoConfirmar();
    }

    function quitarDestino() {
        destinoElegido = null;
        $('paso-buscar-destino').style.display = 'block';
        $('destino-elegido-caja').style.display = 'none';
        $('form-cliente-nuevo').style.display = 'none';
        $('tarjeta-confirmar').style.display = 'none';
    }

    /* ---------- Crear cliente nuevo (idéntico a Clientes) ---------- */
    let codigoManualNuevo = false;
    let mapaNuevo = null, marcadorNuevo = null;

    function ultimos4DigitosNuevo(telefono) {
        const digitos = (telefono || '').replace(/\D/g, '');
        return digitos.length >= 4 ? digitos.slice(-4) : '';
    }

    $('nc-telefono')?.addEventListener('input', function () {
        if (codigoManualNuevo) return;
        $('nc-codigo').value = ultimos4DigitosNuevo(this.value);
    });

    $('nc-codigo')?.addEventListener('input', function () {
        codigoManualNuevo = this.value.trim() !== '';
        if (!codigoManualNuevo) {
            this.value = ultimos4DigitosNuevo($('nc-telefono').value);
        }
    });

    function abrirFormularioClienteNuevo() {
        $('paso-buscar-destino').style.display = 'none';
        $('form-cliente-nuevo').style.display = 'block';

        codigoManualNuevo = false;
        $('nc-nombre').value = '';
        $('nc-telefono').value = '';
        $('nc-codigo').value = '';
        $('nc-correo').value = '';
        $('nc-direccion').value = '';
        $('nc-maximocredito').value = '';
        $('nc-genero').value = '';

        if (ES_GUANA_TRASPASO) {
            $('nc-latitud').value = '';
            $('nc-longitud').value = '';
            $('ubicacion-link-input-nuevo').value = '';
            $('ubicacion-estado-nuevo').textContent = 'Tocá el mapa para poner el pin (se puede arrastrar).';
            if (marcadorNuevo && mapaNuevo) { mapaNuevo.removeLayer(marcadorNuevo); marcadorNuevo = null; }
            iniciarMapaNuevo();
        }

        $('nc-nombre').focus();
    }

    function cerrarFormularioClienteNuevo() {
        $('form-cliente-nuevo').style.display = 'none';
        $('paso-buscar-destino').style.display = 'block';
    }

    function iniciarMapaNuevo() {
        if (!mapaNuevo) {
            mapaNuevo = L.map('mapa-cliente-nuevo').setView([10.6350, -85.4377], 13);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19, attribution: '© OpenStreetMap'
            }).addTo(mapaNuevo);
            mapaNuevo.on('click', e => fijarUbicacionNuevo(e.latlng.lat, e.latlng.lng, false));
        }
        setTimeout(() => { mapaNuevo.invalidateSize(); mapaNuevo.setView([10.6350, -85.4377], 13); }, 150);
    }

    function fijarUbicacionNuevo(lat, lng, centrar = true) {
        lat = Number(lat.toFixed(7)); lng = Number(lng.toFixed(7));
        $('nc-latitud').value = lat;
        $('nc-longitud').value = lng;

        if (!marcadorNuevo) {
            marcadorNuevo = L.marker([lat, lng], { draggable: true }).addTo(mapaNuevo);
            marcadorNuevo.on('dragend', () => {
                const p = marcadorNuevo.getLatLng();
                fijarUbicacionNuevo(p.lat, p.lng, false);
            });
        } else {
            marcadorNuevo.setLatLng([lat, lng]);
        }
        if (centrar) mapaNuevo.setView([lat, lng], 16);
        $('ubicacion-estado-nuevo').textContent = `Ubicación guardada en el formulario: ${lat}, ${lng}`;
    }

    function limpiarUbicacionNuevo() {
        $('nc-latitud').value = '';
        $('nc-longitud').value = '';
        $('ubicacion-link-input-nuevo').value = '';
        if (marcadorNuevo && mapaNuevo) { mapaNuevo.removeLayer(marcadorNuevo); marcadorNuevo = null; }
        $('ubicacion-estado-nuevo').textContent = 'Sin ubicación. Tocá el mapa para poner el pin.';
    }

    function usarMiUbicacionNuevo() {
        if (!navigator.geolocation) {
            Swal.fire('No disponible', 'Este navegador no permite obtener la ubicación.', 'info');
            return;
        }
        navigator.geolocation.getCurrentPosition(
            pos => fijarUbicacionNuevo(pos.coords.latitude, pos.coords.longitude),
            () => Swal.fire('No se pudo obtener', 'Revisá que el navegador tenga permiso de ubicación (y que el sitio use https).', 'warning'),
            { enableHighAccuracy: true, timeout: 15000 }
        );
    }

    function leerLinkUbicacionNuevo() {
        const texto = $('ubicacion-link-input-nuevo').value.trim();
        const patrones = [
            /@(-?\d+\.\d+),(-?\d+\.\d+)/,
            /!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/,
            /[?&](?:q|ll|query)=(-?\d+\.\d+),(-?\d+\.\d+)/,
            /^(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)$/,
        ];
        for (const p of patrones) {
            const m = texto.match(p);
            if (m) {
                const lat = parseFloat(m[1]), lng = parseFloat(m[2]);
                if (Math.abs(lat) <= 90 && Math.abs(lng) <= 180) { fijarUbicacionNuevo(lat, lng); return; }
            }
        }
        Swal.fire('No pude leer el link',
            'Los links cortos (maps.app.goo.gl) no traen coordenadas. Abrilo en el navegador y copiá la URL larga, o pegá "lat, lng".', 'info');
    }

    async function guardarClienteNuevo() {
        const nombre = $('nc-nombre').value.trim();
        if (!nombre) {
            Swal.fire('Falta el nombre', 'Escribí el nombre del cliente nuevo.', 'error');
            return;
        }

        const fd = new FormData();
        fd.append('nombre', nombre);
        fd.append('telefono', $('nc-telefono').value.trim());
        fd.append('codigo', $('nc-codigo').value.trim());
        fd.append('correo', $('nc-correo').value.trim());
        fd.append('direccion', $('nc-direccion').value.trim());
        fd.append('sucursal_id', SUCURSAL_ACTUAL);
        fd.append('maximocredito', $('nc-maximocredito').value || 0);
        fd.append('genero', $('nc-genero').value);
        if (ES_GUANA_TRASPASO && $('nc-latitud').value) {
            fd.append('latitud', $('nc-latitud').value);
            fd.append('longitud', $('nc-longitud').value);
        }

        const resp = await fetch(`{{ route('clientes.store') }}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body: fd,
        });
        const datos = await resp.json();

        if (!resp.ok) {
            const msg = datos.errors ? Object.values(datos.errors).flat().join('\n') : (datos.mensaje ?? 'No se pudo crear el cliente.');
            Swal.fire('Revisá los datos', msg, 'error');
            return;
        }

        // Se creó bien: lo buscamos para obtener su id real y elegirlo como destino
        const r = await fetch(`{{ route('traspaso.clientes') }}?buscar=${encodeURIComponent(nombre)}`, { headers: { Accept: 'application/json' } });
        const lista = await r.json();
        const creado = lista.find(c => c.nombre === nombre) ?? lista[0];
        if (creado) elegirDestino(creado);
    }

    /* ---------- Confirmar ---------- */
     function mostrarPasoConfirmar() {
        const monto = montoATraspasar();
        const aFavor = Number(origenElegido.saldo_actual) < 0;
        const detalle = aFavor
            ? 'todo su saldo a favor'
            : (modoTraspaso === 'total' ? 'todo el saldo pendiente' : `${facturaIdsElegidas.length} factura(s) específica(s)`);

        // Saldo del destino después del traspaso (positivo = debe, negativo = a favor)
        const nuevoDestino = Math.round((Number(destinoElegido.saldo_actual) + (aFavor ? -monto : monto)) * 100) / 100;
        let textoDestino;
        if (nuevoDestino > 0) textoDestino = `pasará a deber <strong>${formatoColones(nuevoDestino)}</strong>`;
        else if (nuevoDestino < 0) textoDestino = `quedará con saldo a favor de <strong>${formatoColones(-nuevoDestino)}</strong>`;
        else textoDestino = 'quedará sin deuda (<strong>₡0.00</strong>)';

        $('resumen-final').innerHTML = `
            Se va a traspasar <strong>${formatoColones(monto)}</strong> (${detalle})
            desde <strong>${origenElegido.nombre}</strong> hacia <strong>${destinoElegido.nombre}</strong>.<br>
            ${origenElegido.nombre} quedará con saldo <strong>₡0.00</strong>${aFavor ? '' : ' (o el resto, si fue parcial)'}.<br>
            ${destinoElegido.nombre} ${textoDestino}.
        `;
        $('error-traspaso').innerHTML = '';
        $('tarjeta-confirmar').style.display = 'block';
    }

    async function confirmarTraspaso() {
        const payload = {
            cliente_origen_id: origenElegido.id,
            cliente_destino_id: destinoElegido.id,
            factura_ids: modoTraspaso === 'facturas' ? facturaIdsElegidas : null,
        };

        const resp = await fetch(`{{ route('traspaso.store') }}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify(payload)
        });
        const datos = await resp.json();

        if (!resp.ok) {
            $('error-traspaso').innerHTML = `<div class="alerta-error">${datos.mensaje ?? 'No se pudo realizar el traspaso.'}</div>`;
            return;
        }

        enviarRecibos([datos.operacion_origen_id, datos.operacion_destino_id]);

        Swal.fire({
            title: 'Traspaso realizado',
            html: `
                <a href="/operaciones/${datos.operacion_origen_id}/comprobante" target="_blank">Ver comprobante del cliente origen</a><br><br>
                <a href="/operaciones/${datos.operacion_destino_id}/comprobante" target="_blank">Ver comprobante del cliente destino</a>
            `,
            icon: 'success',
       }).then(() => quitarOrigen());
    }
</script>
@endpush