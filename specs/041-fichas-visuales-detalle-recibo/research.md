# Research: Fichas Visuales, Rótulos de Sección y Menú de Acciones en el Detalle de Recibo

## §1. Por qué esta vez sí se reprodujeron los detalles decorativos de la imagen

specs/038 descartó fichas/mayúsculas/menú-solo-ícono por conflicto con reglas ya escritas. Esta sesión
implementó primero una versión fiel a la imagen sin conocer esa decisión previa (el pedido del usuario en
esta sesión fue explícito: *"que tenga la siguiente estructura pero sin romper la estética existente"*, sin
mencionar specs/038. Al revisar el trabajo para documentarlo, se encontró el conflicto y se consultó al
usuario en vez de resolverlo unilateralmente en cualquier dirección — revertir habría descartado un pedido
explícito y ya verificado; mantenerlo sin avisar habría dejado una contradicción no registrada entre
`DESIGN.md` y specs/038. El usuario confirmó mantener la nueva versión.

**Decisión**: se mantiene la versión con fichas/mayúsculas/menú, documentada como excepción confirmada (ver
spec.md, Assumptions), no como una revocación silenciosa de la regla ni de specs/038.

## §2. Menú de acciones por pago: por qué "Más" y no "⋮" solo

La imagen de referencia mostraba un botón solo-ícono ("⋮"). El Principio VI de la constitución es explícito:
*"Los íconos son siempre un refuerzo visual adicional a una etiqueta textual explícita [...], nunca un
reemplazo de esa etiqueta."* Un `aria-label` sin texto visible satisface accesibilidad para lectores de
pantalla, pero no satisface ese principio para usuarios videntes. Se agregó el texto visible "Más" junto al
ícono `bi-three-dots-vertical`, evitando así una segunda excepción confirmada además de la de mayúsculas —
esta sí se resuelve dentro de las reglas existentes, sin necesitar el visto bueno del usuario.

## §3. "Registrar Pago": de formulario embebido a modal

El formulario vivía siempre visible al pie de la tarjeta "Pagos" cuando el recibo no estaba pagado por
completo. Con el nuevo encabezado de tarjeta (rótulo de sección + badge de avance) y la lista de pagos ya
más densa (ícono + monto + metadatos + menú por fila), un formulario embebido adicional competía por espacio
y atención. Se convirtió en un modal disparado por un botón "Registrar Pago" en la tarjeta — mismo patrón ya
usado en el proyecto para toda acción secundaria (Editar Pago, Eliminar Pago, Subir Evidencia, Anular/
Reactivar Recibo, etc., y explícitamente requerido por el Principio VI: *"el componente Modal nativo de
Bootstrap [...] para [...] formularios secundarios embebidos"*).

**Riesgo considerado**: un modal cerrado puede esconder errores de validación tras un POST fallido. Se
resolvió con la misma técnica ya usada en `profile/partials/delete-user-form.blade.php`
(`:show="$errors->userDeletion->isNotEmpty()"`) — `<x-modal-bootstrap :show="$errors->has('monto') ||
$errors->has('fecha_pago')">`, que reabre el modal automáticamente vía `data-autoshow` cuando la respuesta
trae esos errores.

## §4. Fichas de contexto (Locación / Período / Emisión)

Antes eran tres pares `dt`/`dd` indistinguibles del resto de la lista de conceptos monetarios. Separarlas en
fichas con ícono las distingue como "metadatos de contexto" en vez de "líneas de un desglose de cobro" —
la misma distinción que ya existe en otras vistas del proyecto entre datos de identificación (breadcrumb,
encabezado) y datos transaccionales (tabla, `dl` de montos), solo que aquí ambos vivían mezclados en un mismo
`dl`.

## §5. Verificación

- 69/69 tests de `ReciboControllerTest`, `PagoReciboControllerTest`, `EvidenciaPagoControllerTest` y
  `ComprobantePagoControllerTest` — sin cambios de resultado, incluida la aserción de orden de columnas de
  specs/038 (`assertSeeInOrder(['col-lg-7', 'Estado', 'Total', 'col-lg-5', 'Pagos', 'Estado del Recibo'])`),
  que se preservó a propósito reutilizando las mismas clases `col-lg-7`/`col-lg-5` y el mismo orden de
  contenido dentro de cada columna.
- `npm run build` sin errores; estilos nuevos (`.detalle-recibo__*`, `.pago-item*`, `.titulo-seccion`,
  `.breadcrumb-discreta`) verificados con estilos computados en el navegador, sin scroll horizontal en
  mobile (375px) ni desktop (1280px).
- Hallazgos del detector de `impeccable` sobre `resources/css/bootstrap.scss` (tamaños de fuente fuera del
  ramp de `DESIGN.md`) resueltos documentando el nuevo token `typography.seccion` y reutilizando la clase
  `small` de Bootstrap para la miga de pan, con una única excepción acotada y con motivo (`ignore-value
  design-system-font-size 1.1rem`, glyph de ícono, no tipografía de cuerpo) — ver `.impeccable/config.json`.
