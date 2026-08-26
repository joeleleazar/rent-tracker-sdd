# Research: Completar Total en Importación Histórica y Seeder

**Nota**: research retroactivo — documenta las decisiones ya tomadas al aplicar el fix, no un análisis previo
a implementarlo (ver spec.md, "Nota de Proceso").

## Decisión 1: fórmula del total

**Decision**: `total = round(consumo * tarifa, 2)`, donde `consumo = round(lectura_actual - (lectura_anterior ?? 0), 2)`.

**Rationale**: es exactamente el mismo criterio ya establecido y probado en `LecturaMedidorController::calcularTotal()`
(specs/019) y en `RegistroMasivoLecturasController` — reutilizar el mismo criterio evita introducir una
tercera forma de calcular el mismo valor de negocio en el sistema.

**Alternatives considered**:
- Extraer un método/servicio compartido (`ServicioCalculoTotalLectura`) usado por los 4 puntos de escritura
  (`LecturaMedidorController`, `RegistroMasivoLecturasController`, el comando de importación, el seeder).
  Descartada para esta feature puntual: hubiera ampliado el alcance a un refactor de los dos controladores ya
  probados y estables, fuera de lo que el usuario pidió corregir. Queda como mejora futura razonable si se
  detecta una quinta duplicación.

## Decisión 2: fuente de la tarifa en el comando de importación histórica

**Decision**: `ConfiguracionGeneral::actual()->tarifa_luz_por_unidad`, leída una sola vez antes del bucle de
~1000 filas (no una consulta por fila).

**Rationale**: es la única tarifa disponible en el sistema — no existe (ni esta feature la introduce) una
tabla de tarifas históricas por período. Es la misma limitación ya documentada explícitamente en el backfill
de `total` de specs/019 para las lecturas existentes al momento de esa migración. Leerla una sola vez fuera
del bucle evita ~1000 consultas redundantes a una configuración que no cambia durante la ejecución del comando.

**Alternatives considered**:
- Requerir que el archivo JSON de entrada (`extracted.json`) incluya una tarifa por fila. Descartada: el
  archivo ya fue generado y su formato no incluye ese dato; regenerarlo estaba fuera de alcance y el archivo
  histórico original (planilla 2018-2019) tampoco registraba una tarifa por período de forma utilizable.
- Dejar el total en `0` para las filas importadas por este comando. Descartada: el propio propósito de
  specs/019 es que `total` sea el valor real usado para generar recibos; dejarlo en `0` sería un dato
  incorrecto silencioso, peor que una aproximación documentada con la tarifa vigente.

## Decisión 3: fuente de la tarifa en el seeder de demostración

**Decision**: usar el literal `0.85` — la misma tarifa que el propio `DatabaseSeeder::run()` ya configura al
principio de su ejecución en `ConfiguracionGeneral::create(['tarifa_luz_por_unidad' => 0.85, ...])` (línea ~38).

**Rationale**: el seeder puebla datos de demostración internamente consistentes entre sí; usar la misma
tarifa que el propio seeder define como parte de su configuración general asegura que los totales de ejemplo
sean coherentes con el resto de los datos que genera (por ejemplo, el recibo del Local 101 ya usaba
`monto_luz => 63.75`, consistente con esa misma tarifa).

**Alternatives considered**:
- Leer la tarifa desde `ConfiguracionGeneral::actual()` después de crearla, igual que el comando de
  importación. Técnicamente equivalente (mismo valor resultante), pero se prefirió el literal directo por
  legibilidad dentro de un seeder que ya construye sus datos de ejemplo con valores literales explícitos en
  todo el resto del archivo (montos de renta, costos fijos, etc.) — ninguna otra parte del seeder relee un
  valor recién creado desde base de datos.

## Decisión 4: no fue necesario un backfill de datos

**Decision**: no se creó ninguna migración ni script de corrección de datos existentes.

**Rationale**: se verificó directamente sobre la base de datos de desarrollo (`rent_tracker_dev`, 2026-08-25,
consulta de solo lectura) que: (a) el conteo de lecturas con `total` nulo es 0, (b) el conteo de lecturas con
`total = 0` es 0, y (c) no existe ninguna locación con nombre `%Historico Medidores%`, lo que confirma que el
comando `medidores:importar-historico` nunca se había ejecutado en este entorno. El defecto era puramente de
código (rompería la *próxima* ejecución de cualquiera de los dos procesos), no de datos ya persistidos.

**Alternatives considered**: N/A — no había datos que corregir, así que no hubo alternativas de estrategia de
backfill que evaluar.

## Decisión 5: por qué el defecto no se detectó en specs/019 ni specs/021

**Decision**: se documenta como hallazgo de proceso, sin acción correctiva adicional más allá de este fix.

**Rationale**: ni `ImportarLecturasMedidorHistoricas` ni `DatabaseSeeder` tienen suite de test Pest dedicada
(ver plan.md, Constitution Check, Principio IV) — son utilidades de ejecución puntual, no lógica de dominio
cubierta por los tests de modelo/controlador que exige el Principio IV. La migración `NOT NULL` de specs/019
(`agregar_total_a_lecturas_medidor`, con `->change()` tras backfill) no falla al aplicarse — solo el próximo
`INSERT` sin esa columna falla — por lo que ni siquiera correr la suite completa después de esa migración
revela el problema; hace falta ejecutar específicamente uno de esos dos procesos. Se detectó recién en una
revisión manual de archivos durante la implementación de specs/021 (que sí tocaba ambos archivos, para quitar
`consumo_calculado`), no por una prueba automatizada.
