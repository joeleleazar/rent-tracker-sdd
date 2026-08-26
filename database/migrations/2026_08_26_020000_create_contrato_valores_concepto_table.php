<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * specs/024: valor de referencia de un concepto de gasto fijo para un
 * contrato específico — reemplaza `costo_agua`/`costo_luz`/`costo_pasadizo`/
 * `costo_seguridad` de `contratos`. Nunca contiene una fila para "Renta" ni
 * "Luz" (ver research.md Decisión 2 y 5) — validado en la capa de
 * aplicación, no aquí.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrato_valores_concepto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('contratos')->cascadeOnDelete();
            $table->foreignId('concepto_gasto_fijo_id')->constrained('conceptos_gasto_fijo')->restrictOnDelete();
            $table->decimal('valor', 12, 2);
            $table->timestamps();

            $table->unique(['contrato_id', 'concepto_gasto_fijo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrato_valores_concepto');
    }
};
