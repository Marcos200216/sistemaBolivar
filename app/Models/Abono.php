<?php
// app/Models/Abono.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Abono extends Model
{
    protected $fillable = [
        'operacion_id', 'cliente_id', 'factura_id', 'saldo_inicial', 'saldo_final', 'sinpe',
        'comprobante_sinpe', 'efectivo', 'monto_abono', 'descuento', 'devolucion', 'es_abono',
        'fecha', 'abono_id_legacy',
    ];

    protected $casts = [
        'es_abono' => 'boolean',
        'fecha' => 'datetime',
        'saldo_inicial' => 'decimal:2', 'saldo_final' => 'decimal:2',
        'sinpe' => 'decimal:2', 'efectivo' => 'decimal:2', 'monto_abono' => 'decimal:2',
        'descuento' => 'decimal:2', 'devolucion' => 'decimal:2',
    ];

    public function cliente() { return $this->belongsTo(Cliente::class); }
    public function factura() { return $this->belongsTo(Factura::class); }
    public function operacion() { return $this->belongsTo(Operacion::class); }
}
