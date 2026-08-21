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
            $table->decimal('costo_agua', 12, 2)->default(0);
            $table->decimal('costo_luz', 12, 2)->default(0);
            $table->decimal('costo_pasadizo', 12, 2)->default(0);
            $table->decimal('costo_seguridad', 12, 2)->default(0);
            $table->timestamp('notificado_30_dias_en')->nullable();
            $table->timestamp('notificado_15_dias_en')->nullable();
            $table->timestamp('notificado_7_dias_en')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropColumn([
                'costo_agua',
                'costo_luz',
                'costo_pasadizo',
                'costo_seguridad',
                'notificado_30_dias_en',
                'notificado_15_dias_en',
                'notificado_7_dias_en',
            ]);
        });
    }
};
