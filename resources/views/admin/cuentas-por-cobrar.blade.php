{{-- resources/views/admin/cuentas-por-cobrar.blade.php --}}
@extends('layouts.app')

@section('titulo', 'Cuentas por cobrar')

@section('contenido')
    <h1 style="margin-top:0;">Cuentas por cobrar</h1>

    <div style="background:#eef3ff; padding:12px 16px; border-radius:6px; font-weight:600; margin-bottom:16px;">
        Total pendiente de cobro: ₡{{ number_format($totalPendiente, 2) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Saldo actual</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($clientes as $cliente)
                <tr>
                    <td>{{ $cliente->codigo }}</td>
                    <td>{{ $cliente->nombre }}</td>
                    <td>{{ $cliente->telefono }}</td>
                    <td>₡{{ number_format($cliente->saldo_actual, 2) }}</td>
                    <td><a href="#">Ver abonos</a></td>
                </tr>
            @empty
                <tr><td colspan="5">No hay clientes con saldo pendiente en esta sucursal.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $clientes->links() }}
@endsection