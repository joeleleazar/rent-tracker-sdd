# Contrato: Cálculo de `total` al crear una `LecturaMedidor`

Este proyecto no expone una API HTTP para esta feature — el "contrato" es el comportamiento esperado de los
dos procesos que crean lecturas de medidor fuera del flujo web (comando Artisan y seeder).

## Contrato 1 — `ImportarLecturasMedidorHistoricas::handle()`

**Entrada**: un archivo JSON (`storage/app/private/import_medidores/extracted.json`) con registros
`{codigo, periodo, lectura_actual, ...}`.

**Comportamiento garantizado**:
- Antes de procesar filas, se lee `ConfiguracionGeneral::actual()->tarifa_luz_por_unidad` una única vez.
- Por cada registro con `lectura_actual` numérica: `consumo = round(lectura_actual - (lectura_anterior ?? 0), 2)`;
  `total = round(consumo * tarifa, 2)`.
- `LecturaMedidor::create()` siempre incluye la clave `'total'` con ese valor — nunca omitida.
- Toda la operación permanece dentro de la transacción `DB::transaction()` ya existente: si cualquier fila
  falla, ninguna lectura de la ejecución queda persistida.

**Postcondición verificable**: tras una ejecución exitosa, `LecturaMedidor::whereNull('total')->count() === 0`
para toda lectura creada por esta ejecución.

## Contrato 2 — `DatabaseSeeder::run()` (bloque de lecturas del Local 101)

**Entrada**: ninguna (datos de demostración fijos, literales en el código).

**Comportamiento garantizado**:
- La tarifa usada es la misma constante (`0.85`) que el propio seeder configura en `ConfiguracionGeneral` al
  principio de su ejecución.
- Por cada una de las 3 lecturas de ejemplo: `consumo = round(lectura - (lecturaAnterior ?? 0), 2)`;
  `total = round(consumo * 0.85, 2)`.
- `LecturaMedidor::create()` siempre incluye la clave `'total'` con ese valor.

**Postcondición verificable**: tras `php artisan db:seed`, las 3 lecturas del Local 101 existen con `total`
no nulo, y cada una coincide con `consumo × 0.85` redondeado a 2 decimales.
