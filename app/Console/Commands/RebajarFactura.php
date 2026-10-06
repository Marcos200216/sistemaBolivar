<?php

namespace App\Console\Commands;

use App\Enums\EstadoFactura;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RebajarFactura extends Command
{
    protected $signature = 'rebajar:factura
        {--cliente= : cliente_id_legacy del cliente}
        {--factura= : factura_id_legacy de la factura}
        {--monto= : monto exacto a rebajar (debe ser el pendiente completo de la factura)}
        {--motivo= : motivo de la rebaja (queda en el log y en la reversa)}
        {--user= : id del usuario autor (por defecto el superadmin de menor id)}
        {--apply : Escribe en la BD (sin esto es corrida en seco)}
        {--confirmar-bd= : Nombre de la BD, obligatorio con --apply o --revertir}
        {--revertir= : Archivo de reversa a deshacer}';

    protected $description = 'Rebaja (perdona) el pendiente completo de una factura de crédito dejando una operación de registro. Corrida en seco por defecto.';

    public function handle(): int
    {
        $bd = DB::connection()->getDatabaseName();
        $host = (string) config('database.connections.' . config('database.default') . '.host');
        $this->line("Destino: BD '{$bd}' (" . (in_array($host, ['127.0.0.1', 'localhost'], true) ? 'LOCAL' : 'REMOTA') . ')');

        if ($archivo = $this->option('revertir')) {
            return $this->revertir($archivo, $bd);
        }

        $monto = round((float) $this->option('monto'), 2);
        $motivo = trim((string) $this->option('motivo'));
        if (!$this->option('cliente') || !$this->option('factura') || $monto <= 0 || $motivo === '') {
            $this->error('Indicá --cliente, --factura, --monto y --motivo.');
            return self::FAILURE;
        }

        $cliente = DB::table('clientes')->where('cliente_id_legacy', (int) $this->option('cliente'))->first();
        if (!$cliente) {
            $this->error('El cliente no existe en esta BD.');
            return self::FAILURE;
        }
        $factura = DB::table('facturas')->where('cliente_id', $cliente->id)
            ->where('factura_id_legacy', (int) $this->option('factura'))->first();
        if (!$factura) {
            $this->error('Esa factura no existe para ese cliente.');
            return self::FAILURE;
        }
        if ($factura->estado !== EstadoFactura::Credito->value) {
            $this->error("La factura está en estado '{$factura->estado}', no en crédito.");
            return self::FAILURE;
        }

        $pendiente = $this->pendiente($factura);
        if (abs($pendiente - $monto) > 0.005) {
            $this->error('El pendiente actual de esa factura es ₡' . number_format($pendiente, 2)
                . ', no ₡' . number_format($monto, 2) . '. No se hace nada.');
            return self::FAILURE;
        }

        $saldo = round((float) $cliente->saldo_actual, 2);
        if ($saldo < $monto - 0.005) {
            $this->error('El saldo del cliente (₡' . number_format($saldo, 2) . ') es menor que el monto a rebajar.');
            return self::FAILURE;
        }

        $userId = $this->option('user')
            ? (int) $this->option('user')
            : (int) DB::table('users')->where('es_superadmin', 1)->orderBy('id')->value('id');
        $usuario = $userId ? DB::table('users')->where('id', $userId)->first(['id', 'name']) : null;
        if (!$usuario) {
            $this->error('No encuentro el usuario autor. Pasá --user=ID.');
            return self::FAILURE;
        }

        $sucursalId = (int) $cliente->sucursal_id;
        $saldoFinal = round($saldo - $monto, 2);
        $numero = (int) DB::table('operaciones')->where('sucursal_id', $sucursalId)->max('numero') + 1;

        $this->table(['dato', 'valor'], [
            ['Cliente', $cliente->nombre . ' (viejo ' . $this->option('cliente') . ')'],
            ['Sucursal (id)', $sucursalId],
            ['Factura', $this->option('factura') . ' (id ' . $factura->id . ')'],
            ['Monto a rebajar', number_format($monto, 2)],
            ['Saldo del cliente', number_format($saldo, 2) . ' → ' . number_format($saldoFinal, 2)],
            ['Estado de la factura', $factura->estado . ' → saldada'],
            ['Operación a crear', 'N° ' . $numero . ', tipo rebaja'],
            ['Autor', $usuario->name . ' (id ' . $usuario->id . ')'],
            ['Motivo', $motivo],
        ]);

        if (!$this->option('apply')) {
            $this->warn("CORRIDA EN SECO: no se escribió nada. Para aplicar: --apply --confirmar-bd={$bd}");
            return self::SUCCESS;
        }
        if ($this->option('confirmar-bd') !== $bd) {
            $this->error("Con --apply hay que pasar --confirmar-bd={$bd}.");
            return self::FAILURE;
        }

        try {
            [$opId, $abonoId] = DB::transaction(function () use ($cliente, $factura, $monto, $saldo, $saldoFinal, $sucursalId, $userId) {
                $c = DB::table('clientes')->where('id', $cliente->id)->lockForUpdate()->first();
                $f = DB::table('facturas')->where('id', $factura->id)->lockForUpdate()->first();
                if (round((float) $c->saldo_actual, 2) !== $saldo) {
                    throw new \RuntimeException('El saldo del cliente cambió mientras tanto.');
                }
                if ($f->estado !== EstadoFactura::Credito->value || abs($this->pendiente($f) - $monto) > 0.005) {
                    throw new \RuntimeException('La factura cambió mientras tanto.');
                }

                $numero = (int) DB::table('operaciones')->where('sucursal_id', $sucursalId)->max('numero') + 1;
                $opId = DB::table('operaciones')->insertGetId([
                    'sucursal_id' => $sucursalId,
                    'cliente_id' => $cliente->id,
                    'user_id' => $userId,
                    'ruta_cliente_id' => null,
                    'numero' => $numero,
                    'saldo_inicial' => $saldo,
                    'saldo_final' => $saldoFinal,
                    'no_abono' => 0,
                    'tipo' => 'rebaja',
                    'fecha' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $abonoId = DB::table('abonos')->insertGetId([
                    'operacion_id' => $opId,
                    'cliente_id' => $cliente->id,
                    'factura_id' => $factura->id,
                    'saldo_inicial' => $saldo,
                    'saldo_final' => $saldoFinal,
                    'sinpe' => 0,
                    'efectivo' => 0,
                    'monto_abono' => $monto,
                    'fecha' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($this->pendiente($f) < 0.005) {
                    DB::table('facturas')->where('id', $factura->id)->update(['estado' => EstadoFactura::Saldada->value, 'updated_at' => now()]);
                }
                DB::table('clientes')->where('id', $cliente->id)->update(['saldo_actual' => $saldoFinal, 'updated_at' => now()]);

                return [$opId, $abonoId];
            });
        } catch (\Throwable $e) {
            $this->error('No se aplicó nada: ' . $e->getMessage());
            return self::FAILURE;
        }

        $datos = [
            'bd' => $bd, 'cliente_id' => $cliente->id, 'factura_id' => $factura->id,
            'operacion_id' => $opId, 'abono_id' => $abonoId, 'monto' => $monto,
            'saldo_anterior' => $saldo, 'estado_anterior' => $factura->estado,
            'user_id' => $userId, 'motivo' => $motivo,
        ];
        $reversa = storage_path('app/reversa_rebaja_' . date('Ymd_His') . '.json');
        file_put_contents($reversa, json_encode($datos, JSON_UNESCAPED_UNICODE));
        Log::info('Rebaja de factura aplicada', $datos);
        $this->info("Aplicado. Reversa: {$reversa}");

        $cli = DB::table('clientes')->where('id', $cliente->id)->first();
        $fac = DB::table('facturas')->where('id', $factura->id)->first();
        $suma = DB::table('facturas')->where('cliente_id', $cliente->id)
            ->where('estado', EstadoFactura::Credito->value)->get()
            ->sum(fn ($x) => $this->pendiente($x));
        $this->table(['Verificación', 'Valor', ''], [
            ['Saldo del cliente', number_format((float) $cli->saldo_actual, 2), ''],
            ['Suma pendiente de sus facturas abiertas', number_format($suma, 2),
                abs((float) $cli->saldo_actual - $suma) < 0.005 ? 'OK' : 'REVISAR'],
            ['Estado de la factura rebajada', $fac->estado, $fac->estado === EstadoFactura::Saldada->value ? 'OK' : 'REVISAR'],
            ['Operación', 'N° ' . $numero . ' (id ' . $opId . ')', ''],
        ]);

        return self::SUCCESS;
    }

    private function pendiente(object $f): float
    {
        $abonos = DB::table('abonos')->where('factura_id', $f->id);
        $devs = DB::table('devoluciones')->where('factura_id', $f->id);
        if ($f->operacion_id === null) {
            $abonos->whereNotNull('operacion_id');
            $devs->whereNotNull('operacion_id');
            $base = (float) $f->saldo_migrado;
        } else {
            $base = (float) $f->total;
        }

        return max(0.0, round($base - (float) $abonos->sum('monto_abono') - (float) $devs->sum('total'), 2));
    }

    private function revertir(string $archivo, string $bd): int
    {
        if ($this->option('confirmar-bd') !== $bd) {
            $this->error("Para revertir pasá --confirmar-bd={$bd}.");
            return self::FAILURE;
        }
        $d = json_decode((string) @file_get_contents($archivo), true);
        if (!is_array($d) || empty($d['operacion_id']) || ($d['bd'] ?? null) !== $bd) {
            $this->error('Archivo de reversa inválido o de otra BD.');
            return self::FAILURE;
        }
        if (DB::table('operaciones')->where('cliente_id', $d['cliente_id'])->where('id', '>', $d['operacion_id'])->exists()) {
            $this->error('El cliente tiene operaciones posteriores: no se revierte.');
            return self::FAILURE;
        }
        DB::transaction(function () use ($d) {
            DB::table('abonos')->where('id', $d['abono_id'])->where('operacion_id', $d['operacion_id'])->delete();
            DB::table('operaciones')->where('id', $d['operacion_id'])->delete();
            DB::table('facturas')->where('id', $d['factura_id'])->update(['estado' => $d['estado_anterior']]);
            DB::table('clientes')->where('id', $d['cliente_id'])->update(['saldo_actual' => $d['saldo_anterior']]);
        });
        $this->info('Revertido.');

        return self::SUCCESS;
    }
}