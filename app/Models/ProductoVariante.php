<?php
// ProductoVariante.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoVariante extends Model
{
    protected $connection = 'catalogo';
    protected $table = 'producto_variantes';
    protected $fillable = ['producto_id', 'talla', 'color', 'stock'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}