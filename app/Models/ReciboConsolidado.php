<?php
// app/Models/ReciboConsolidado.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReciboConsolidado extends Model
{
    protected $fillable = [
        'cliente_id', 'monto_total', 'detalle_facturas_legacy',
        'admin_registro', 'fecha', 'consolidado_id_legacy',
    ];

    protected $casts = ['fecha' => 'datetime', 'monto_total' => 'decimal:2'];

    public function cliente() { return $this->belongsTo(Cliente::class); }
}