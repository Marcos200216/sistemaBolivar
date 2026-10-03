<?php

namespace App\Console\Commands;

use App\Enums\EstadoFactura;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\FacturaLinea;
use App\Models\Sucursal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrarClientesFacturas extends Command
{
    protected $signature = 'migrar:clientes-facturas {--fresh : Vacía las tablas antes de migrar}';
    protected $descripcion = 'Migra clientes, facturas y factura_lineas de kahely_legacy a sistema_nuevo (solo Guana + Azur)';

    protected array $tiendaidsPermitidos = [5, 10, 11, 12, 13];

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->warn('Vaciando factura_lineas, facturas y clientes...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('factura_lineas')->truncate();
            DB::table('facturas')->truncate();
            DB::table('clientes')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $sucursalPorTiendaid = Sucursal::pluck('id', 'tiendaid_legacy');

        $this->info('Migrando clientes...');
        $mapaClientes = $this->migrarClientes($sucursalPorTiendaid);
        $this->info(count($mapaClientes) . ' clientes migrados.');

        $this->info('Migrando facturas...');
        $mapaFacturas = $this->migrarFacturas($mapaClientes);
        $this->info(count($mapaFacturas) . ' facturas migradas.');

        $this->info('Migrando líneas de factura...');
        $totalLineas = $this->migrarLineas($mapaFacturas);
        $this->info("$totalLineas líneas migradas.");

        $this->info('Listo.');
        return self::SUCCESS;
    }

    protected function migrarClientes($sucursalPorTiendaid): array
    {
        $mapa = [];
        $codigosUsados = [];

        DB::connection('legacy')->table('cliente')
            ->whereIn('tiendaid', $this->tiendaidsPermitidos)
            ->orderBy('id')
            ->chunkById(200, function ($clientes) use (&$mapa, &$codigosUsados, $sucursalPorTiendaid) {
                foreach ($clientes as $c) {
                    $sucursalId = $sucursalPorTiendaid[$c->tiendaid] ?? null;
                    if (!$sucursalId) continue;

                    $codigo = $this->generarCodigo($c->telefono, $sucursalId, $codigosUsados);

                    $saldo = DB::connection('legacy')->table('abono')
                        ->where('clienteid', $c->id)
                        ->orderByDesc('id')
                        ->value('saldofinal') ?? 0;

                    $nuevo = Cliente::create([
                        'sucursal_id' => $sucursalId,
                        'codigo' => $codigo,
                        'nombre' => $c->nombre,
                        'telefono' => $c->telefono ?: null,
                        'direccion' => $c->domicilio ?: null,
                        'maximocredito' => $c->maximocredito ?? 0,
                        'saldo_actual' => $saldo,
                        'genero' => $c->genero ?: null,
                        'cliente_id_legacy' => $c->id,
                    ]);

                    $mapa[$c->id] = ['id' => $nuevo->id, 'sucursal_id' => $sucursalId];
                }
            });

        return $mapa;
    }

    protected function generarCodigo(?string $telefono, int $sucursalId, array &$codigosUsados): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $telefono);
        if (strlen($digitos) < 4) return null;

        $base = substr($digitos, -4);
        $codigo = $base;
        $sufijo = 2;

        while (isset($codigosUsados["$sucursalId-$codigo"])) {
            $codigo = $base . '-' . $sufijo;
            $sufijo++;
        }

        $codigosUsados["$sucursalId-$codigo"] = true;
        return $codigo;
    }

    protected function migrarFacturas(array $mapaClientes): array
    {
        $mapa = [];

        DB::connection('legacy')->table('factura')
            ->whereIn('clienteid', array_keys($mapaClientes))
            ->orderBy('id')
            ->chunkById(200, function ($facturas) use (&$mapa, $mapaClientes) {
                foreach ($facturas as $f) {
                    $clienteInfo = $mapaClientes[$f->clienteid] ?? null;
                    if (!$clienteInfo) continue;

                    $pie = DB::connection('legacy')->table('pie')
                        ->where('facturaid', $f->id)
                        ->first();

                    $nueva = Factura::create([
                        'sucursal_id' => $clienteInfo['sucursal_id'], // sucursal del CLIENTE, no la de la factura legacy
                        'cliente_id' => $clienteInfo['id'],
                        'estado' => EstadoFactura::desdeLegacy($f->estado)->value,
                        'plazo' => $f->plazo,
                        'montototal' => $pie->montototal ?? 0,
                        'impuesto' => $pie->impuesto ?? 0,
                        'descuento' => $pie->descuento ?? 0,
                        'flete' => $pie->flete ?? 0,
                        'total' => $pie->total ?? 0,
                        'pago' => $pie->pago ?? 0,
                        'vuelto' => $pie->vuelto ?? 0,
                        'factura_id_legacy' => $f->id,
                    ]);

                    $mapa[$f->id] = $nueva->id;
                }
            });

        return $mapa;
    }

    protected function migrarLineas(array $mapaFacturas): int
    {
        $total = 0;

        DB::connection('legacy')->table('linea')
            ->whereIn('facturaid', array_keys($mapaFacturas))
            ->orderBy('id')
            ->chunkById(500, function ($lineas) use (&$total, $mapaFacturas) {
                $filas = [];
                foreach ($lineas as $l) {
                    $facturaId = $mapaFacturas[$l->facturaid] ?? null;
                    if (!$facturaId) continue;

                    $filas[] = [
                        'factura_id' => $facturaId,
                        'producto_id' => $l->productoid, // referencia informativa, sin FK real al catálogo nuevo
                        'producto_variante_id' => null,
                        'descripcion' => $l->descripcion,
                        'costo_unit' => $l->costo,
                        'precio_unit' => $l->preciounit,
                        'cantidad' => $l->cantidad,
                        'descuento' => $l->descuento ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if ($filas) {
                    FacturaLinea::insert($filas);
                    $total += count($filas);
                }
            });

        return $total;
    }
}
