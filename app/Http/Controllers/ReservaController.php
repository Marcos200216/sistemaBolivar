<?php
// app/Http/Controllers/ReservaController.php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Reserva;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReservaController extends Controller
{
    private const ESTADOS = ['pendiente', 'terminada', 'cancelada'];

    public function index(Request $request)
    {
        $this->exigirGuana();

        if (!$request->wantsJson()) {
            return view('admin.reservas');
        }

        $estado = $request->query('estado', 'pendiente');
        $q = trim((string) $request->query('q', ''));
        $hoy = now()->toDateString();

        $query = Reserva::with('cliente')
            ->when(in_array($estado, self::ESTADOS, true), fn ($w) => $w->where('reservas.estado', $estado))
            ->when($q !== '', fn ($w) => $w->whereHas('cliente', fn ($c) => $c->where(
                fn ($z) => $z->where('nombre', 'like', "%{$q}%")->orWhere('codigo', 'like', "%{$q}%")
            )));

        if ($estado === 'pendiente') {
            // Las de hoy y las próximas primero; las vencidas (fecha pasada sin cerrar) al final
            $query->orderByRaw('reservas.fecha < ?', [$hoy])
                ->orderBy('reservas.fecha')
                ->orderBy('reservas.hora');
        } else {
            $query->orderByDesc('reservas.fecha')->orderByDesc('reservas.hora');
        }
        $query->orderBy('reservas.id');

        $reservas = $query->paginate(15)->through(function (Reserva $r) use ($hoy) {
            $fecha = $r->fecha->toDateString();
            $pendiente = $r->estado === 'pendiente';

            return [
                'id' => $r->id,
                'cliente_id' => $r->cliente_id,
                'cliente_nombre' => $r->cliente?->nombre ?? 'Cliente no disponible',
                'cliente_codigo' => $r->cliente?->codigo,
                'cliente_telefono' => $r->cliente?->telefono,
                'cliente_direccion' => $r->cliente?->direccion,
                'fecha' => $fecha,
                'fecha_texto' => $r->fecha->format('d/m/Y'),
                'hora' => substr((string) $r->hora, 0, 5),
                'hora_texto' => $this->horaTexto((string) $r->hora),
                'motivo' => $r->motivo,
                'estado' => $r->estado,
                'es_hoy' => $pendiente && $fecha === $hoy,
                'vencida' => $pendiente && $fecha < $hoy,
            ];
        });

        return response()->json($reservas);
    }

    public function store(Request $request)
    {
        $this->exigirGuana();
        $data = $this->validar($request);

        Reserva::create($data + [
            'user_id' => Auth::id(),
            'estado' => 'pendiente',
        ]);

        return response()->json(['ok' => true, 'mensaje' => 'Reserva guardada.']);
    }

    public function update(Request $request, Reserva $reserva)
    {
        $this->exigirGuana();
        $reserva->update($this->validar($request));

        return response()->json(['ok' => true, 'mensaje' => 'Reserva actualizada.']);
    }

    public function cambiarEstado(Request $request, Reserva $reserva)
    {
        $this->exigirGuana();

        $data = $request->validate([
            'estado' => 'required|in:pendiente,terminada,cancelada',
        ]);

        $reserva->update(['estado' => $data['estado']]);

        $mensajes = [
            'pendiente' => 'Reserva reactivada.',
            'terminada' => 'Reserva marcada como terminada.',
            'cancelada' => 'Reserva cancelada.',
        ];

        return response()->json(['ok' => true, 'mensaje' => $mensajes[$data['estado']]]);
    }

    private function validar(Request $request): array
    {
        $data = $request->validate([
            'cliente_id' => 'required|integer|exists:clientes,id',
            'fecha' => 'required|date_format:Y-m-d',
            'hora' => 'required|date_format:H:i',
            'motivo' => 'nullable|string|max:500',
        ], [
            'cliente_id.required' => 'Elegí el cliente.',
            'cliente_id.exists' => 'El cliente no existe.',
            'fecha.required' => 'Elegí la fecha.',
            'fecha.date_format' => 'La fecha no es válida.',
            'hora.required' => 'Elegí la hora.',
            'hora.date_format' => 'La hora no es válida.',
            'motivo.max' => 'El motivo no puede pasar de 500 caracteres.',
        ]);

        // Cliente::find respeta el Global Scope: solo clientes de la sucursal activa
        if (!Cliente::find($data['cliente_id'])) {
            throw ValidationException::withMessages(['cliente_id' => 'El cliente no existe en esta sucursal.']);
        }

        $motivo = trim((string) ($data['motivo'] ?? ''));
        $data['motivo'] = $motivo !== '' ? $motivo : null;

        return $data;
    }

    /** Las reservas solo existen en la sucursal mayorista (Guana). */
    private function exigirGuana(): void
    {
        $canal = Sucursal::find(session('sucursal_id'))?->canal;
        $canal = $canal instanceof \BackedEnum ? $canal->value : $canal;

        abort_unless($canal === 'mayorista', 403, 'Las reservas solo existen en la sucursal mayorista.');
    }

    /** "15:30:00" → "3:30 p. m." */
    private function horaTexto(string $hora): string
    {
        [$h, $m] = array_map('intval', explode(':', $hora));

        return (($h % 12) ?: 12) . ':' . str_pad((string) $m, 2, '0', STR_PAD_LEFT) . ' ' . ($h < 12 ? 'a. m.' : 'p. m.');
    }
}