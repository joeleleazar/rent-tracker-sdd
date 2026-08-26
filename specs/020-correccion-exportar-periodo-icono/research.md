# Research: Corrección de Exportación, Cambio de Periodo e Ícono de Edición en Registro Masivo

## Contexto

A diferencia de specs/016 (donde la lectura estática del código no alcanzó para confirmar la causa
raíz), esta vez los tres defectos se **reprodujeron en vivo** en el navegador durante esta fase de
planificación, con datos de prueba sembrados y luego eliminados (sin dejar rastro en la base de
datos de desarrollo). Cada hallazgo está respaldado por evidencia observada, no solo por lectura de
código.

**Nota de entorno**: igual que specs/016-019, usar el binario de PHP de Herd
(`C:\Users\joel5\.config\herd\bin\php.bat`) para `artisan`/`pest` en esta máquina.

## Decisión 1: `hx-boost="false"` en los enlaces de exportar (FR-002/FR-003)

- **Reproducido**: con la pestaña del navegador en `/lecturas/registro-masivo?periodo=2026-08`, se
  hizo clic en "Exportar a Excel". La lectura de la red confirmó una petición
  `GET /lecturas/registro-masivo/exportar/excel?periodo=2026-08` con `statusCode: 200`, pero la
  URL de la pestaña cambió a esa misma ruta de exportación (en vez de permanecer en la pantalla) y
  la pestaña quedó congelada (timeout de `Page.captureScreenshot`, "renderer unresponsive").
- **Decision**: agregar `hx-boost="false"` a los dos `<a>` de "Exportar a Excel"/"Exportar a PDF"
  en `index.blade.php`, para que htmx los excluya explícitamente del `hx-boost="true"` del layout
  raíz (specs/011) y el navegador los trate como una navegación/descarga normal.
- **Rationale**: `hx-boost` intercepta todo enlace/formulario dentro de su contenedor por diseño
  (specs/011, Principio VI) — es el comportamiento correcto para la navegación normal de la app,
  pero exactamente el incorrecto para un enlace cuya respuesta es un archivo binario con
  `Content-Disposition: attachment`, no una página HTML para reemplazar. `hx-boost="false"` es el
  mecanismo declarativo que htmx ya provee para esta excepción puntual, sin tocar el `hx-boost` del
  resto de la app.
- **Alternatives considered**: mover los enlaces fuera del contenedor con `hx-boost="true"` —
  descartado porque ese contenedor es el `<div>` raíz de todo el layout (specs/011); no hay forma
  de sacar solo estos dos enlaces sin restructurar el layout entero. `hx-boost="false"` es la
  solución ya prevista por htmx para exactamente este caso.

## Decisión 2: `type="submit"` explícito en el botón "Cambiar Periodo" (FR-001)

- **Reproducido**: se cambió el valor del input de periodo a "September 2026" y se hizo clic en
  "Cambiar Periodo". El campo visual mostró "September 2026", pero la URL de la pestaña **no
  cambió** (siguió en `?periodo=2026-08`) y la fila de la locación de prueba siguió mostrando el
  estado de agosto (su propia lectura de agosto marcada como "completada", no como "lectura
  anterior" de septiembre) — el formulario nunca se envió. Navegar directamente por URL a
  `?periodo=2026-09` sí mostró correctamente la lectura de agosto como "Lectura Periodo Anterior"
  de septiembre, confirmando que la consulta del servidor (ya corregida en specs/016) es correcta;
  el defecto está exclusivamente en que el botón nunca dispara el envío.
- **Decision**: `resources/views/components/secondary-button.blade.php` define
  `$attributes->merge(['type' => 'button', ...])` — `type="button"` es el valor por defecto del
  componente en todo el resto de la app (correcto ahí, para botones que no envían un formulario).
  En `index.blade.php`, el uso de `<x-secondary-button>Cambiar Periodo</x-secondary-button>` dentro
  del `<form method="GET">` nunca sobrescribió ese default. Se agrega `type="submit"` explícito en
  ese único uso: `<x-secondary-button type="submit">Cambiar Periodo</x-secondary-button>`.
- **Rationale**: Es la causa raíz exacta y ya confirmada — ningún otro cambio (consulta, htmx,
  JS) es necesario. La corrección es de una sola palabra, en el único lugar donde este botón
  necesitaba comportarse como submit.
- **Alternatives considered**: cambiar el default del componente `secondary-button` a
  `type="submit"` — descartado porque rompería silenciosamente cualquier otro uso existente de
  `<x-secondary-button>` en la app que hoy depende de que NO envíe un formulario (ej. botones
  "Cancelar" dentro de un `<form>`); el fix correcto es local a este único botón.

## Decisión 3: disponer los tooltips de Bootstrap antes de que htmx los reemplace (FR-006)

- **Reproducido**: se pasó el cursor sobre el ícono verde de "completada" (apareció el tooltip
  "Lectura completada — clic para editar"), y se hizo clic. La celda cambió correctamente a modo
  edición (campo + botones guardar/cancelar), pero el tooltip **permaneció visible**, flotando
  sobre la fila, sin ningún elemento visible al que "pertenezca" ya en el DOM.
- **Decision**: en `resources/js/registro-masivo-lecturas.js`, agregar un listener del evento
  `htmx:beforeCleanupElement` (el hook que htmx dispara justo antes de remover un elemento del DOM
  tras un swap) que llama `bootstrap.Tooltip.getInstance(elemento)?.dispose()` sobre el elemento
  removido y sobre cualquier descendiente con `data-bs-toggle="tooltip"`.
- **Rationale**: `inicializarTooltips()` ya crea instancias con `getOrCreateInstance()` pero nunca
  las destruye — Bootstrap adjunta el popup del tooltip como un elemento flotante separado (vía
  Popper), no como hijo del propio trigger, así que reemplazar el trigger con `hx-swap="outerHTML"`
  no se lleva el tooltip con él. `htmx:beforeCleanupElement` es el punto exacto que la propia
  documentación de htmx recomienda para liberar recursos de terceros (tooltips, popovers, plugins)
  asociados a un elemento a punto de desaparecer.
- **Alternatives considered**: llamar `.dispose()` manualmente solo en el handler de clic del ícono
  de completada — descartado porque, tras FR-004/FR-005, ese ícono deja de ser el disparador de la
  edición (ver Decisión 4); el nuevo botón de editar es el que dispara el swap, y el enfoque de
  `htmx:beforeCleanupElement` cubre ambos sin depender de cuál control específico originó el
  cambio.

## Decisión 4: separar el ícono de "completada" del control de "editar" (FR-004/FR-005)

- **Decision**: en `campo-lectura-registro-masivo.blade.php`, el ícono verde
  (`bi-check-circle-fill`) deja de ser un `<button>` con `hx-get` — pasa a ser un `<span>`
  puramente informativo (`aria-label="Lectura completada"`, con su propio tooltip no interactivo
  "Lectura completada"). Se agrega un `<button>` nuevo, después del valor, con el ícono ya
  estandarizado para editar en toda la app (`bi-pencil-square`, Principio VI) — ese botón es quien
  ahora lleva el `hx-get`/`hx-target`/`hx-swap` que antes tenía el ícono de completada, con su
  propio tooltip "Editar lectura".
- **Rationale**: Es exactamente lo pedido — el ícono de estado y el control de acción son hoy el
  mismo elemento, lo cual generó el defecto de la Decisión 3 y además mezcla dos significados en un
  solo ícono (specs/017 ya había reposicionado este ícono para alinearlo visualmente, pero seguía
  siendo a la vez indicador y disparador). Separarlos resuelve la ambigüedad y reutiliza el ícono de
  "editar" ya convencional del resto de la app, sin introducir un ícono nuevo.
- **Alternatives considered**: mantener el ícono de completada como único control pero agregar
  `aria-live` o `role="status"` para mitigar la confusión — descartado porque no resuelve el pedido
  explícito del usuario ("mejor dejalo como icono y agrega otro que indique específicamente que vas
  a editarlo"), que pide dos controles distintos, no uno con más atributos.

## Brecha de pruebas a cerrar en tasks.md

Ninguna prueba actual verifica: (a) que los enlaces de exportar declaren `hx-boost="false"`; (b)
que el botón "Cambiar Periodo" declare `type="submit"`; (c) que el ícono de completada y el botón
de editar sean dos elementos separados en el HTML, cada uno con su propio `aria-label`/`title`. Las
tres son verificables como "contrato HTML" con Pest (presencia/ausencia de atributos), aunque el
comportamiento real en el navegador (descarga efectiva, refresco de página, ausencia de tooltip
huérfano) solo se valida manualmente vía `quickstart.md`.
