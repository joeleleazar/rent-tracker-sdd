# Contrato de Interfaz: Rutas web de Lecturas de Medidor y Recibo por Periodo

**Feature**: `005-lecturas-medidor-recibo-periodo` | **Date**: 2026-08-20

Aplicación monolítica Laravel con vistas Blade server-rendered, consistente con `specs/001-004`. Rutas protegidas por `middleware(['auth'])`. Todas las rutas mutantes exigen CSRF.

**Nota de reconciliación** (ver `research.md` §1): las rutas de recibo de `specs/004` (`/contratos/{contrato}/recibos*`) se sustituyen por las rutas locación-céntricas de esta tabla; `GET /recibos/{recibo}` (detalle) se mantiene sin cambios.

## Lecturas de medidor

| Método | Ruta | Controlador@acción | Descripción | Respuesta esperada |
|---|---|---|---|---|
| GET | `/locaciones/{locacion}/lecturas` | `LecturaMedidorController@index` | Historial de lecturas y recibos de la locación (US3) | 200 |
| GET | `/locaciones/{locacion}/lecturas/crear` | `LecturaMedidorController@create` | Formulario de nueva lectura para un periodo; si ya existe lectura de ese periodo, redirige a edición (FR-003) | 200 |
| POST | `/locaciones/{locacion}/lecturas` | `LecturaMedidorController@store` | Guarda la lectura del periodo, calcula consumo | 302 en éxito; 422 si el periodo ya existe sin confirmar edición, o si falta confirmación ante consumo negativo |
| GET | `/lecturas/{lectura}/editar` | `LecturaMedidorController@edit` | Formulario de edición de una lectura existente | 200 |
| PUT | `/lecturas/{lectura}` | `LecturaMedidorController@update` | Actualiza la lectura (con advertencia si el recibo del periodo ya fue emitido, Edge Case) | 302 en éxito; 422 en validación |

## Recibos por periodo (locación-céntrico)

| Método | Ruta | Controlador@acción | Descripción | Respuesta esperada |
|---|---|---|---|---|
| GET | `/locaciones/{locacion}/recibos/crear` | `ReciboController@create` | Formulario de recibo para `?periodo=YYYY-MM`, con casillas de inclusión por concepto y montos precargados (US2) | 200; redirect con error si no hay contrato activo en el periodo (FR-008), permitiendo igualmente registrar la lectura por separado |
| POST | `/locaciones/{locacion}/recibos` | `ReciboController@store` | Emite el recibo del periodo con los conceptos seleccionados | 302 en éxito; 422 si ya existe recibo para `(locacion_id, periodo)` (FR-009), ofreciendo edición |
| GET | `/recibos/{recibo}` | `ReciboController@show` | Detalle del recibo (de `specs/004`, sin cambios) | 200 |
| GET | `/recibos/{recibo}/editar` | `ReciboController@edit` | Formulario de edición de un recibo ya emitido | 200 |
| PUT | `/recibos/{recibo}` | `ReciboController@update` | Actualiza conceptos/montos de un recibo existente | 302 en éxito; 422 en validación |

**Resolución del contrato activo (FR-008)**: `ReciboController@create`/`@store` MUST invocar `Locacion::contratoActivoEnPeriodo($periodo)`; si retorna `null`, la respuesta bloquea la generación del recibo con un mensaje explícito, sin impedir que `LecturaMedidorController@store` se ejecute de forma independiente para el mismo periodo.

**No duplicación (FR-009)**: `ReciboController@store` MUST verificar la existencia previa de un `Recibo` con el mismo `(locacion_id, periodo)` antes de crear uno nuevo; si existe, la respuesta 422 MUST ofrecer el enlace de edición (`/recibos/{recibo}/editar`) en vez de solo rechazar.

## Form Requests (validación de entrada)

- `SolicitudGuardarLecturaMedidor` (`store`/`update` de `LecturaMedidorController`): valida `periodo` (fecha válida, normalizada al día 1 del mes), `lectura` (`numeric`, `required`, `min:0`), `confirmar_consumo_negativo` (`boolean`, requerido solo si el cálculo resulta negativo, ver `research.md` §4).
- `SolicitudGuardarRecibo` (extendida de `specs/004`): agrega `incluye_alquiler`/`incluye_luz`/`incluye_agua`/`incluye_seguridad`/`incluye_pasadizo` (`boolean`, cada uno por defecto `true`), y valida que `periodo` no tenga ya un recibo asociado a la misma locación salvo que se esté editando ese mismo registro.

## Errores y mensajes

- La advertencia de "consumo negativo" MUST mostrarse en alto contraste antes de permitir el guardado definitivo (Edge Case).
- El mensaje de "ya existe un recibo para este periodo" y el de "no existe contrato activo en este periodo" MUST ser explícitos y distintos entre sí, con tipografía ≥18px (Principio III).
- Los conceptos excluidos (`incluye_* = false`) MUST omitirse por completo del detalle y del total del recibo (FR-005), no solo mostrarse en cero.
