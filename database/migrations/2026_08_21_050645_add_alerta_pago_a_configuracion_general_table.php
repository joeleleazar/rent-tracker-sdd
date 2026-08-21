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
        Schema::table('configuracion_general', function (Blueprint $table) {
            $table->unsignedInteger('dias_anticipacion_alerta_pago')->default(5);
            $table->timestamp('alerta_pago_mes_enviada_en')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_general', function (Blueprint $table) {
            $table->dropColumn(['dias_anticipacion_alerta_pago', 'alerta_pago_mes_enviada_en']);
        });
    }
};
