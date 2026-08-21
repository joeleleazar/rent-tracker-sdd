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
            $table->decimal('tarifa_luz_por_unidad', 12, 4)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_general', function (Blueprint $table) {
            $table->dropColumn('tarifa_luz_por_unidad');
        });
    }
};
