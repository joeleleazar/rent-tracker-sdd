# Quickstart: Registro de Garantía Entregada por Contrato

**Feature**: `009-garantia-contrato` | **Date**: 2026-08-20

Guía de validación end-to-end. Ver `data-model.md` y `contracts/rutas-garantia-contrato.md` para el detalle técnico, y `tasks.md` para las tareas de construcción.

## Prerrequisitos

- Migraciones de `specs/001-008` ya ejecutadas.
- Migración de esta feature ejecutada (`contratos` con columnas de garantía).
- Usuario autenticado.

## Escenario 1 — Registro de la garantía entregada (US1)

1. Crear o editar un contrato ingresando monto de garantía "S/ 1500.00", fecha de entrega "2026-08-19" y medio "Efectivo".
2. **Resultado esperado**: el contrato se guarda con esta información y se muestra en su detalle.
3. Consultar el detalle de un contrato sin garantía registrada.
4. **Resultado esperado**: se muestra "Sin garantía registrada" en vez de un campo vacío.

## Escenario 2 — Consulta destacada en el detalle del contrato (US2)

1. Consultar el detalle de un contrato con garantía de "S/ 1500.00" entregada en efectivo el "2026-08-19".
2. **Resultado esperado**: monto, fecha y medio se muestran de forma destacada junto a renta y fechas de vigencia, con tipografía ≥18px y alto contraste.

## Escenario 3 — Resolución de la garantía (US3)

1. Sobre un contrato con garantía de "S/ 1500.00", registrar la resolución con "S/ 1200.00" devueltos, "S/ 300.00" retenidos y motivo "Reparación de puerta dañada".
2. **Resultado esperado**: el sistema guarda el registro, marca la garantía como "Resuelta" y muestra el detalle completo (devuelto, retenido, motivo, fecha de resolución).
3. Repetir con devolución total "S/ 1500.00" sin retención.
4. **Resultado esperado**: se guarda correctamente, sin exigir motivo.
5. Intentar registrar una resolución con monto retenido mayor a cero sin motivo.
6. **Resultado esperado**: el sistema bloquea el guardado y muestra un mensaje explícito de alto contraste.
7. Sobre una garantía ya "Resuelta", presionar "Corregir Resolución de Garantía".
8. **Resultado esperado**: se solicita confirmación explícita de alta visibilidad antes de permitir editar los montos ya registrados.

## Escenario 4 — Suma de montos no coincide con la garantía (Edge Case)

1. Intentar registrar una resolución donde devuelto + retenido no sume exactamente el monto de garantía entregada.
2. **Resultado esperado**: el sistema bloquea el guardado y muestra la diferencia detectada de forma explícita.

## Escenario 5 — Retención total (Edge Case)

1. Registrar una resolución con "S/ 0.00" devueltos y el monto total de la garantía como retenido.
2. **Resultado esperado**: el sistema exige igualmente un motivo de retención antes de guardar.

## Escenario 6 — Contrato sin garantía (Edge Case)

1. Guardar un contrato sin ingresar ningún monto de garantía.
2. **Resultado esperado**: el sistema permite el guardado sin bloquear ni mostrar errores de validación relacionados a la garantía.

## Validación automatizada (referencia)

```bash
php artisan test --filter=Contrato
php artisan test --filter=ServicioResolucionGarantiaContrato
```

**Cobertura esperada** (Principio IV): modelo `Contrato` (garantía opcional, "sin garantía" con monto 0, `tieneGarantia()`), `ServicioResolucionGarantiaContrato` (cuadre exacto de montos, motivo obligatorio con retención, confirmación de re-edición), `ContratoController` (happy path, 422 en discrepancia de montos, 422 sin motivo, 422 sin confirmación al corregir).
