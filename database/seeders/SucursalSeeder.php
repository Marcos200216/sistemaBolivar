<?php
// database/seeders/SucursalSeeder.php

namespace Database\Seeders;

use App\Models\Sucursal;
use Illuminate\Database\Seeder;

class SucursalSeeder extends Seeder
{
    public function run(): void
    {
        $sucursales = [
            ['nombre' => 'Distribuidora Guana', 'canal' => 'mayorista', 'tiendaid_legacy' => 5],
            ['nombre' => 'Distribuidora Azur 1', 'canal' => 'normal', 'tiendaid_legacy' => 10],
            ['nombre' => 'Distribuidora Azur 2', 'canal' => 'normal', 'tiendaid_legacy' => 11],
            ['nombre' => 'Distribuidora Azur 3', 'canal' => 'normal', 'tiendaid_legacy' => 12],
            ['nombre' => 'Distribuidora Azur 4', 'canal' => 'normal', 'tiendaid_legacy' => 13],
        ];

        foreach ($sucursales as $s) {
            Sucursal::updateOrCreate(['tiendaid_legacy' => $s['tiendaid_legacy']], $s);
        }
    }
}