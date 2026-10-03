<?php
// Subcategoria.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subcategoria extends Model
{
    protected $connection = 'catalogo';
    protected $table = 'subcategorias';
    protected $fillable = ['categoria_id', 'nombre', 'slug', 'orden', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}