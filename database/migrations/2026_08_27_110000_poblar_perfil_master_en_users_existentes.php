<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * specs/040 (FR-016): las cuentas que ya existían al incorporar la gestión de
 * usuarios por perfiles son las cuentas fundadoras del sistema y deben
 * conservar el control total. Se elevan todas a `master` y se garantizan
 * activas, de modo que ninguna quede sin perfil y siempre exista al menos un
 * Master activo. A partir de aquí, las cuentas nuevas nacen como
 * `administrador` salvo que un Master elija otro perfil al crearlas.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->update([
            'perfil' => 'master',
            'activo' => true,
        ]);
    }

    public function down(): void
    {
        // Irreversible por diseño: no es posible reconstruir qué cuentas no
        // tenían perfil antes de esta migración. Revertir el esquema
        // (migración anterior) es suficiente para deshacer la funcionalidad.
    }
};
