<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FR-008: el planificador de PostgreSQL usa automáticamente un índice GIN de
 * trigramas para predicados ILIKE '%...%' sin necesitar reescribir la consulta
 * de InquilinoController::buscar() — research.md R5.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX inquilinos_dni_trgm_idx ON inquilinos USING gin (dni gin_trgm_ops)');
        DB::statement('CREATE INDEX inquilinos_apellidos_trgm_idx ON inquilinos USING gin (apellidos gin_trgm_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS inquilinos_dni_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS inquilinos_apellidos_trgm_idx');
    }
};
