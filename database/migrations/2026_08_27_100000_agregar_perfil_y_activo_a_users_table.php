<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * specs/040: gestión de usuarios por perfiles.
 *
 * Añade a `users` el perfil de acceso (conjunto cerrado master/administrador,
 * con `CHECK` a nivel de motor vía `enum`) y el estado activo/inactivo de la
 * cuenta. El perfil nace como `administrador` por defecto (mínimo privilegio:
 * una cuenta creada sin indicar perfil nunca queda como master por accidente);
 * las cuentas ya existentes se elevan a `master` en la migración de datos
 * siguiente (FR-016).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('perfil', ['master', 'administrador'])
                ->default('administrador')
                ->after('password');
            $table->boolean('activo')
                ->default(true)
                ->after('perfil');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['perfil', 'activo']);
        });
    }
};
