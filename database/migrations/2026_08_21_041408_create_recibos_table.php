<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recibos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('contratos')->restrictOnDelete();
            // locacion_id se agrega ya en esta migración (no en una ALTER posterior de specs/005) porque
            // la reconciliación de specs/005-lecturas-medidor-recibo-periodo/research.md §1 exige que las
            // rutas de recibo sean locación-céntricas desde su implementación en esta misma spec 004 — ver
            // tasks.md de esta feature, sección Notes.
            $table->foreignId('locacion_id')->constrained('locaciones')->restrictOnDelete();
            $table->decimal('monto_renta', 12, 2);
            $table->decimal('monto_agua', 12, 2)->default(0);
            $table->decimal('monto_luz', 12, 2)->default(0);
            $table->decimal('monto_pasadizo', 12, 2)->default(0);
            $table->decimal('monto_seguridad', 12, 2)->default(0);
            $table->date('periodo');
            $table->date('fecha_emision');
            $table->timestamps();

            $table->index(['locacion_id', 'periodo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recibos');
    }
};
