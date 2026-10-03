<?php
// Producto.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $connection = 'catalogo';
    protected $table = 'productos';

    protected $fillable = [
        'subcategoria_id', 'nombre', 'slug', 'descripcion',
        'destacado', 'activo', 'tiene_color', 'codigo', 'precio',
    ];

    protected $casts = [
        'destacado' => 'boolean',
        'activo' => 'boolean',
        'tiene_color' => 'boolean',
        'precio' => 'decimal:2',
    ];

    public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class);
    }

    public function variantes()
    {
        return $this->hasMany(ProductoVariante::class);
    }
}