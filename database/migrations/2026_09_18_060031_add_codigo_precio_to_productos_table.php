<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Corre contra la conexión 'catalogo' (Railway), no contra la BD por defecto
    protected $connection = 'catalogo';

    public function up(): void
    {
        Schema::connection('catalogo')->table('productos', function (Blueprint $table) {
            $table->string('codigo', 50)->nullable()->unique()->after('id');
            $table->decimal('precio', 10, 2)->nullable()->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::connection('catalogo')->table('productos', function (Blueprint $table) {
            $table->dropColumn(['codigo', 'precio']);
        });
    }
};