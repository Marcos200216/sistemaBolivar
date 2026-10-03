<?php
// app/Models/Cliente.php

namespace App\Models;

use App\Models\Concerns\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use BelongsToSucursal;

    protected $fillable = [
        'sucursal_id',
        'zona_id',
        'codigo',
        'nombre',
        'telefono',
        'correo',
        'direccion',
        'maximocredito',
        'saldo_actual',
        'genero',
        'cliente_id_legacy',
        'latitud',
        'longitud',
    ];

    protected $casts = [
    'maximocredito' => 'decimal:2',
    'saldo_actual' => 'decimal:2',
    'latitud' => 'float',
    'longitud' => 'float',
];

    public function scopeActivos($query)
    {
        return $query->where('saldo_actual', '>', 0);
    }

    public function scopeCancelados($query)
    {
        return $query->where('saldo_actual', '<=', 0);
    }
}