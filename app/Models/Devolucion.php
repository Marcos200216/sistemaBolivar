<?php
// app/Models/Devolucion.php
namespace App\Models;

use App\Enums\MotivoDevolucion;
use Illuminate\Database\Eloquent\Model;

class Devolucion extends Model
{
    protected $table = 'devoluciones';

    protected $fillable = [
        'operacion_id', 'cliente_id', 'factura_id', 'estado_legacy', 'motivo', 'total_lineas',
        'monto_total', 'impuesto', 'descuento', 'total', 'fecha', 'devolucion_id_legacy',
    ];

    protected $casts = [
        'motivo' => MotivoDevolucion::class,
        'fecha' => 'datetime',
        'monto_total' => 'decimal:2', 'impuesto' => 'decimal:2',
        'descuento' => 'decimal:2', 'total' => 'decimal:2',
    ];

    public function cliente() { return $this->belongsTo(Cliente::class); }
    public function factura() { return $this->belongsTo(Factura::class); }
    public function operacion() { return $this->belongsTo(Operacion::class); }
    public function lineas() { return $this->hasMany(DevolucionLinea::class); }
}