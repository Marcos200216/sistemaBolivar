<?php
// app/Models/DevolucionLinea.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevolucionLinea extends Model
{
    protected $fillable = [
        'devolucion_id', 'producto_id', 'producto_variante_id', 'factura_linea_id',
        'descripcion', 'cantidad', 'precio_unit', 'precio_total', 'descuento',
        'impuesto', 'costo', 'regresa_stock',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3', 'precio_unit' => 'decimal:2', 'precio_total' => 'decimal:2',
        'descuento' => 'decimal:2', 'impuesto' => 'decimal:2', 'costo' => 'decimal:2',
        'regresa_stock' => 'boolean',
    ];

    public function devolucion() { return $this->belongsTo(Devolucion::class); }
    public function facturaLinea() { return $this->belongsTo(FacturaLinea::class); }
}