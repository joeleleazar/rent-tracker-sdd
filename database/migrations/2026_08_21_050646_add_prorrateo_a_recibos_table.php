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
        Schema::table('recibos', function (Blueprint $table) {
            $table->unsignedInteger('dias_activos_periodo')->nullable();
            $table->unsignedInteger('dias_totales_periodo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            $table->dropColumn(['dias_activos_periodo', 'dias_totales_periodo']);
        });
    }
};
