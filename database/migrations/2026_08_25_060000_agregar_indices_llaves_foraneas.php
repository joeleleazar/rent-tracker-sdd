<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PostgreSQL no crea automáticamente un índice sobre las columnas de llave
 * foránea (a diferencia de MySQL/InnoDB) — endurece 6 columnas detectadas sin
 * ningún índice que las cubra como columna líder (specs/018, data-model.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_contrato', function (Blueprint $table) {
            $table->index('contrato_id');
        });

        Schema::table('recibos', function (Blueprint $table) {
            $table->index('contrato_id');
            $table->index('lectura_medidor_id');
        });

        Schema::table('contrato_inquilino', function (Blueprint $table) {
            $table->index('inquilino_id');
        });

        Schema::table('borradores_lectura_medidor', function (Blueprint $table) {
            $table->index('locacion_id');
        });

        Schema::table('locaciones', function (Blueprint $table) {
            $table->index('locacion_padre_id');
        });
    }

    public function down(): void
    {
        Schema::table('documentos_contrato', function (Blueprint $table) {
            $table->dropIndex(['contrato_id']);
        });

        Schema::table('recibos', function (Blueprint $table) {
            $table->dropIndex(['contrato_id']);
            $table->dropIndex(['lectura_medidor_id']);
        });

        Schema::table('contrato_inquilino', function (Blueprint $table) {
            $table->dropIndex(['inquilino_id']);
        });

        Schema::table('borradores_lectura_medidor', function (Blueprint $table) {
            $table->dropIndex(['locacion_id']);
        });

        Schema::table('locaciones', function (Blueprint $table) {
            $table->dropIndex(['locacion_padre_id']);
        });
    }
};
