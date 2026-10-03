{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.app')

@section('titulo', 'Inicio')

@push('estilos')
<style>
    .encabezado-dashboard { margin-bottom: 18px; }
    .encabezado-dashboard h1 { margin: 0 0 4px; font-weight: 700; font-size: 22px; color: var(--azul-oscuro); }
    .encabezado-dashboard p { margin: 0; font-size: 13.5px; color: var(--texto-tenue); }

    .rejilla-estadisticas { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
    .tarjeta-estadistica {
        background: var(--superficie); border: 1px solid var(--borde); border-radius: 12px;
        box-shadow: var(--sombra-card); padding: 16px; display: flex; align-items: center; gap: 12px; min-width: 0;
    }
    .icono-estadistica {
        display: inline-flex; align-items: center; justify-content: center;
        width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0; color: #fff;
    }
    .icono-estadistica svg { width: 20px; height: 20px; }
    .icono-azul { background: linear-gradient(160deg, var(--azul-medio), var(--azul-oscuro)); }
    .icono-ambar { background: linear-gradient(160deg, #f5a524, #c9761a); }
    .icono-rojo { background: linear-gradient(160deg, #e5484d, #b42318); }
    .icono-verde { background: linear-gradient(160deg, #17b06b, #0d8a52); }
    .etiqueta-estadistica { font-size: 12.5px; font-weight: 600; color: var(--texto-tenue); margin-bottom: 4px; }
    .valor-estadistica { font-size: 24px; font-weight: 700; color: var(--texto); line-height: 1.1; }

    .rejilla-paneles { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-top: 20px; }
    .panel {
        background: var(--superficie); border: 1px solid var(--borde); border-radius: 12px;
        box-shadow: var(--sombra-card); padding: 16px 18px; min-width: 0;
    }
    .panel-ancho { grid-column: 1 / -1; }
    .panel h2 { margin: 0 0 12px; font-size: 15px; font-weight: 700; color: var(--azul-oscuro); }
    .vacio { margin: 0; font-size: 13.5px; color: var(--texto-tenue); }

    .fila-lista {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        padding: 10px 0; border-top: 1px solid var(--borde);
    }
    .fila-lista:first-of-type { border-top: 0; padding-top: 0; }
    .fila-info { min-width: 0; }
    .fila-nombre { font-size: 14px; font-weight: 600; color: var(--texto); overflow-wrap: anywhere; }
    .fila-sub { font-size: 12.5px; color: var(--texto-tenue); }
    .enlace-accion {
        flex-shrink: 0; padding: 7px 12px; border-radius: 8px; font-size: 13px; font-weight: 600;
        color: var(--azul-medio); border: 1px solid var(--borde); text-decoration: none;
    }
    .enlace-accion:hover { border-color: var(--azul-medio); }
    .insignia { display: inline-block; margin-left: 6px; padding: 1px 7px; border-radius: 999px; font-size: 11.5px; background: #fdf1dc; color: #a35f0c; }
    .atraso { color: #b42318; font-weight: 600; }

    @media (max-width: 980px) { .rejilla-estadisticas { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 720px) { .rejilla-paneles { grid-template-columns: 1fr; } }
    @media (max-width: 640px) {
        .encabezado-dashboard h1 { font-size: 19px; }
        .rejilla-estadisticas { gap: 10px; }
        .tarjeta-estadistica { padding: 12px; gap: 10px; }
        .valor-estadistica { font-size: 21px; }
        .panel { padding: 14px; }
    }
</style>
@endpush

@section('contenido')
    @php $colones = fn ($n) => '₡' . number_format($n, 2); @endphp

    <div class="encabezado-dashboard">
        <h1>Inicio</h1>
        <p>Lo que necesita atención en esta sucursal.</p>
    </div>

    <div class="rejilla-estadisticas">
        <div class="tarjeta-estadistica">
            <span class="icono-estadistica icono-azul">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.5 2.9-6 6.5-6s6.5 2.5 6.5 6"/><path d="M16.5 8.5a3 3 0 1 1 0-5.9"/><path d="M18.5 14.2c2.3.6 3.9 2.7 3.9 5.3"/></svg>
            </span>
            <div>
                <div class="etiqueta-estadistica">Clientes</div>
                <div class="valor-estadistica">{{ $totalClientes }}</div>
            </div>
        </div>

        <div class="tarjeta-estadistica">
            <span class="icono-estadistica icono-ambar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v10M9.5 9.5h4a1.5 1.5 0 0 1 0 3h-3a1.5 1.5 0 0 0 0 3H15"/></svg>
            </span>
            <div>
                <div class="etiqueta-estadistica">Con saldo pendiente</div>
                <div class="valor-estadistica">{{ $totalConSaldo }}</div>
            </div>
        </div>

        <div class="tarjeta-estadistica">
            <span class="icono-estadistica icono-rojo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </span>
            <div>
                <div class="etiqueta-estadistica">Atrasados (+4 semanas)</div>
                <div class="valor-estadistica">{{ $totalAtrasados }}</div>
            </div>
        </div>

        <div class="tarjeta-estadistica">
            <span class="icono-estadistica icono-verde">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h8l4 4v14H7Z"/><path d="M15 3v4h4"/><path d="M9 13h6M9 17h6"/></svg>
            </span>
            <div>
                <div class="etiqueta-estadistica">Operaciones de hoy</div>
                <div class="valor-estadistica">{{ $operacionesHoy }}</div>
            </div>
        </div>
    </div>

    <div class="rejilla-paneles">
        <section class="panel">
            <h2>Clientes atrasados</h2>
            @forelse ($topAtrasados as $item)
                <div class="fila-lista">
                    <div class="fila-info">
                        <div class="fila-nombre">{{ $item['cliente']->nombre }}</div>
                        <div class="fila-sub">
                            <span class="atraso">Hace {{ intdiv($item['dias'], 7) }} semanas sin abonar</span>
                            · Debe {{ $colones($item['cliente']->saldo_actual) }}
                        </div>
                    </div>
                    <a class="enlace-accion" href="{{ route('facturacion.index', ['cliente_id' => $item['cliente']->id, 'operacion' => 'abonar']) }}">Abonar</a>
                </div>
            @empty
                <p class="vacio">No hay clientes atrasados.</p>
            @endforelse
            @if ($totalAtrasados > $topAtrasados->count())
               <p class="vacio" style="margin-top:10px;">Y {{ $totalAtrasados - $topAtrasados->count() }} más.</p>
            @endif
        </section>

        <section class="panel">
            <h2>Clientes con mayor deuda</h2>
            @forelse ($topDeudores as $cliente)
                <div class="fila-lista">
                    <div class="fila-info">
                        <div class="fila-nombre">{{ $cliente->nombre }}</div>
                        <div class="fila-sub">Debe {{ $colones($cliente->saldo_actual) }}</div>
                    </div>
                    <a class="enlace-accion" href="{{ route('facturacion.index', ['cliente_id' => $cliente->id, 'operacion' => 'abonar']) }}">Abonar</a>
                </div>
            @empty
                <p class="vacio">Ningún cliente tiene saldo pendiente.</p>
            @endforelse
        </section>

        <section class="panel panel-ancho">
            <h2>Últimas operaciones</h2>
            @forelse ($ultimasOperaciones as $op)
                <div class="fila-lista">
                    <div class="fila-info">
                        <div class="fila-nombre">
                            #{{ $op->numero }} · {{ $nombres[$op->cliente_id] ?? 'Cliente' }}
                            @if ($op->no_abono)<span class="insignia">No abonó</span>@endif
                        </div>
                        <div class="fila-sub">{{ $op->created_at?->format('d/m/Y H:i') }} · Saldo final {{ $colones($op->saldo_final) }}</div>
                    </div>
                    <a class="enlace-accion" href="{{ route('operaciones.comprobante', $op) }}" target="_blank" rel="noopener">Comprobante</a>
                </div>
            @empty
                <p class="vacio">Todavía no hay operaciones en esta sucursal.</p>
            @endforelse
        </section>
    </div>
@endsection