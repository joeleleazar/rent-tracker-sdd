<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * specs/024: catálogo dinámico de conceptos de gasto fijo, reemplazando los 5
 * conceptos hoy codificados como columnas fijas de `contratos`/`recibos`.
 * `clave` es un identificador estable (no editable desde la UI) que distingue
 * "Renta" y "Luz" — los dos únicos conceptos con una fuente de valor especial
 * — de cualquier concepto regular, sin acoplar esa lógica al nombre visible,
 * que sí es editable (research.md Decisión 1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conceptos_gasto_fijo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('clave')->nullable()->unique();
            $table->unsignedInteger('orden');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        $ahora = now();
        DB::table('conceptos_gasto_fijo')->insert([
            ['nombre' => 'Renta', 'clave' => 'renta', 'orden' => 1, 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Agua', 'clave' => null, 'orden' => 2, 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Luz', 'clave' => 'luz', 'orden' => 3, 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Luz de Pasadizo', 'clave' => null, 'orden' => 4, 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Seguridad', 'clave' => null, 'orden' => 5, 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('conceptos_gasto_fijo');
    }
};
