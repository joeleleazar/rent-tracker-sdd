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
        Schema::table('contratos', function (Blueprint $table) {
            $table->decimal('monto_garantia', 12, 2)->nullable();
            $table->date('fecha_entrega_garantia')->nullable();
            $table->enum('medio_entrega_garantia', ['efectivo', 'transferencia', 'cheque'])->nullable();
            $table->enum('estado_garantia', ['entregada', 'resuelta'])->nullable();
            $table->decimal('monto_devuelto_garantia', 12, 2)->nullable();
            $table->decimal('monto_retenido_garantia', 12, 2)->nullable();
            $table->text('motivo_retencion_garantia')->nullable();
            $table->timestamp('fecha_resolucion_garantia')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropColumn([
                'monto_garantia',
                'fecha_entrega_garantia',
                'medio_entrega_garantia',
                'estado_garantia',
                'monto_devuelto_garantia',
                'monto_retenido_garantia',
                'motivo_retencion_garantia',
                'fecha_resolucion_garantia',
            ]);
        });
    }
};
