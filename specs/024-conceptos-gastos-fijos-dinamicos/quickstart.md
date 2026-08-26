# Quickstart: Catálogo Dinámico de Conceptos de Gastos Fijos, Periodo Ágil y Totales por Locación

Escenarios de validación manual, a correr tras implementar. Usar el binario de PHP de Herd:
`C:\Users\joel5\.config\herd\bin\php.bat`.

## Escenario 1 — Migración de datos sin pérdida (SC-002)

1. Antes de migrar, registrar (script de solo lectura) el `total()` de cada recibo existente y los 4 valores
   de costo de cada contrato existente.
2. Correr las migraciones de esta feature (`php artisan migrate`).
3. Repetir el cálculo de `total()` por recibo y comparar contra lo registrado en el paso 1 — deben coincidir
   exactamente. Verificar también que cada contrato tiene sus valores de Agua/Pasadizo/Seguridad accesibles
   vía `valorDeConcepto()` con el mismo monto que tenía antes en `costo_agua`/`costo_pasadizo`/
   `costo_seguridad`.

**Verificación ya realizada (2026-08-26, durante la implementación, contra `rent_tracker_dev` real)**: no es
posible escribir esto como un test Pest estándar — `RefreshDatabase` corre todas las migraciones (incluidas
las de backfill) sobre una base de datos siempre vacía, así que el backfill nunca tiene datos legados reales
que migrar dentro de un test. Se verificó en cambio con un script de solo lectura corrido después de
`php artisan migrate` sobre los datos reales ya existentes (4 contratos, 3 recibos previos a esta feature):

```
ValoresConceptoContrato: contrato_id=1 → Agua=40.00, Luz de Pasadizo=12.00, Seguridad=20.00
                          contrato_id=2 → Agua=45.00, Luz de Pasadizo=12.00, Seguridad=20.00
ReciboConceptos:         recibo_id=1 → Luz de Pasadizo=12.00, Seguridad=20.00
                          recibo_id=2 → Agua=45.00, Luz=70.00, Luz de Pasadizo=12.00, Seguridad=20.00
```

Coincide exactamente con los valores que esos mismos contratos/recibos tenían en `costo_agua`/
`costo_pasadizo`/`costo_seguridad` y `monto_agua`/`monto_luz`/`monto_pasadizo`/`monto_seguridad`/
`incluye_*` antes de esta feature — sin pérdida de información (SC-002 cumplido).

## Escenario 2 — Agregar un concepto nuevo sin cambio de código (US1, SC-001)

1. Ir a "Conceptos de Gasto Fijo" → crear "Internet".
2. Configurar en un contrato existente un valor de referencia para "Internet".
3. Abrir el flujo de emisión de recibo (individual o masivo) de esa locación y verificar que "Internet"
   aparece disponible con ese monto sugerido.

## Escenario 3 — Proteger "Renta" (US1)

1. Intentar desactivar o eliminar el concepto "Renta" desde la pantalla de conceptos.
2. Verificar que el sistema lo impide con un mensaje explícito, sin cambiar su estado.

## Escenario 4 — "Luz" sigue viniendo de la lectura, no de un valor configurado (US2)

1. Configurar (si el formulario lo permitiera, lo cual no debería) o verificar que no existe forma de
   configurar un valor de referencia para "Luz" en un contrato.
2. Con una lectura de medidor registrada para el periodo, abrir el flujo de emisión de recibo y verificar que
   el monto sugerido de "Luz" coincide con el `total` de esa lectura, no con ningún valor manual.

## Escenario 5 — Periodo ágil sin recarga completa (US3, SC-003)

1. Abrir `/recibos/registro-masivo`, hacer clic en «Siguiente» 3 veces seguidas y luego en «Anterior» 3
   veces — verificar en la pestaña de Red del navegador que cada clic dispara solo una petición parcial
   (`hx-get`), nunca una navegación completa de página.
2. Elegir un mes directamente en el selector y verificar que la tabla se actualiza sin un botón adicional.
3. Repetir en `/lecturas/registro-masivo`.

## Escenario 6 — Total y cantidad de recibos por locación (US4)

1. Con una locación que tiene 2 recibos en el periodo visible (uno cubriendo renta, otro cubriendo el
   resto) y otra locación sin ningún recibo, abrir `/recibos/registro-masivo` y verificar: la primera
   muestra "2 recibos" y el total exacto de ambos sumados; la segunda muestra "0 recibos" y S/ 0.00.
2. Anular uno de los dos recibos de la primera locación (`recibos.estado.update`) y recargar la pantalla —
   verificar que la cantidad baja a "1 recibo" y el total baja en el monto del recibo anulado.

**Verificación manual ya realizada (2026-08-26, navegador real contra `rent_tracker_dev`)**: se recorrieron
los Escenarios 2 a 6 en vivo con Herd (`php artisan serve`) y `claude-in-chrome`, con resultado exitoso en
todos:

- **Escenario 2**: se creó el concepto "Internet" desde `/conceptos-gasto-fijo/crear`, se le configuró
  S/ 50.00 en el Contrato #1 (Local 101) — el campo "Costo de Internet" apareció automáticamente en el
  formulario de costos sin cambio de código, y el "Total de Referencia" se recalculó en vivo (72.00 →
  122.00). Al abrir "Generar Recibo" para Local 101 en `/recibos/registro-masivo`, el modal mostró
  "Incluir Internet" premarcado con S/ 50.00 sugerido.
- **Escenario 3**: `/conceptos-gasto-fijo/1/editar` (Renta) muestra el aviso "Este es un concepto
  protegido..." sin checkbox de activo ni botón Eliminar; el listado no ofrece "Eliminar" para Renta ni
  Luz, solo "Editar" (limitado a nombre/orden).
- **Escenario 4**: el recibo #1 (Local 101, agosto 2026) muestra "Monto de Luz: S/ 63.75", que coincide
  con el consumo de la última lectura de medidor registrada (75 kWh × S/ 0.85), no con un valor manual.
- **Escenario 5**: en `/recibos/registro-masivo`, cada clic en «Siguiente»/«Anterior» generó exactamente
  una petición `GET .../registro-masivo?periodo=YYYY-MM` (confirmado vía inspección de red), sin
  navegación completa de página (la URL de la pestaña no cambió). El selector de mes nativo también
  disparó la actualización automáticamente al cambiar, sin botón adicional. Se repitió con el mismo
  resultado en `/lecturas/registro-masivo`.
- **Escenario 6**: Local 101 mostró "1 recibo · S/ 935.75" y Local 102 "1 recibo · S/ 1,097.00"; las
  locaciones sin recibos (103, 201, 202) mostraron "0 recibos · S/ 0.00". Al anular el recibo de Local 101
  (`recibos/1/estado`), el conteo bajó a "0 recibos · S/ 0.00" tras recargar `/recibos/registro-masivo`.

**Nota adicional (fuera del alcance de esta feature)**: se observó que un recibo anulado sigue contando
como "cubierto" para efectos de disponibilidad de conceptos — el modal "Generar Recibo" no vuelve a
ofrecer Renta/Agua/Luz/etc. tras anular el único recibo del periodo, solo los conceptos nunca antes
incluidos (p. ej. "Internet"). Este es el comportamiento heredado de `ServicioGeneracionReciboPeriodo`
desde specs/018/023 (no filtra por `estado` al calcular conceptos cubiertos) y no fue parte del alcance de
specs/024, que solo excluye los recibos anulados de los *totales y conteos* (US4). Documentado aquí para
una futura feature si se decide que anular un recibo debería liberar sus conceptos para reemisión.
