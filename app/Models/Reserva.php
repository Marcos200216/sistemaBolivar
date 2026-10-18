<?php
// app/Models/Reserva.php

namespace App\Models;

use App\Models\Concerns\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    use BelongsToSucursal;

    protected $fillable = ['sucursal_id', 'cliente_id', 'user_id', 'fecha', 'hora', 'motivo', 'estado'];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}