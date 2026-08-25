# Research: Registro Masivo de Lecturas de Luz

## Contexto

El Technical Context del plan no dejó ningún `NEEDS CLARIFICATION` — las 3 preguntas de alcance
(persistencia del borrador, restauración, fin del borrador) ya se resolvieron en la sesión de
`/speckit-clarify`. Esta investigación documenta cómo implementar esas decisiones reutilizando
al máximo el código y los patrones ya existentes en el proyecto.

## Decisión 1: Reutilizar `ServicioConstruccionArbolLocaciones` para agrupar las filas

- **Decision**: La pantalla de registro masivo llama al mismo
  `ServicioConstruccionArbolLocaciones::construir()` que ya usa `/locaciones`, y renderiza una
  parcial recursiva análoga a `fila-arbol-locacion.blade.php` — pero mostrando un campo de
  lectura (o el estado "ya registrada") en vez de botones de acción para las locaciones
  alquilables, y solo la etiqueta de nombre (sin campo) para las no alquilables, que actúan
  como encabezado organizativo del árbol.
- **Rationale**: FR-002 exige que la agrupación sea "consistente con la jerarquía ya usada en la
  vista general de locaciones" — reutilizar el servicio ya probado evita reimplementar la lógica
  de agrupación y garantiza que ambas vistas nunca se desincronicen en su forma de agrupar.
- **Alternatives considered**: Una consulta plana (`Locacion::alquilables()->get()`) ordenada por
  nombre — descartada porque no satisface FR-002 (no hay jerarquía visible) y contradice el
  patrón ya establecido en `/locaciones`.

## Decisión 2: Reutilizar `ServicioCalculoConsumoMedidor` por fila, sin N+1

- **Decision**: Para poblar la "lectura del periodo anterior" de cada fila (FR-006), se
  construye un mapa `locacion_id => lectura_actual` con **una sola consulta** —
  `LecturaMedidor::whereIn('locacion_id', $idsAlquilables)->where('periodo', '<', $periodo)`
  agrupado y quedándose con el máximo periodo por locación — en vez de invocar
  `ServicioCalculoConsumoMedidor::sugerirLecturaAnterior()` (que ya hace una consulta por
  locación) dentro de un bucle. Se reutiliza `calcularConsumo()` tal cual, que es una operación
  en memoria sin acceso a base de datos.
- **Rationale**: `sugerirLecturaAnterior()` está probado y es correcto para el flujo individual
  (una sola locación), pero llamarlo dentro de un `foreach` de N locaciones produciría N
  consultas — un antipatrón N+1 que el flujo individual nunca tuvo que evitar porque solo
  atendía una locación a la vez. La pantalla masiva sí necesita evitarlo (ver Performance Goals
  del plan).
- **Alternatives considered**: Llamar `sugerirLecturaAnterior()` en un bucle — descartada por el
  problema de N+1 ya explicado; aceptable solo si el volumen de locaciones fuera trivial, pero no
  hay ninguna razón para no evitarlo desde el principio.

## Decisión 3: Persistencia fila por fila, no un único lote atómico (FR-009)

- **Decision**: El guardado masivo NO envuelve todas las filas en un único `DB::transaction`.
  Cada fila con un valor completado se procesa de forma independiente, reutilizando el mismo
  patrón de `LecturaMedidorController@store` (un `DB::transaction` por fila, que verifica
  duplicado y consumo negativo, y lanza `LecturaMedidorDuplicadaException`/
  `ConsumoNegativoSinConfirmarException` ya existentes). Las filas que se guardan con éxito
  quedan persistidas aunque otra fila del mismo envío falle.
- **Rationale**: Es la única forma de cumplir FR-009 ("las filas válidas quedan registradas... sin
  descartar lo demás"). Una transacción única para todo el lote violaría ese requisito porque
  cualquier error en una fila revertiría a todas. Esto no contradice el Principio V de la
  constitución (transacciones atómicas): la unidad atómica correcta aquí es la fila individual
  (una lectura de medidor), no el lote completo — igual que el registro individual ya trata cada
  lectura como su propia unidad transaccional.
- **Alternatives considered**: Transacción única para todo el lote (todo o nada) — descartada por
  violar FR-009 y por ser peor UX (perder 9 filas correctas por 1 error de tipeo en la fila 10).

## Decisión 4: Autoguardado con `hx-trigger="every 120s"`, sin JS de temporizador custom

- **Decision**: El elemento que dispara el autoguardado (FR-010) es un elemento no-formulario
  independiente (ej. `<div id="autoguardado-borrador" hx-post="{{ route('lecturas.registroMasivo.borrador') }}" hx-trigger="every 120s" hx-include="#formulario-registro-masivo" hx-swap="none">`)
  colocado junto al formulario principal, no el propio `<form>`. Usa `hx-include` para recolectar
  los valores actuales de los campos del formulario sin necesidad de que el usuario envíe nada.
- **Rationale**: htmx (`hx-boost` + triggers declarativos) es la única capa de interactividad de
  escritura autorizada por el Principio VI (specs/011) — un `setInterval` de JavaScript custom
  para el autoguardado estaría fuera de esa decisión ya tomada. `hx-trigger="every 120s"` es la
  sintaxis nativa de htmx para polling periódico, sin dependencias nuevas.
- **Alternatives considered**: Alpine.js `x-init` con `setInterval` — descartado, prohibido por
  el Principio VI. `setInterval` en `resources/js/htmx.js` disparando `htmx.trigger()` a mano —
  descartado por ser más código que la sintaxis declarativa ya nativa de htmx para el mismo
  resultado.

## Decisión 5: El autoguardado no debe disparar el tratamiento visual de "Guardando…" del envío manual

- **Decision**: `resources/js/htmx.js` ya deshabilita el botón de envío y lo cambia a
  "Guardando…" en `htmx:beforeRequest`, pero solo cuando `evento.target.tagName === 'FORM'`
  (función `botonEnvioDe`). Como el trigger del autoguardado (Decisión 4) es un `<div>`, no un
  `<form>`, ese listener ya lo ignora sin necesidad de tocar `htmx.js`. El autoguardado exitoso
  actualiza, vía `hx-swap` dirigido a un pequeño `<span>` de estado (ej. "Borrador guardado a las
  HH:MM"), sin alertas ni recarga de página.
- **Rationale**: Confirmado leyendo `resources/js/htmx.js` — el listener ya filtra por
  `tagName === 'FORM'`. Verificar esto por adelantado evita un bug de UX (un mensaje intrusivo de
  "Guardando…" apareciendo cada 2 minutos sin que el usuario haya hecho nada) que solo se
  descubriría manualmente en pruebas de navegador si no se documentaba aquí.
- **Alternatives considered**: Agregar una condición explícita a `botonEnvioDe` para excluir el
  trigger de autoguardado por `id` — innecesario, ya que la condición de `tagName` existente ya
  resuelve el caso sin tocar código compartido por el resto de la app.

## Decisión 6: Nueva tabla `borradores_lectura_medidor`, upsert por (usuario, periodo, locación)

- **Decision**: Se crea `borradores_lectura_medidor` (`usuario_id` FK → `users`, `periodo` date,
  `locacion_id` FK → `locaciones`, `lectura_actual` decimal nullable, timestamps), con un índice
  único compuesto `(usuario_id, periodo, locacion_id)`. Cada ciclo de autoguardado hace un
  `upsert()` de las filas con valor no vacío del formulario actual (una sola sentencia, no un
  `DB::transaction` por fila como el guardado final, porque el borrador no aplica las
  validaciones de negocio de FR-008/FR-009 — ver Assumptions de `spec.md`).
- **Rationale**: Un esquema relacional normalizado (una fila por locación) es directamente
  consultable con `where usuario_id = ? and periodo = ?` para restaurar (FR-011) y se alinea con
  el Principio I de la constitución (aprovechar las capacidades relacionales de PostgreSQL,
  `NUMERIC` para la lectura) en vez de un blob JSON de esquema libre. Además, promover un
  borrador a `LecturaMedidor` real en el guardado final es una copia directa fila a fila, sin
  parseo.
- **Alternatives considered**: Una sola columna JSON por usuario+periodo con todas las lecturas
  del lote — descartada por ir en contra del principio de esquema relacional explícito de la
  constitución, y por ser más difícil de indexar/consultar que una fila por locación.

## Decisión 7: Totalizado por consumo — tarifa reutilizada de `ConfiguracionGeneral`, cálculo en el navegador

- **Decision**: FR-013/FR-014/FR-015 reutilizan `ConfiguracionGeneral::actual()->tarifa_luz_por_unidad`
  (ya existente desde specs/005, usada hoy en `ServicioGeneracionReciboPeriodo`) como valor por
  defecto del único input de tarifa de la pantalla. El total por fila (`consumo × tarifa`) y el
  total general (suma de los totales por fila) se calculan en JavaScript puro en el navegador —
  sin round-trip al servidor — siguiendo exactamente el patrón ya usado por
  `resources/js/costos-fijos-contrato.js` (recalcular en `input`, reenganchar tras
  `htmx:afterSettle`). Cuando el usuario cambia el valor de la tarifa, un `hx-patch` disparado en
  el evento `change` del campo (no en cada tecla) persiste el nuevo valor en
  `configuracion_general` vía una acción nueva y liviana (`actualizarTarifa`), con
  `hx-swap="none"` — sin alertas ni recarga, igual de discreto que el autoguardado (Decisión 5).
- **Rationale**: Evita duplicar la fuente de verdad de la tarifa (una sola fila en
  `configuracion_general`, ya usada por recibos) y evita inventar un mecanismo de cálculo nuevo
  cuando el proyecto ya tiene un patrón idéntico (suma en vivo, JS puro, sin Alpine.js) para el
  mismo tipo de necesidad (`costos-fijos-contrato.js`). Separar "recalcular en pantalla" (JS puro,
  instantáneo) de "persistir la tarifa" (htmx, en `change` en vez de en cada tecla) evita golpear
  el servidor en cada dígito tipeado.
- **Alternatives considered**: Un campo de tarifa por fila/local — descartado por la clarificación
  ya registrada en `spec.md` (un único input global). Calcular el total en el servidor y
  refrescarlo con htmx en cada cambio — descartado por ser más lento (round-trip por cada tecla)
  sin ningún beneficio, dado que el cálculo es una multiplicación simple sin lógica de negocio
  sensible.

## Decisión 8: Exportación a Excel y PDF con librerías PHP puras, reutilizando la consulta de `index()`

- **Decision**: Se agregan dos dependencias Composer nuevas: `maatwebsite/excel` (Excel, envuelve
  PhpSpreadsheet) y `barryvdh/laravel-dompdf` (PDF) — la misma librería ya evaluada y documentada
  como alternativa viable en `specs/007-estado-envio-recibo/research.md`, ahora sí necesaria
  porque FR-016 pide explícitamente un archivo descargable (a diferencia de specs/007, que solo
  pedía una vista de impresión del navegador). Ambas rutas nuevas (`exportarExcel`, `exportarPdf`)
  reciben el mismo query param `periodo` que `index()` y reutilizan exactamente la misma consulta
  N+1-seguro (Decisión 2) extraída a un método privado compartido, para que el contenido exportado
  nunca pueda desincronizarse del contenido visible en pantalla (FR-016). El PDF usa una plantilla
  Blade dedicada (`exportar-pdf.blade.php`) renderizada por `Pdf::loadView()`; el Excel usa una
  clase `ExportacionRegistroMasivoLecturas` (`FromCollection`, `WithHeadings`) sin plantilla Blade.
- **Rationale**: Ambas librerías son PHP puro (sin binarios externos como `wkhtmltopdf`), lo que
  respeta el Principio I (stack sin dependencias de infraestructura adicionales) y funciona igual
  en cualquier entorno de despliegue del proyecto. Reutilizar la consulta de `index()` en vez de
  reconstruirla evita que un cambio futuro en la lógica de agrupación/lectura anterior deje la
  exportación desactualizada silenciosamente.
- **Alternatives considered**: Generar el PDF con `window.print()` del navegador como en
  specs/007 — descartado porque FR-016 pide explícitamente "exportar" (un archivo), no solo una
  vista de impresión. Un endpoint único que decida el formato por un parámetro (`?formato=pdf`) en
  vez de dos rutas — descartado por ser menos explícito en `routes/web.php` y en los nombres de
  ruta, sin ninguna ventaja real.

## Decisión 9: Edición en línea reutilizando la validación de `LecturaMedidorController@update`, servida vía htmx

- **Decision**: FR-017 se implementa con dos rutas nuevas bajo el mismo controlador
  (`editarInline` GET, `actualizarInline` PATCH), disparadas por el ícono de FR-005
  (`hx-get` para reemplazar la celda por un input editable vía `hx-target`/`hx-swap="outerHTML"`,
  `hx-patch` para guardar). `actualizarInline` reutiliza `SolicitudGuardarLecturaMedidor` y el
  mismo patrón `DB::transaction` + `ConsumoNegativoSinConfirmarException` que
  `LecturaMedidorController@update`, pero en vez de redirigir devuelve la misma parcial de fila
  (`fila-registro-masivo.blade.php`) ya actualizada — sin navegar fuera de la pantalla de registro
  masivo, y sin duplicar la lógica de validación de consumo negativo.
- **Rationale**: Reutilizar la validación ya probada del flujo individual (en vez de reimplementarla
  para el modo inline) evita que las dos vías de edición diverjan en su criterio de consumo
  negativo. htmx con `hx-swap="outerHTML"` sobre la fila es el mecanismo ya establecido por el
  Principio VI para reemplazar contenido sin recargar la página, sin introducir Alpine.js ni un
  modal (que además requeriría gestionar el foco manualmente, algo que el intercambio inline de
  htmx no necesita).
- **Alternatives considered**: Un modal Bootstrap con el formulario de edición — descartado porque
  el Principio VI reserva el `Modal` de Bootstrap para confirmaciones destructivas y formularios
  secundarios embebidos; aquí un intercambio inline en la misma fila es más directo y evita el
  paso adicional de abrir/cerrar el modal para una edición de un solo campo.

## Decisión 10: Ícono no invasivo con equivalente textual accesible, en vez del badge "Completada"

- **Decision**: El `<span class="badge text-bg-success">Completada</span>` se reemplaza por
  `<i class="bi bi-check-circle-fill text-success" aria-label="Lectura completada" data-bs-toggle="tooltip" title="Lectura completada"></i>`,
  el mismo ícono que además actúa como disparador de la edición en línea (Decisión 9). El texto
  "Completada" deja de estar permanentemente visible pero sigue disponible como `aria-label` (para
  lectores de pantalla) y como tooltip Bootstrap al pasar el cursor/foco (para el resto de
  usuarios), en vez de eliminarse.
- **Rationale**: Satisface el pedido explícito ("no invasivo") sin violar el Principio III
  ("los íconos son siempre un refuerzo visual adicional a una etiqueta textual explícita, nunca un
  reemplazo") en su lectura de accesibilidad: el texto explícito sigue existiendo, solo deja de
  ocupar espacio visual permanente. Es consistente con cómo Bootstrap 5 resuelve botones
  icon-only accesibles (`aria-label` + tooltip) en vez de requerir siempre texto visible en línea.
- **Alternatives considered**: Quitar el badge sin ningún equivalente textual — descartado por
  violar directamente el Principio III. Mantener el badge de texto pero reducir su tamaño/color —
  descartado porque el pedido es explícito sobre reemplazarlo por un ícono, no solo atenuarlo.

## Resultado

Todas las decisiones técnicas reutilizan servicios, excepciones y convenciones ya existentes en
el proyecto (`ServicioConstruccionArbolLocaciones`, `ServicioCalculoConsumoMedidor`,
`ConsumoNegativoSinConfirmarException`, `LecturaMedidorDuplicadaException`, `ConfiguracionGeneral`,
el patrón `DB::transaction` por fila, htmx como única capa de escritura asíncrona, y el patrón de
JS puro presentacional de `costos-fijos-contrato.js`). Las piezas genuinamente nuevas son la tabla
de borrador con su upsert periódico, las dos dependencias Composer de exportación, y las rutas de
edición en línea/actualización de tarifa. No quedan `NEEDS CLARIFICATION` pendientes para la
Fase 1.
