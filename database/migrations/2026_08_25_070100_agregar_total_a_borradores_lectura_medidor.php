<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * specs/019 research.md Decisión 4: el borrador de registro masivo protege
 * también un total editado a mano antes de guardar, igual que ya hace con
 * lectura_actual — sin esta columna, el valor que el usuario escribió en el
 * campo de total (ya incluido en cada petición de autoguardado por
 * hx-include) se recibiría y se descartaría en silencio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borradores_lectura_medidor', function (Blueprint $table) {
            $table->decimal('total', 12, 2)->nullable()->after('lectura_actual');
        });
    }

    public function down(): void
    {
        Schema::table('borradores_lectura_medidor', function (Blueprint $table) {
            $table->dropColumn('total');
        });
    }
};
