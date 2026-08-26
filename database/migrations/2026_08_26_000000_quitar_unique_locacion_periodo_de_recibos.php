<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * specs/023: una misma locación y periodo puede tener más de un recibo, siempre que
 * el conjunto de conceptos (incluye_*) cubiertos entre todos ellos no se superponga
 * — esa regla ya no es expresable como una constraint UNIQUE(locacion_id, periodo)
 * (specs/004 FR-009), así que se retira y se reemplaza por un índice no único (para
 * conservar el rendimiento de las consultas por locación+periodo que este mismo
 * cambio agrega) más la validación de aplicación en ServicioGeneracionReciboPeriodo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            $table->dropUnique(['locacion_id', 'periodo']);
            $table->index(['locacion_id', 'periodo']);
        });
    }

    public function down(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            $table->dropIndex(['locacion_id', 'periodo']);
            $table->unique(['locacion_id', 'periodo']);
        });
    }
};
