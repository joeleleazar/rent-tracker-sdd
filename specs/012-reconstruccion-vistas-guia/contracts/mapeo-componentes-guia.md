# Contrato de Interfaz: Mapeo de Componentes a la Guía de Referencia

**Feature**: `012-reconstruccion-vistas-guia` | **Date**: 2026-08-21

Este documento mapea cada componente exigido por la especificación a su fuente en `docs/referencias-diseno-bootstrap/`, el archivo real que se modifica, y qué NO cambia (ruta/controlador/validación).

| Componente | Fuente en la guía | Archivo(s) a modificar | Ruta/Controlador (sin cambios salvo lo indicado) |
|---|---|---|---|
| Dropzone de documentos | `GUIA_COMPONENTES_BOOTSTRAP.md` § Spec 002, Componente 1 | `resources/views/contratos/partials/galeria-documentos.blade.php` | `DocumentoContratoController@store` — sin cambios |
| Modal de solapamiento (2 bloques) | `GUIA_COMPONENTES_BOOTSTRAP.md` § Spec 002, Componente 2 | `resources/views/contratos/create.blade.php`, `edit.blade.php` (o una parcial nueva de modal compartida) | `ContratoController@store`/`@update` — **ampliación aditiva** (ver `research.md` §3): se agrega `->with('contratoEnConflicto', ...)` a la respuesta ya existente, sin cambiar validación ni el mensaje ya verificado por tests |
| Timeline de historial | `GUIA_COMPONENTES_BOOTSTRAP.md` § Spec 002, Componente 3 | `resources/views/contratos/index.blade.php` | `ContratoController@index` — sin cambios (mismos datos ya provistos por `scopeHistorialCronologico`) |
| Grid de costos + total calculado | `GUIA_COMPONENTES_BOOTSTRAP.md` § Spec 004, Componente 1 | `resources/views/contratos/partials/costos-fijos-contrato.blade.php`, nuevo `resources/js/costos-fijos-contrato.js` | `ContratoController@actualizarCostos` (o el método vigente) — sin cambios |
| Tarjetas de representante en grid | `GUIA_COMPONENTES_BOOTSTRAP.md` § Spec 003, Componente 1 | `resources/views/contratos/partials/representantes-contrato.blade.php` | `RepresentanteController`/`ContratoController@agregarRepresentante`/`quitarRepresentante` — sin cambios |
| Selector de estado de recibo (3 opciones) | `GUIA_COMPONENTES_BOOTSTRAP.md` § Spec 007, Componente 1 | `resources/views/locaciones/recibos/show.blade.php` | `ReciboController@actualizarEstado` — sin cambios (mismo endpoint parametrizado por `nuevo_estado`) |
| Reglas de impresión | `GUIA_COMPONENTES_BOOTSTRAP.md` § Spec 007, Componente 2 | `resources/views/locaciones/recibos/comprobante.blade.php` | `ReciboController@comprobante` — sin cambios |

## Reconciliaciones vinculantes que NO se tocan en ningún componente (Principio VI)

- Navegación: sidebar fijo de `specs/010`, no el navbar horizontal de los wireframes de la guía.
- Interactividad de escritura: htmx (`hx-boost`) de `specs/011`, no Alpine.js.
- Paleta de colores: `resources/css/bootstrap.scss`, no la tabla de colores de `RESUMEN_EJECUTIVO_SPECS.md`.

## Verificación de no-regresión

Todos los componentes de esta tabla se verifican contra la suite completa de Pest (193 pruebas) después de cada historia de usuario; en particular, las pruebas que ya cubren `assertSessionHasErrors('solapamiento')` y `assertSessionHasErrors('nuevo_estado')`/transiciones de recibo (specs 002, 007) son el gate específico de que la ampliación aditiva de §3 no rompió nada.
