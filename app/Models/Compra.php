<?php
// app/Models/Compra.php
namespace App\Models;

use App\Enums\EstadoCompra;
use App\Enums\TipoCompra;
use App\Models\Concerns\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    use BelongsToSucursal;

    protected $fillable = [
        'sucursal_id', 'proveedor_id', 'descripcion', 'monto_total',
        'tipo', 'estado', 'saldo_pendiente', 'fecha',
    ];

    protected $casts = [
        'tipo' => TipoCompra::class,
        'estado' => EstadoCompra::class,
        'monto_total' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
        'fecha' => 'date',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function pagos()
    {
        return $this->hasMany(PagoCompra::class);
    }
}