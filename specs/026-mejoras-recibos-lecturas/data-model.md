# Data Model: Mejoras al Flujo de Recibos y Lecturas

## Entidad nueva

### `BorradorRecibo` (tabla `borradores_recibo`)

Avance no confirmado de la generación de un recibo, análogo a `BorradorLecturaMedidor` (specs/015) pero
para el flujo de recibos. Transitorio: se descarta al confirmarse la emisión del recibo correspondiente.

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `usuario_id` | FK → `users.id`, `cascadeOnDelete()` | |
| `periodo` | `date` | Se guarda como el primer día del mes (`startOfMonth()`), igual que `borradores_lectura_medidor.periodo`. |
| `locacion_id` | FK → `locaciones.id`, `cascadeOnDelete()` | |
| `incluye_alquiler` | `boolean`, default `false` | Espeja el campo real del formulario (Renta es la única excepción de forma fija, ver `recibo-conceptos-dinamico.md` de specs/024). |
| `monto_renta` | `decimal(12,2)` nullable | Solo relevante si `incluye_alquiler` es verdadero. |
| `fecha_emision` | `date` nullable | |
| `conceptos` | `jsonb`, default `'{}'` | Mapa `concepto_gasto_fijo_id (string) => monto (number)`. La presencia de una clave significa "concepto incluido"; ausencia significa "no incluido". Ver Decisión 4 de research.md sobre por qué `jsonb` y no una tabla hija. |
| `created_at`, `updated_at` | timestamps | |

**Restricciones**: `unique(usuario_id, periodo, locacion_id)` — un único borrador vigente por usuario,
locación y periodo; un guardado posterior hace `upsert` sobre la misma fila (mismo patrón que
`BorradorLecturaMedidor::upsert(...)`).

**Relaciones**: `usuario(): BelongsTo(User)`, `locacion(): BelongsTo(Locacion)`.

**Ciclo de vida**:
1. Se crea/actualiza (`upsert`) cada vez que el usuario guarda el borrador — manualmente (botón "Guardar
   Borrador") o automáticamente (`hx-trigger="every 120s"` mientras la página de generación está abierta).
2. Se lee al abrir `GET locaciones.recibos.create` para ese usuario/locación/periodo, para prellenar el
   formulario si existe.
3. Se elimina al confirmarse exitosamente `POST locaciones.recibos.store` para esa misma
   locación/periodo/usuario (igual que `BorradorLecturaMedidor` se elimina al terminar
   `RegistroMasivoLecturasController::store()` sin errores).
4. No tiene ningún otro efecto en el sistema mientras existe — no bloquea ni reserva conceptos; la
   validación de superposición sigue ocurriendo solo al confirmar (Edge Cases de spec.md).

## Entidades existentes — cambios de comportamiento (sin cambio de esquema)

### `Recibo`

- Se agrega `scopeVigente(Builder $query): Builder` (`where('estado', '!=', 'anulado')`), usado por
  `ServicioGeneracionReciboPeriodo` (Decisión 1) y por el conteo de "en uso" de `ConceptoGastoFijo`
  (Decisión 2). No agrega ninguna columna ni migración a la tabla `recibos`.

### `ConceptoGastoFijo`

- Sin cambio de esquema. `ConceptoGastoFijoController::index()`/`destroy()` cambian el cálculo de
  `recibos_en_uso` para excluir recibos anulados (`whereHas('recibo', fn ($q) => $q->vigente())` en vez de
  `reciboConceptos()->count()` sin filtrar), ver Decisión 2.

## Rutas retiradas

Estas rutas y sus vistas/controladores asociados quedan sin ningún llamador tras la Decisión 3 y se
eliminan como parte de esta feature (no se dejan como código muerto):

- `GET recibos.registroMasivo.modal` (`RegistroMasivoRecibosController::modal()`)
- `POST recibos.registroMasivo.store` (`RegistroMasivoRecibosController::store()`)
- Vistas `resources/views/recibos/registro-masivo/partials/modal-recibo.blade.php` y
  `error-modal-recibo.blade.php`
- `SolicitudGuardarReciboRegistroMasivo` (Form Request específico del modal)

## Rutas nuevas

- `POST locaciones.recibos.borrador` (`/locaciones/{locacion}/recibos/borrador`) — guarda/actualiza el
  borrador del usuario autenticado para esa locación y el periodo enviado (Decisión 4).
- `GET recibos.registroMasivo.recibosDelPeriodo` (`/recibos/registro-masivo/{locacion}/recibos`, query
  `periodo`) — redirige o lista según cantidad (Decisión 5).
