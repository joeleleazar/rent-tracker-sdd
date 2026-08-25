<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Última etapa de la consolidación (ver research.md §1 de specs/003, revisión
 * 2026-08-23): una vez copiados los datos por la migración anterior, se
 * eliminan las estructuras que quedan obsoletas: la tabla `representantes`,
 * la tabla pivote `contrato_representante`, la columna `contratos.inquilino_id`
 * (reemplazada por `contrato_inquilino.es_principal = true`) y la columna
 * `inquilinos.nombre` (reemplazada por `apellidos` + `nombres`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('contrato_representante');
        Schema::dropIfExists('representantes');

        Schema::table('contratos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inquilino_id');
        });

        // apellidos/nombres/dni/fecha_nacimiento se mantienen nullable a nivel
        // de base de datos (no se agrega un CHECK/NOT NULL retroactivo): los
        // inquilinos ya existentes antes de esta consolidación solo tenían
        // 'nombre' y no hay una forma confiable de derivar sus datos
        // personales completos a partir de ese único campo. La obligatoriedad
        // (FR-002) se exige a nivel de aplicación en `SolicitudGuardarInquilino`
        // para todo inquilino nuevo o editado a partir de esta feature.
        Schema::table('inquilinos', function (Blueprint $table) {
            $table->dropColumn('nombre');
        });
    }

    public function down(): void
    {
        // No reversible: las tablas/columnas eliminadas aquí solo pueden
        // reconstruirse vacías, sin los datos originales (ya migrados por la
        // migración anterior, que tampoco soporta rollback).
        Schema::table('inquilinos', function (Blueprint $table) {
            $table->string('nombre')->nullable();
        });

        Schema::table('contratos', function (Blueprint $table) {
            $table->foreignId('inquilino_id')->nullable()->constrained('inquilinos')->restrictOnDelete();
        });
    }
};
