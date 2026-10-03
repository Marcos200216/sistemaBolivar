<?php
// app/Models/Factura.php

namespace App\Models;

use App\Models\Concerns\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    use BelongsToSucursal;

    protected $fillable = [
    'operacion_id',
    'sucursal_id',
    'cliente_id',
    'estado',
    'plazo',
    'fecha',
    'montototal',
    'impuesto',
    'descuento',
    'flete',
    'total',
    'pago',
    'vuelto',
    'efectivo',
    'sinpe',
    'saldo_favor_aplicado',
    'comprobante_sinpe',
    'factura_id_legacy',
    'anulada_at',
    'anulada_por',
    'motivo_anulacion',
];

protected $casts = [
    'fecha' => 'datetime',
    'montototal' => 'decimal:2',
    'impuesto' => 'decimal:2',
    'descuento' => 'decimal:2',
    'flete' => 'decimal:2',
    'total' => 'decimal:2',
    'pago' => 'decimal:2',
    'vuelto' => 'decimal:2',
    'efectivo' => 'decimal:2',
    'sinpe' => 'decimal:2',
    'saldo_favor_aplicado' => 'decimal:2',
    'anulada_at' => 'datetime',
];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function operacion()
    {
        return $this->belongsTo(Operacion::class);
    }

    public function lineas()
    {
        return $this->hasMany(FacturaLinea::class);
    }

    public function abonos()
    {
        return $this->hasMany(Abono::class);
    }

    public function devoluciones()
    {
        return $this->hasMany(Devolucion::class);
    }

    // Ventas hechas desde el sistema nuevo (las migradas no tienen operación)
    public function scopeNuevas($query)
    {
        return $query->whereNotNull($this->getTable() . '.operacion_id');
    }

    public function anuladaPor()
{
    return $this->belongsTo(User::class, 'anulada_por');
}
}