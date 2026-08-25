# Quickstart: Validar Optimización de Esquema y Consultas PostgreSQL

**Feature**: `018-optimizacion-esquema-postgresql` | **Date**: 2026-08-25

## Prerrequisitos

- PostgreSQL 15+ corriendo con la base `rent_tracker_dev` (ver `.env`).
- Migraciones de este feature aplicadas: `php artisan migrate`.
- Suite de datos de prueba disponible (`php artisan db:seed` si aplica, o factories de Pest).

## 1. Verificar que la suite existente sigue en verde (SC-004)

```bash
php artisan test
```

Ninguna aserción de resultado esperado en `tests/Feature/RegistroMasivoLecturasControllerTest.php`, `tests/Feature/InquilinoControllerTest.php`, `tests/Feature/ConfiguracionGeneralControllerTest.php` ni en los tests de `Contrato`/`Recibo`/`LecturaMedidor` debe requerir cambios más allá de fixtures de zona horaria.

## 2. Verificar los índices de llave foránea nuevos (FR-004)

```bash
php artisan tinker --execute="dump(DB::select(\"SELECT indexname FROM pg_indexes WHERE tablename IN ('documentos_contrato','recibos','contrato_inquilino','borradores_lectura_medidor','locaciones') ORDER BY tablename\"));"
```

Confirmar que aparece un índice cubriendo `contrato_id` (`documentos_contrato`, `recibos`), `lectura_medidor_id` (`recibos`), `inquilino_id` (`contrato_inquilino`), `locacion_id` (`borradores_lectura_medidor`) y `locacion_padre_id` (`locaciones`).

## 3. Verificar el fix de N+1 en registro masivo (FR-001, SC-001, contrato asociado)

1. Sembrar ≥50 locaciones alquilables sin lectura del periodo actual.
2. Habilitar el query log (`DB::enableQueryLog()`) alrededor de una llamada a `store()` con las 50 filas completas.
3. Confirmar que `count(DB::getQueryLog())` no crece proporcionalmente a 50 (debe acercarse a `3 + N_filas_válidas`, no a `~4×50`).
4. Repetir con un lote de 5 locaciones y comparar el tiempo de respuesta contra el de 50 — debe cumplir SC-001 (≤120% de diferencia).

## 4. Verificar la búsqueda de inquilinos (FR-008, SC-002)

```bash
php artisan tinker --execute="dump(DB::select(\"EXPLAIN SELECT * FROM inquilinos WHERE dni ILIKE '%23%' OR apellidos ILIKE '%rez%'\"));"
```

El plan DEBE mostrar `Bitmap Index Scan` sobre el índice GIN de `pg_trgm`, no `Seq Scan`, una vez la tabla tenga volumen suficiente para que el planificador prefiera el índice.

## 5. Verificar la migración de zona horaria (FR-005, FR-006, SC-003)

1. Antes de migrar: registrar el valor exacto (`toIso8601String()`) de un timestamp existente conocido (ej. `Contrato::first()->created_at`).
2. Aplicar la migración.
3. Confirmar que el mismo registro, leído de nuevo, produce el mismo `toIso8601String()` — el instante no cambió.
4. Confirmar en `\d contratos` (psql) o `information_schema.columns` que el tipo de columna ahora es `timestamp with time zone`.

## 6. Verificar `configuracion_general` clave-valor (FR-007, SC-005, SC-006, contrato asociado)

1. Antes de migrar: registrar `ConfiguracionGeneral::actual()->tarifa_luz_por_unidad` y los otros 3 valores.
2. Aplicar la migración de datos.
3. Confirmar que `ConfiguracionGeneral::actual()->tarifa_luz_por_unidad` (y los otros 3) devuelven los mismos valores.
4. Confirmar que `ConfiguracionGeneral::actual()->update(['dias_anticipacion_alerta_pago' => 10])` persiste y se refleja en una nueva llamada a `actual()`.
5. Insertar manualmente una fila `('nueva_clave', '"valor"')` en la tabla y confirmar que no fue necesaria ninguna migración de esquema para hacerlo (SC-006).

## 7. Verificar el `CHECK` de `periodo` (FR-007b, FR-010)

```bash
php artisan tinker --execute="DB::table('lecturas_medidor')->insert(['locacion_id' => 1, 'periodo' => '2026-08-15', 'lectura_actual' => 1, 'fecha_registro' => now(), 'created_at' => now(), 'updated_at' => now()]);"
```

Debe fallar con una violación del `CHECK` (día distinto de 1), confirmando que la restricción está activa.
