<?php
// app/Models/Operacion.php

namespace App\Models;

use App\Models\Concerns\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;

class Operacion extends Model
{
    use BelongsToSucursal;

    // Eloquent pluralizaría a "operacions"
    protected $table = 'operaciones';

    protected $fillable = [
        'sucursal_id',
        'cliente_id',
        'user_id',
        'ruta_cliente_id',
        'numero',
        'saldo_inicial',
        'saldo_final',
        'no_abono',
        'no_abono_descripcion',
        'tipo',
        'traspaso_cliente_id',
        'fecha',
    ];

    protected $casts = [
        'saldo_inicial' => 'decimal:2',
        'saldo_final' => 'decimal:2',
        'no_abono' => 'boolean',
        'fecha' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function rutaCliente()
    {
        return $this->belongsTo(RutaCliente::class);
    }
    public function abonos()
    {
        return $this->hasMany(Abono::class);
    }
    public function facturas()
    {
        return $this->hasMany(Factura::class);
    }
    public function devoluciones()
    {
        return $this->hasMany(Devolucion::class);
    }
    public function traspasoCliente()
    {
        return $this->belongsTo(Cliente::class, 'traspaso_cliente_id');
    }
}
