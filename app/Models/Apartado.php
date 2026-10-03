<?php
// app/Models/Apartado.php

namespace App\Models;

use App\Models\Concerns\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;

class Apartado extends Model
{
    use BelongsToSucursal;

    protected $fillable = ['sucursal_id', 'cliente_id', 'nota'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function lineas()
    {
        return $this->hasMany(ApartadoLinea::class);
    }
}