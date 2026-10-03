<?php
// app/Models/FacturaLinea.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacturaLinea extends Model
{
    protected $fillable = [
        'factura_id',
        'producto_id',
        'producto_variante_id',
        'descripcion',
        'costo_unit',
        'precio_unit',
        'cantidad',
        'descuento',
    ];

    protected $casts = [
        'costo_unit' => 'decimal:2',
        'precio_unit' => 'decimal:2',
        'descuento' => 'decimal:2',
    ];

    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }
}