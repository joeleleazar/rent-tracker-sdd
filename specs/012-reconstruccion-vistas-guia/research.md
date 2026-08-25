# Research: Reconstrucción de Vistas según la Guía de Referencia Bootstrap

**Feature**: `012-reconstruccion-vistas-guia` | **Date**: 2026-08-21

## 1. Timeline de historial de contratos (FR-003)

**Decision**: Construir el timeline con primitivas nativas de Bootstrap (`border-start border-3`, `badge`, `row`/`col-auto`, utilidades de espaciado `ms-*`/`ps-*`), replicando la estructura del snippet de referencia (indicador de fecha a la izquierda, badge de estado, datos del contrato y acciones a la derecha), sin introducir ninguna librería de timeline de terceros.

**Rationale**: Bootstrap 5 no tiene un componente "timeline" nativo, pero su sistema de grid y utilidades alcanza para replicar fielmente la estructura visual del snippet sin dependencias nuevas, consistente con el Principio VI (Bootstrap 5 como único sistema de componentes).

**Alternatives considered**: Una librería de timeline de terceros (ej. algún plugin de Bootstrap): rechazada, agrega una dependencia no auditada para un patrón que las utilidades existentes ya cubren.

## 2. Grid de costos con total calculado (FR-004)

**Decision**: El grid de 2 columnas usa `row g-4` + `col-md-6` con `input-group` (prefijo "S/") para cada uno de los 4 costos de referencia ya existentes (`costo_agua`, `costo_luz`, `costo_pasadizo`, `costo_seguridad` — el mismo alcance que ya definió `specs/004-condiciones-contrato-recibo`). El campo "Total de Referencia" es de solo lectura y se recalcula en vivo con un script pequeño (`resources/js/costos-fijos-contrato.js`) que suma los 4 campos en cada evento `input`, sin petición al servidor.

**Rationale**: El snippet de referencia incluye "Costo de Renta" dentro del mismo grid, pero `monto_renta` es un campo obligatorio del contrato ya validado y ubicado en el formulario principal desde `specs/002`, no un "costo de referencia" opcional (ese concepto nace específicamente en `specs/004` y se limita a agua/luz/pasadizo/seguridad). Mover `monto_renta` a este grid alteraría su ubicación y validación ya implementada, violando FR-010. Se adapta el componente (grid + total calculado) al alcance real de campos ya validado por el proyecto, conforme al Edge Case "Componentes de la guía no aplicables al dominio real del proyecto" de la especificación — el total de referencia se calcula solo sobre los 4 costos opcionales, consistente con lo que la propia vista actual (`costos-fijos-contrato.blade.php`) ya gestiona.

**Alternatives considered**: Incluir `monto_renta` en el total además de los 4 costos: rechazado, requeriría leer un campo de un formulario/sección distinta (acoplando dos partials que hoy son independientes) sin ningún requisito de negocio que lo exija.

## 3. Modal de solapamiento con dos bloques (FR-002) — ampliación aditiva de controlador

**Decision**: `ContratoSolapadoException` ya expone `$contratoEnConflicto` como propiedad pública readonly (ver `app/Exceptions/ContratoSolapadoException.php`, sin cambios). Se amplía el `catch` en `ContratoController@store`/`@update` para pasar ese objeto a la vista además del mensaje de error de texto ya existente: `return back()->withInput()->withErrors(['solapamiento' => $excepcion->getMessage()])->with('contratoEnConflicto', $excepcion->contratoEnConflicto)`. La vista renderiza el modal con dos bloques de `alert`: uno con los datos de `$contratoEnConflicto` (fechas, inquilino, monto — ya cargados vía sus relaciones Eloquent) y otro con los datos que el propio Administrador acaba de intentar guardar (disponibles vía `old()`).

**Rationale**: Es la única forma de construir el componente literal que exige FR-002 (dos bloques con datos reales de ambos contratos) sin duplicar la lógica de detección de solapamiento en la vista. `->with()` es un método estándar de `RedirectResponse` para adjuntar datos a la sesión junto a los errores ya existentes: no cambia el código de estado HTTP, la clave `solapamiento` que las pruebas ya verifican (`assertSessionHasErrors('solapamiento')`), ni ninguna regla de validación — solo agrega un dato adicional disponible para la vista. Se documenta explícitamente en `plan.md` → Complexity Tracking como la única excepción a "no tocar controladores" de esta spec, por ser aditiva y no rompible contra la suite existente.

**Alternatives considered**: Parsear los datos del contrato en conflicto desde el string del mensaje ya generado: rechazado, frágil (depende del formato exacto del mensaje) y mezcla responsabilidades. Repetir la consulta de solapamiento en la vista o en un nuevo endpoint: rechazado, duplica la lógica de negocio que `ServicioValidacionSolapamientoContrato` ya encapsula.

## 4. Selector de estado de recibo con 3 opciones simultáneas (FR-007)

**Decision**: Reemplazar los botones de acción condicionales actuales (`@if ($recibo->estado === 'pendiente') ... @elseif ...`) por un único `btn-group` con 3 `btn-check` (uno por cada valor de `nuevo_estado`: pendiente/pagado/anulado), cada uno dentro de su propio `<form>` que apunta al mismo endpoint `recibos.estado.update` ya existente, con el botón correspondiente al estado actual marcado como `checked`/`active` y deshabilitado (no se puede "cambiar" al mismo estado que ya tiene). Los formularios de confirmación ya existentes (modal para anular, confirmación para revertir) se mantienen sin cambios, solo se dispara su apertura desde el nuevo control unificado.

**Rationale**: `ReciboController::actualizarEstado()` ya es un único endpoint parametrizado por `nuevo_estado` (confirmado en el código actual), por lo que este componente es 100% de presentación: no requiere ningún cambio de controlador, ruta o Form Request. Satisface FR-007 (3 opciones simultáneamente visibles) sin tocar la lógica de negocio que valida las transiciones permitidas.

**Alternatives considered**: Un `<select>` con las 3 opciones: rechazado, el snippet de referencia especifica explícitamente un `btn-group`/`btn-check` visual, más claro que un desplegable para mostrar las 3 opciones simultáneamente.

## 5. Tarjetas de representante en grid (FR-005, FR-006)

**Decision**: Envolver las tarjetas individuales de representante (ya existentes desde `specs/003`/`specs/011`) en `row g-3` + `col-md-6` (ancho mínimo ~200px en pantallas angostas, dos por fila en pantallas medianas+), sin cambiar su contenido interno (nombre, DNI, badge Principal, botones Marcar Principal/Quitar). La búsqueda en el directorio global ya ocurre dentro de un modal (`data-bs-toggle="modal"` sobre el formulario de "Agregar Otro Representante", confirmado en el código actual) — no requiere cambios adicionales, solo se verifica que cumple FR-006 tal cual está.

**Rationale**: El componente ya cumple la mayoría de FR-005/FR-006 desde `specs/011`; el único ajuste real es el layout de grid (una tarjeta por fila hoy → dos columnas en pantallas medianas+), consistente con "ancho mínimo consistente" del Acceptance Scenario.

**Alternatives considered**: Ninguna — es el ajuste mínimo necesario sobre un componente que ya está mayormente alineado.

## 6. Reglas de impresión del comprobante (FR-008)

**Decision**: Ampliar el CSS propio ya existente en `resources/views/locaciones/recibos/comprobante.blade.php` (aislado de Bootstrap por la limitación de `html2canvas` con `oklch()`, ver `specs/007-estado-envio-recibo/research.md`) con una sección `@media print` que oculte los controles de navegación/interacción ("Volver al Recibo") y ajuste el documento a un formato limpio de una sola columna, tal como describe la guía.

**Rationale**: Esta vista ya es standalone (no usa el layout compartido) por una razón técnica ya documentada y vigente; las reglas de impresión se agregan dentro de ese mismo CSS aislado, sin reintroducir Bootstrap ni afectar la captura de `html2canvas` (que no depende de `@media print`, solo se dispara por JS al hacer clic en un botón).

**Alternatives considered**: Migrar esta vista al layout compartido para reutilizar utilidades de impresión de Bootstrap: rechazado, reabriría el problema de `oklch()` con `html2canvas` ya resuelto deliberadamente en `specs/007`.

## 7. Dropzone de documentos (FR-001)

**Decision**: Envolver los dos botones de carga ya existentes ("Seleccionar PDF del Contrato", "Subir Foto de Página") dentro de un contenedor con borde punteado (`border border-2 border-dashed p-4 text-center`) y un texto de ayuda ("O arrastra archivos aquí"), sin implementar arrastrar-y-soltar (drag & drop) funcional real, ya que ningún Acceptance Scenario de la especificación exige esa interacción — solo la presentación visual de un área de carga con las dos opciones y sus límites.

**Rationale**: Los `Acceptance Scenarios` de US1 (FR-001) piden explícitamente "un área de carga con las dos opciones... indicando los límites", no una interacción de arrastrar-soltar; agregar un manejador de eventos `dragover`/`drop` sería trabajo no solicitado (ver principio general del proyecto de no construir más allá de lo pedido).

**Alternatives considered**: Implementar drag & drop funcional con JS adicional: rechazado por alcance, ya que no es un requisito verificable de esta especificación; puede proponerse como mejora futura si el usuario lo pide explícitamente.
