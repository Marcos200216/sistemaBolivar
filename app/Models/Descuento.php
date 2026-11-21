<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Descuento extends Model
{
    protected $table = 'descuentos';

    protected $fillable = [
        'operacion_id', 'cliente_id', 'factura_id',
        'saldo_inicial', 'saldo_final', 'monto', 'motivo',
    ];

    public function operacion()
    {
        return $this->belongsTo(Operacion::class);
    }

    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }
}