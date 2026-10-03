<?php
// app/Models/Ruta.php

namespace App\Models;

use App\Enums\FiltroRuta;
use App\Enums\TipoRuta;
use App\Models\Concerns\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    use BelongsToSucursal;

    protected $fillable = ['sucursal_id', 'nombre', 'tipo', 'filtro', 'ultimo_reinicio'];

    protected $casts = [
        'tipo' => TipoRuta::class,
        'filtro' => FiltroRuta::class,
        'ultimo_reinicio' => 'datetime',
    ];

    public function clientes()
    {
        return $this->hasMany(RutaCliente::class);
    }

    public function clientesActivos()
    {
        return $this->hasMany(RutaCliente::class)
            ->whereIn('estado', ['pendiente', 'recobro'])
            ->orderBy('orden');
    }

    public function clientesFinalizados()
    {
        return $this->hasMany(RutaCliente::class)
            ->whereIn('estado', ['finalizado', 'no_abono'])
            ->orderBy('orden');
    }

    public function reportes()
    {
        return $this->hasMany(ReporteRuta::class);
    }
}