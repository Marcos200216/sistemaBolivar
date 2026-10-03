<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoCompra extends Model
{
    protected $table = 'pagos_compra'; // Eloquent pluraliza distinto ("pago_compras")

    protected $fillable = ['compra_id', 'monto', 'fecha'];
    protected $casts = ['monto' => 'decimal:2', 'fecha' => 'date'];

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }
}