# Quickstart: Reconstrucción de Vistas según la Guía de Referencia Bootstrap

**Feature**: `012-reconstruccion-vistas-guia` | **Date**: 2026-08-21

Guía de validación end-to-end. Ver `research.md` para las decisiones técnicas y `contracts/mapeo-componentes-guia.md` para el mapeo componente-por-componente.

## Prerrequisitos

- Proyecto con `specs/001` a `specs/011` implementadas y su suite de 193 pruebas pasando (línea base).

## Escenario 1 — Componentes de contrato (US1)

1. Crear un contrato sin documentos y visitar su detalle.
2. **Resultado esperado**: se ve un área de carga con borde punteado, las dos opciones "Seleccionar PDF del Contrato"/"Subir Fotos de Páginas" y los límites de tamaño/cantidad.
3. Crear un contrato que se solape en fechas con uno existente.
4. **Resultado esperado**: aparece un modal con dos bloques de alerta claramente separados — uno con los datos del contrato existente (fechas, inquilino, monto reales) y otro con los datos del contrato que se intentó registrar.
5. Con 3+ contratos históricos en una locación, visitar su historial.
6. **Resultado esperado**: se presenta como línea de tiempo, con indicador de fecha y badge de estado por contrato.
7. Visitar la sección de costos de referencia de un contrato y completar 2 de los 4 campos.
8. **Resultado esperado**: los campos están en grid de 2 columnas con prefijo "S/"; el campo "Total de Referencia" (solo lectura) se actualiza en vivo con la suma, sin recargar la página.
9. Ejecutar `php artisan test` completo — 193/193 deben seguir pasando.

## Escenario 2 — Representantes y recibos (US2)

1. Agregar dos representantes a un contrato.
2. **Resultado esperado**: cada uno en su propia tarjeta, dos por fila en pantallas medianas o más grandes.
3. Buscar un representante existente por DNI desde el formulario de agregar.
4. **Resultado esperado**: la búsqueda ocurre dentro de un modal dedicado, con resultados seleccionables.
5. Visitar el detalle de un recibo pendiente.
6. **Resultado esperado**: las 3 opciones de estado (Pendiente/Pagado/Anulado) se ven simultáneamente en un solo control, con la opción vigente resaltada; seleccionar otra dispara el cambio de estado ya validado.
7. Ejecutar `php artisan test` completo — 193/193 deben seguir pasando.

## Escenario 3 — Impresión y consistencia (US3)

1. Abrir el comprobante de un recibo y solicitar su impresión (vista previa de impresión del navegador).
2. **Resultado esperado**: el documento se adapta a un formato limpio, sin navegación ni controles de interacción visibles.
3. Recorrer las vistas restantes (locaciones, lecturas de medidor, configuración) y confirmar que siguen usando los mismos componentes ya estandarizados (cards, badges, input-groups, breadcrumbs), sin inconsistencias nuevas.
4. Ejecutar `php artisan test` completo — 193/193 deben seguir pasando.

## Validación automatizada (referencia)

```bash
php artisan test
```

**Cobertura esperada** (Principio IV): la suite completa existente actúa como gate de no-regresión para los 6 componentes reconstruidos; ninguna aserción de negocio debería requerir cambios.
