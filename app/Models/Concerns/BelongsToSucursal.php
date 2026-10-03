<?php
// app/Models/Concerns/BelongsToSucursal.php

namespace App\Models\Concerns;

use App\Models\Scopes\SucursalScope;
use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 * @method static void creating(\Closure|string $callback)
 * @method static void addGlobalScope(\Illuminate\Database\Eloquent\Scope|\Closure|string $scope, \Closure $implementation = null)
 */
trait BelongsToSucursal
{
    public static function bootBelongsToSucursal(): void
    {
        static::addGlobalScope(new SucursalScope);

        static::creating(function ($model) {
            if (empty($model->sucursal_id) && session()->has('sucursal_id')) {
                $model->sucursal_id = session('sucursal_id');
            }
        });
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function scopeTodasLasSucursales($query)
    {
        return $query->withoutGlobalScope(SucursalScope::class);
    }
}