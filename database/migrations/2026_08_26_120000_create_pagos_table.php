<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * specs/032: un pago individual (total o parcial) registrado contra un
 * recibo. Varios pagos pueden corresponder a un mismo recibo — el avance de
 * pago se deriva sumando esta tabla, nunca de un único campo agregado en
 * `recibos` (research.md Decisión 1). `registrado_por_id` usa
 * `nullOnDelete()` en vez de `restrictOnDelete()` para no repetir el mismo
 * error de diseño ya corregido en `recibo_conceptos` (specs/026) — un
 * registro financiero debe sobrevivir aunque la cuenta que lo registró se
 * elimine (research.md Decisión 2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recibo_id')->constrained('recibos')->cascadeOnDelete();
            $table->decimal('monto', 12, 2);
            $table->date('fecha_pago');
            $table->foreignId('registrado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
