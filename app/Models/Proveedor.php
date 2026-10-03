<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use BelongsToSucursal;

    protected $table = 'proveedores'; // Eloquent pluraliza mal "Proveedor" en español

    protected $fillable = ['sucursal_id', 'nombre', 'telefono'];

    public function compras()
    {
        return $this->hasMany(Compra::class);
    }
}