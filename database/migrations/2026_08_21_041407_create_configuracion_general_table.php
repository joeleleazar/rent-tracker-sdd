<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configuracion_general', function (Blueprint $table) {
            $table->id();
            $table->string('correo_notificaciones_vencimiento');
            $table->timestamps();
        });

        // Fila única (patrón singleton, id = 1), ver data-model.md §ConfiguracionGeneral.
        DB::table('configuracion_general')->insert([
            'id' => 1,
            'correo_notificaciones_vencimiento' => config('mail.from.address', 'hello@example.com'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracion_general');
    }
};
