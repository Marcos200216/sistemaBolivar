<?php
// app/Models/RutaCliente.php — SIN BelongsToSucursal, hereda aislamiento vía ruta_id

namespace App\Models;

use App\Enums\EstadoRutaCliente;
use Illuminate\Database\Eloquent\Model;

class RutaCliente extends Model
{
    protected $table = 'ruta_clientes'; // por si acaso Eloquent no lo pluraliza bien, mejor explícito

    protected $fillable = ['ruta_id', 'cliente_id', 'orden', 'estado', 'etiqueta'];

    protected $casts = [
        'estado' => EstadoRutaCliente::class,
    ];

    public function ruta()
    {
        return $this->belongsTo(Ruta::class);
    }

    public function cliente()
{
    return $this->belongsTo(Cliente::class)->withoutGlobalScope(\App\Models\Scopes\SucursalScope::class);
}
}