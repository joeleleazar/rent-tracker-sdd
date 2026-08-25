<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquilinos', function (Blueprint $table) {
            $table->string('apellidos')->nullable()->after('nombre');
            $table->string('nombres')->nullable()->after('apellidos');
            $table->string('dni')->nullable()->unique()->after('nombres');
            $table->date('fecha_nacimiento')->nullable()->after('dni');
        });
    }

    public function down(): void
    {
        Schema::table('inquilinos', function (Blueprint $table) {
            $table->dropColumn(['apellidos', 'nombres', 'dni', 'fecha_nacimiento']);
        });
    }
};
