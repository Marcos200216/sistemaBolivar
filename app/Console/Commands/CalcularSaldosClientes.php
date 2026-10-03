<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalcularSaldosClientes extends Command
{
    protected $signature = 'migrar:saldos-clientes';

    protected $description = 'Calcula el saldo actual de cada cliente migrado, usando el último abono registrado en legacy';

    public function handle(): int
    {
        $clientes = Cliente::whereNotNull('cliente_id_legacy')->get();

        $this->info("Calculando saldo para {$clientes->count()} clientes...");

        $barra = $this->output->createProgressBar($clientes->count());
        $actualizados = 0;
        $sinAbonos = 0;

        foreach ($clientes as $cliente) {
            $ultimoAbono = DB::connection('legacy')
                ->table('abono')
                ->where('clienteid', $cliente->cliente_id_legacy)
                ->orderBy('datecreated', 'desc')
                ->first();

            if ($ultimoAbono) {
                $cliente->update(['saldo_actual' => $ultimoAbono->saldofinal]);
                $actualizados++;
            } else {
                $sinAbonos++;
            }

            $barra->advance();
        }

        $barra->finish();
        $this->newLine(2);
        $this->info("Listo: {$actualizados} clientes con saldo actualizado, {$sinAbonos} sin ningún abono registrado (quedan en 0).");

        return self::SUCCESS;
    }
}