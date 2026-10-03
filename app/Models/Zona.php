<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;

class Zona extends Model
{
    use BelongsToSucursal;

    protected $fillable = [
        'sucursal_id',
        'nombre',
        'zona_id_legacy',
    ];
}