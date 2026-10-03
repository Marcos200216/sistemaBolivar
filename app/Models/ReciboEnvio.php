<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReciboEnvio extends Model
{
    public const ENVIADO = 'enviado';
    public const FALLIDO = 'fallido';
    public const INVALIDO = 'invalido';
    public const SIN_TELEFONO = 'sin_telefono';

    protected $table = 'recibo_envios';

    protected $fillable = [
        'operacion_id',
        'cliente_id',
        'sucursal_id',
        'telefono',
        'destino',
        'plantilla',
        'ruta_pdf',
        'estado',
        'whatsapp_message_id',
        'error',
        'enviado_at',
        'reenviado_por',
    ];

    protected $casts = [
        'enviado_at' => 'datetime',
    ];

    public function operacion()
    {
        return $this->belongsTo(Operacion::class);
    }
}