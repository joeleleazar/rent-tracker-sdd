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
        Schema::create('lecturas_medidor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('locacion_id')->constrained('locaciones')->restrictOnDelete();
            $table->date('periodo');
            $table->decimal('lectura', 12, 2);
            $table->decimal('consumo_calculado', 12, 2)->nullable();
            $table->timestamp('fecha_registro')->useCurrent();
            $table->timestamps();

            $table->unique(['locacion_id', 'periodo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lecturas_medidor');
    }
};
