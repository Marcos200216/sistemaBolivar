<?php

namespace App\Providers;

use App\Models\Sucursal;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $view->with(
                'sucursalActual',
                session()->has('sucursal_id') ? Sucursal::find(session('sucursal_id')) : null
            );
        });
    }
}