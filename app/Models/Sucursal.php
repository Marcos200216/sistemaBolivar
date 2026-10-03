<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    protected $table = 'sucursales';

    protected $fillable = ['nombre', 'canal', 'activa', 'tiendaid_legacy'];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }
}