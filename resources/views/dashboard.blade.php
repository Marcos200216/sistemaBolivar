<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard — Bolívar</title>
    <style>
        body { font-family: sans-serif; padding: 24px; max-width: 900px; margin: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; font-size: 14px; }
        form.inline { display: inline; }
        .form-crear { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px; }
        .form-crear input { padding: 6px; }
        .exito { background: #e6ffed; color: #036b26; padding: 10px; border-radius: 6px; margin-bottom: 12px; }
        .errores { background: #ffecec; color: #a30000; padding: 10px; border-radius: 6px; margin-bottom: 12px; }
    </style>
</head>
<body>
    <h1>Bienvenido, {{ auth()->user()->name }}</h1>
    <p>Sucursal actual (sesión): {{ session('sucursal_id') }}</p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>

    <hr>

    <h2>Clientes</h2>

    @if (session('exito'))
        <div class="exito">{{ session('exito') }}</div>
    @endif

    @if ($errors->any())
        <div class="errores">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Máx. crédito</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($clientes as $cliente)
                <tr>
                    <td>{{ $cliente->codigo }}</td>
                    <td>{{ $cliente->nombre }}</td>
                    <td>{{ $cliente->telefono }}</td>
                    <td>{{ $cliente->correo }}</td>
                    <td>{{ $cliente->maximocredito }}</td>
                    <td>
                        <form class="inline" method="POST" action="{{ route('clientes.destroy', $cliente) }}" onsubmit="return confirm('¿Eliminar este cliente?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">No hay clientes en esta sucursal todavía.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3>Nuevo cliente</h3>
    <form class="form-crear" method="POST" action="{{ route('clientes.store') }}">
        @csrf
        <input type="text" name="codigo" placeholder="Código" value="{{ old('codigo') }}" required>
        <input type="text" name="nombre" placeholder="Nombre" value="{{ old('nombre') }}" required>
        <input type="text" name="telefono" placeholder="Teléfono" value="{{ old('telefono') }}">
        <input type="email" name="correo" placeholder="Correo" value="{{ old('correo') }}">
        <input type="text" name="direccion" placeholder="Dirección" value="{{ old('direccion') }}">
        <input type="number" step="0.01" name="maximocredito" placeholder="Máx. crédito" value="{{ old('maximocredito') }}">
        <input type="text" name="genero" placeholder="Género" value="{{ old('genero') }}">
        <button type="submit">Crear</button>
    </form>
</body>
</html>