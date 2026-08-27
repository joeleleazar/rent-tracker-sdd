<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * specs/035: evidencia (imagen o PDF) del comprobante de pago ya firmado, un
 * único archivo por pago (se reemplaza al subir uno nuevo — research.md
 * Decisión 2/3) — columnas directas en `pagos`, sin tabla aparte, a
 * diferencia de `documentos_contrato` (relación 1 a N real).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->string('evidencia_ruta')->nullable();
            $table->string('evidencia_nombre_archivo')->nullable();
            $table->enum('evidencia_tipo', ['pdf', 'imagen'])->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn(['evidencia_ruta', 'evidencia_nombre_archivo', 'evidencia_tipo']);
        });
    }
};
