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
        Schema::create('borradores_recibo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->date('periodo');
            $table->foreignId('locacion_id')->constrained('locaciones')->cascadeOnDelete();
            $table->boolean('incluye_alquiler')->default(false);
            $table->decimal('monto_renta', 12, 2)->nullable();
            $table->date('fecha_emision')->nullable();
            $table->jsonb('conceptos')->default('{}');
            $table->timestamps();

            $table->unique(['usuario_id', 'periodo', 'locacion_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borradores_recibo');
    }
};
