<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrato_inquilino', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('contratos')->cascadeOnDelete();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->restrictOnDelete();
            $table->boolean('es_principal')->default(false);
            $table->timestamps();

            $table->unique(['contrato_id', 'inquilino_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrato_inquilino');
    }
};
