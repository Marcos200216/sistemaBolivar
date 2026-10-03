<?php
// app/Models/Gasto.php
namespace App\Models;

use App\Models\Concerns\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;

class Gasto extends Model
{
    use BelongsToSucursal;

    protected $table = 'gastos'; // por si acaso, aunque esta sí pluraliza bien

    protected $fillable = ['sucursal_id', 'categoria', 'descripcion', 'monto', 'fecha'];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'date',
    ];
}