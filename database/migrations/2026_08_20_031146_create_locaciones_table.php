<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prerrequisito técnico de specs/001-jerarquia-locaciones: modelo mínimo
     * necesario para satisfacer la clave foránea de Contrato (specs/002).
     */
    public function up(): void
    {
        Schema::create('locaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->decimal('tamano', 10, 2);
            $table->text('ubicacion_fisica');
            $table->text('descripcion');
            $table->foreignId('locacion_padre_id')
                ->nullable()
                ->constrained('locaciones')
                ->nullOnDelete();
            $table->boolean('es_alquilable')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locaciones');
    }
};
