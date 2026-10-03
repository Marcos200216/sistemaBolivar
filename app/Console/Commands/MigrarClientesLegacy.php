<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrarClientesLegacy extends Command
{
    protected $signature = 'migrar:clientes-legacy {--fresh : Borra los clientes migrados antes de volver a correr}';

    protected $description = 'Migra clientes desde kahely_legacy hacia la tabla clientes del sistema nuevo';

    // Mapeo tiendaid (legacy) => sucursal_id (nuevo)
    private array $mapaSucursales = [
        5 => 1,   // Guana
        10 => 2,  // Azur 1
        11 => 3,  // Azur 2
        12 => 4,  // Azur 3
        13 => 5,  // Azur 4
    ];

    public function handle(): int
    {
        if ($this->option('fresh')) {
            Cliente::whereNotNull('cliente_id_legacy')->delete();
            $this->info('Clientes migrados anteriormente eliminados.');
        }

        $clientesLegacy = DB::connection('legacy')
            ->table('cliente')
            ->whereIn('tiendaid', array_keys($this->mapaSucursales))
            ->get();

        $this->info("Encontrados {$clientesLegacy->count()} clientes en legacy para migrar.");

        $barra = $this->output->createProgressBar($clientesLegacy->count());
        $creados = 0;
        $omitidos = 0;

        foreach ($clientesLegacy as $c) {
            $sucursalId = $this->mapaSucursales[$c->tiendaid];

            // Evitar duplicar si ya se migró antes (por cliente_id_legacy)
            $yaExiste = Cliente::where('cliente_id_legacy', $c->id)->exists();

            if ($yaExiste) {
                $omitidos++;
                $barra->advance();
                continue;
            }

            $codigo = $this->generarCodigo($c->telefono, $sucursalId);

            Cliente::create([
                'sucursal_id' => $sucursalId,
                'codigo' => $codigo,
                'nombre' => $c->nombre,
                'telefono' => $c->telefono ?: null,
                'correo' => null, // no existe en legacy
                'direccion' => $c->domicilio ?: null, // ojo: domicilio es la dirección real, no 'direccion'
                'maximocredito' => $c->maximocredito ?? 0,
                'saldo_actual' => 0, // se calcula en un paso aparte (lógica de saldo consolidado)
                'genero' => $c->genero ?: null,
                'cliente_id_legacy' => $c->id,
            ]);

            $creados++;
            $barra->advance();
        }

        $barra->finish();
        $this->newLine(2);
        $this->info("Migración completa: {$creados} clientes creados, {$omitidos} omitidos (ya existían).");

        return self::SUCCESS;
    }

    private function generarCodigo(?string $telefono, int $sucursalId): ?string
    {
        if (empty($telefono)) {
            return null;
        }

        $base = substr($telefono, -4);
        $codigo = $base;
        $sufijo = 2;

        // Si el código ya existe en esta sucursal, le agrega sufijo hasta encontrar uno libre
        while (Cliente::where('sucursal_id', $sucursalId)->where('codigo', $codigo)->exists()) {
            $codigo = $base.'-'.$sufijo;
            $sufijo++;
        }

        return $codigo;
    }
}