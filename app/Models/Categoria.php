<?php
// Categoria.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $connection = 'catalogo';
    protected $table = 'categorias';
    protected $fillable = ['nombre', 'slug', 'imagen', 'orden', 'activo', 'canal'];
    protected $casts = ['activo' => 'boolean'];

    public function subcategorias()
    {
        return $this->hasMany(Subcategoria::class);
    }
}