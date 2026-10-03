<?php
// app/Models/ApartadoLinea.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApartadoLinea extends Model
{
    protected $table = 'apartado_lineas'; // por si acaso, mismo patrón que Devolucion/Proveedor

    protected $fillable = ['apartado_id', 'producto_id', 'producto_variante_id', 'nombre', 'cantidad'];

    public function apartado()
    {
        return $this->belongsTo(Apartado::class);
    }
}