<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('locacion_id')->constrained('locaciones')->restrictOnDelete();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->decimal('monto_renta', 12, 2);
            // enum() en el driver pgsql se compila como varchar + CHECK constraint,
            // sin depender del tipo ENUM nativo de PostgreSQL (ver data-model.md).
            $table->enum('estado', ['borrador', 'activo', 'vencido', 'rescindido'])->default('borrador');
            $table->timestamps();

            $table->index(['locacion_id', 'fecha_inicio', 'fecha_fin']);
            $table->index(['locacion_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
