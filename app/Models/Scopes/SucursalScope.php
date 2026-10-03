<?php
// app/Models/Scopes/SucursalScope.php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use RuntimeException;

class SucursalScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (!session()->has('sucursal_id')) {
            throw new RuntimeException(
                "No hay sucursal_id en sesión para consultar [{$model->getTable()}]. " .
                "Si estás en tinker, corré primero: session(['sucursal_id' => 1]); " .
                "Si de verdad necesitás ver todas las sucursales a la vez, usá " .
                "->withoutGlobalScope(\App\Models\Scopes\SucursalScope::class) explícitamente."
            );
        }

        $builder->where($model->getTable() . '.sucursal_id', session('sucursal_id'));
    }
}