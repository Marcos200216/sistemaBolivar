<?php
// app/Models/ReporteRuta.php

namespace App\Models;

use App\Models\Concerns\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;

class ReporteRuta extends Model
{
    use BelongsToSucursal;

    protected $table = 'reportes_ruta';

    protected $fillable = ['sucursal_id', 'ruta_id', 'tipo', 'filtro', 'nombre_ruta', 'generado_en', 'datos'];

    protected $casts = [
        'generado_en' => 'datetime',
        'datos' => 'array',
    ];

    public function ruta()
    {
        return $this->belongsTo(Ruta::class);
    }
}