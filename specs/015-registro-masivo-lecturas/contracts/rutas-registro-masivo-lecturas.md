# Contrato de Interfaz: Rutas de Registro Masivo de Lecturas

**Feature**: `015-registro-masivo-lecturas` | **Date**: 2026-08-24

Aplicación web Laravel — el "contrato" son las rutas HTTP que la interfaz expone, todas dentro
del middleware `auth` ya usado por el resto de la app (mismo grupo de rutas que
`locaciones.*`/`lecturas.*` en `routes/web.php`).

## `GET /lecturas/registro-masivo` — `lecturas.registroMasivo.index`

**Propósito**: US1 + US2 — pantalla principal del registro masivo.

**Query params**:
- `periodo` (opcional, formato `YYYY-MM`): periodo a mostrar; por defecto el mes actual (FR-007).

**Respuesta (vista)**: árbol de locaciones (reutilizando `ServicioConstruccionArbolLocaciones`),
con para cada locación alquilable:
- Si ya existe `LecturaMedidor` para `locacion_id` + `periodo`: fila en estado "completada"
  (valor ya registrado, enlace a `lecturas.edit`) — FR-005.
- Si no existe: campo `lectura_actual` editable, prellenado con el valor del borrador si existe
  (FR-011) o vacío; lectura del periodo anterior visible como referencia (FR-006) o "sin lectura
  previa".

Las locaciones no alquilables aparecen solo como encabezado organizativo del árbol (sin campo),
igual que en `/locaciones`.

## `POST /lecturas/registro-masivo` — `lecturas.registroMasivo.store`

**Propósito**: US1 — guardado final del lote.

**Request body**:
```text
periodo: string (Y-m-d, primer día del mes)
lecturas: array<locacion_id, {
    lectura_actual: string|null,
    confirmar_consumo_negativo: bool (opcional, por fila)
}>
```

**Comportamiento**:
- Filas con `lectura_actual` vacío se omiten silenciosamente (FR-004) — no se validan ni se
  cuentan como error.
- Cada fila no vacía se procesa de forma independiente (Decisión 3 de `research.md`): se valida,
  se calcula el consumo, y si resulta negativo sin `confirmar_consumo_negativo`, esa fila
  específica queda pendiente de confirmación sin afectar a las demás (FR-008, FR-009).
- Al finalizar, si el lote no tuvo ninguna fila pendiente de confirmación ni error irrecuperable:
  se descarta el borrador de ese `usuario_id` + `periodo` (FR-012) y se redirige con un mensaje
  de éxito indicando cuántas lecturas se registraron.
- Si alguna fila quedó pendiente de confirmación: se vuelve a renderizar la misma pantalla con
  las filas ya guardadas marcadas como completadas, las filas pendientes de confirmación con su
  checkbox de confirmación visible, y el resto de los valores ya escritos preservados (no se
  pierde lo tipeado en otras filas todavía no enviadas con éxito).

## `POST /lecturas/registro-masivo/borrador` — `lecturas.registroMasivo.borrador`

**Propósito**: US3 — autoguardado periódico (invocado por `hx-trigger="every 120s"`, ver
Decisión 4-5 de `research.md`; nunca por una acción explícita del usuario).

**Request body**: mismo arreglo `lecturas[locacion_id][lectura_actual]` que el formulario
principal, recolectado vía `hx-include` — sin `confirmar_consumo_negativo` (el borrador no
aplica esa validación, ver Assumptions de `spec.md`).

**Comportamiento**: `upsert()` de una fila de `BorradorLecturaMedidor` por cada `locacion_id`
con valor no vacío, bajo la clave `(usuario_id, periodo, locacion_id)`. Sin validación de
negocio. Respuesta mínima (sin `mensaje` de sesión ni redirección) — un `hx-swap` dirigido a un
`<span>` de estado con la hora del último autoguardado exitoso; en caso de fallo, no se muestra
ningún error bloqueante (Edge Case de `spec.md`: se reintenta en el siguiente ciclo de 2
minutos).

## `GET /lecturas/registro-masivo/exportar/excel` — `lecturas.registroMasivo.exportarExcel`

**Propósito**: FR-016 — descarga en formato `.xlsx` del contenido de la pantalla para el periodo
seleccionado.

**Query params**: `periodo` (igual que `index`; por defecto el mes actual).

**Comportamiento**: Reutiliza la misma consulta N+1-segura de `index()` (Decisión 8 de
`research.md`) para reunir, por cada locación alquilable, lectura anterior, lectura actual (o
vacío si está pendiente), consumo, total por fila (consumo × tarifa vigente) y el total general.
Responde con un archivo `.xlsx` descargable (`ExportacionRegistroMasivoLecturas`, vía
`maatwebsite/excel`), sin alterar ningún dato ni el borrador.

## `GET /lecturas/registro-masivo/exportar/pdf` — `lecturas.registroMasivo.exportarPdf`

**Propósito**: FR-016 — descarga en formato `.pdf` del mismo contenido, misma fuente de datos que
`exportarExcel`.

**Query params**: `periodo` (igual que `index`).

**Comportamiento**: Renderiza `exportar-pdf.blade.php` (plantilla dedicada, no la vista
interactiva) con los mismos datos que `exportarExcel`, y devuelve el PDF resultante como descarga
(`barryvdh/laravel-dompdf`).

## `GET /lecturas/registro-masivo/lecturas/{lectura}/editar-inline` — `lecturas.registroMasivo.editarInline`

**Propósito**: FR-017 — reemplaza, dentro de la pantalla de registro masivo, la fila de una
`LecturaMedidor` ya registrada (identificada por su ícono no invasivo, FR-005/Decisión 10) por su
modo de edición.

**Comportamiento**: Disparado por `hx-get` desde el ícono; responde con la parcial
`fila-registro-masivo.blade.php` renderizada en modo edición (input prellenado con
`lectura_actual`, botones guardar/cancelar), reemplazando la fila vía `hx-swap="outerHTML"`, sin
persistir nada todavía.

## `PATCH /lecturas/registro-masivo/lecturas/{lectura}` — `lecturas.registroMasivo.actualizarInline`

**Propósito**: FR-017 — guarda la edición en línea disparada por `editarInline`.

**Request body**: `lectura_actual`, `confirmar_consumo_negativo` (opcional) — mismos campos que
`SolicitudGuardarLecturaMedidor`.

**Comportamiento**: Reutiliza exactamente la misma validación y el mismo patrón
`DB::transaction`/`ConsumoNegativoSinConfirmarException` que `LecturaMedidorController@update`
(Decisión 9 de `research.md`). Si tiene éxito, responde con la parcial de la fila ya actualizada
en modo lectura (ícono + valor nuevo), vía `hx-swap="outerHTML"` — sin redirección ni recarga de
la pantalla de registro masivo. Si el consumo resulta negativo sin confirmar, responde con la
misma parcial en modo edición mostrando el checkbox de confirmación, igual que el flujo
individual ya existente.

## `PATCH /lecturas/registro-masivo/tarifa` — `lecturas.registroMasivo.actualizarTarifa`

**Propósito**: FR-015 — persiste el nuevo valor de tarifa por kWh cuando el usuario lo edita desde
la pantalla de registro masivo.

**Request body**: `tarifa_luz_por_unidad` (numérico, `>= 0`).

**Comportamiento**: Disparado por `hx-patch` en el evento `change` del input de tarifa (Decisión 7
de `research.md`), actualiza `ConfiguracionGeneral::actual()`. Respuesta mínima (`hx-swap="none"`)
— el recálculo visual de los totales ya ocurrió del lado del cliente antes de este request; esta
llamada solo persiste el valor para que quede disponible como valor por defecto en periodos
futuros y en la generación de recibos.
