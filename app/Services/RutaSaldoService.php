<?php

namespace App\Services;

use App\Enums\EstadoRutaCliente;
use App\Enums\FiltroRuta;
use App\Models\Cliente;
use App\Models\Ruta;
use App\Models\RutaCliente;

/**
 * Mantiene al cliente en la ruta que corresponde a su saldo:
 * saldo > 0 => rutas Activos; saldo <= 0 => rutas Cancelados.
 * Mueve solo filas pendientes o en recobro; lo finalizado se conserva hasta exportar.
 */
class RutaSaldoService
{
   public function sincronizar(int $clienteId, bool $soloQuitar = false): void
    {
        $cliente = Cliente::withoutGlobalScopes()->find($clienteId);
        if (! $cliente) {
            return;
        }

        $correcto = (float) $cliente->saldo_actual > 0 ? FiltroRuta::Activos : FiltroRuta::Cancelados;

        $filas = RutaCliente::where('cliente_id', $cliente->id)
            ->whereIn('estado', ['pendiente', 'recobro'])
            ->whereHas('ruta', fn ($q) => $q->withoutGlobalScopes()
                ->where('sucursal_id', $cliente->sucursal_id)
                ->where('filtro', '!=', $correcto->value))
            ->get();

        foreach ($filas as $fila) {
            if ($soloQuitar) {
                $fila->delete();
                continue;
            }
            $rutaVieja = Ruta::withoutGlobalScopes()->find($fila->ruta_id);

            $destino = Ruta::withoutGlobalScopes()->firstOrCreate(
                [
                    'sucursal_id' => $cliente->sucursal_id,
                    'tipo' => $rutaVieja->tipo->value,
                    'filtro' => $correcto->value,
                ],
                ['nombre' => 'Ruta ' . $rutaVieja->tipo->label() . ' - ' . $correcto->label()]
            );

            // Si ya estaba en la ruta destino, solo se quita la fila sobrante.
            if (RutaCliente::where('ruta_id', $destino->id)->where('cliente_id', $cliente->id)->exists()) {
                $fila->delete();
                continue;
            }

            $fila->update([
                'ruta_id' => $destino->id,
                'orden' => (int) RutaCliente::where('ruta_id', $destino->id)->max('orden') + 1,
                'estado' => EstadoRutaCliente::Pendiente->value,
                'etiqueta' => null,
            ]);
        }
    }
}