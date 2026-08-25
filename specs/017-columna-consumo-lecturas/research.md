# Research: Columna de Consumo y Alineación del Ícono de Completado en Registro Masivo

## Contexto

El Technical Context del plan no dejó ningún `NEEDS CLARIFICATION` — el spec ya resolvió el
posicionamiento y formato de la columna nueva apoyándose en un precedente real ya existente en el
código (la exportación a Excel/PDF de la misma pantalla). Esta investigación confirma ese
precedente leyendo el código fuente actual y documenta cómo reutilizarlo sin duplicar lógica de
cálculo.

**Nota de entorno**: igual que specs/016, usar el binario de PHP de Herd
(`C:\Users\joel5\.config\herd\bin\php.bat`) para `artisan`/`pest` en esta máquina.

## Decisión 1: La columna "Consumo" se calcula en el navegador, igual que "Total"

- **Decision**: La celda `#consumo-fila-{locacion_id}` se renderiza inicialmente como `—` en Blade
  (idéntico al patrón ya usado por `#total-fila-{locacion_id}`) y se completa/actualiza en
  `resources/js/registro-masivo-lecturas.js`, dentro de la misma función `recalcularTotales()` que
  ya calcula `consumo` internamente para alimentar "Total" — solo se agrega la escritura de ese
  mismo valor ya calculado en la celda nueva, sin una segunda función de cómputo.
- **Rationale**: `calcularConsumoDeCampo(campo)` (líneas 13-28 del archivo) ya resuelve
  correctamente los tres casos de la spec: fila completada (lee `data-consumo`, el valor exacto
  persistido en `consumo_calculado`), fila pendiente con valor tipeado (resta en vivo
  `lectura_actual` del input menos `data-lectura-anterior`), y fila sin dato (retorna `null`). Ya
  se ejecuta en `DOMContentLoaded` y en `htmx:afterSettle` (tras cualquier swap — edición en línea,
  actualización de tarifa), por lo que la celda de consumo se mantiene sincronizada exactamente en
  los mismos momentos en que hoy se sincroniza "Total", sin cablear un evento nuevo.
- **Alternatives considered**: renderizar el consumo de las filas ya completadas directamente en
  Blade (server-side, ya que `$lecturaDelPeriodo->consumo_calculado` está disponible en
  `fila-registro-masivo.blade.php`) y dejar el cálculo en vivo solo para filas pendientes —
  descartado por introducir dos rutas de renderizado distintas para la misma columna (una
  server-side, otra client-side) dentro de la misma tabla, cuando "Total" ya resolvió el mismo
  problema con una sola ruta uniforme; mantener un único patrón reduce la superficie de bugs futuros
  del mismo tipo que motivó specs/016.

## Decisión 2: Formato y ubicación de la columna — igual que la exportación existente

- **Decision**: La columna nueva se llama "Consumo", se ubica entre "Lectura Actual" y "Total", y
  formatea el valor con 2 decimales — exactamente el mismo orden, encabezado ("Consumo (kWh)") y
  formato (`number_format($fila['consumo'], 2)`) que ya usa
  `resources/views/lecturas/registro-masivo/exportar-pdf.blade.php` y
  `app/Exports/ExportacionRegistroMasivoLecturas.php`.
- **Rationale**: Confirmado leyendo ambos archivos — `filasExportables()` en el controlador ya
  produce `'consumo' => $consumo !== null ? (string) $consumo : null` con ese orden y ese
  encabezado. Adoptar el mismo contrato visual evita que la pantalla en vivo y sus propias
  exportaciones muestren el mismo dato con dos apariencias distintas.
- **Alternatives considered**: ubicar "Consumo" antes de "Lectura Periodo Anterior" o al final de
  la tabla — descartado porque rompería el orden ya establecido y ya visible para el usuario en los
  archivos exportados, que es precisamente la referencia que ancló el Assumption del spec.

## Decisión 3: Grid CSS de 4 a 5 columnas — un solo punto de cambio

- **Decision**: `resources/css/bootstrap.scss` define el layout de la tabla como CSS Grid con una
  única variable `$registro-masivo-columnas: minmax(16rem, 1fr) 10rem minmax(14rem, 1fr) 8rem;`
  (línea 255) reutilizada por el encabezado, cada fila y la fila de total general. Se agrega un
  quinto track de ancho similar al de "Total" (`8rem`) en la posición de "Consumo", y se ajusta el
  `min-width` del contenedor (línea 269, hoy `40rem`) para que seguir cabiendo sin recorte en el
  ancho mínimo soportado.
- **Rationale**: Al ser una única variable SCSS compartida por las tres reglas del grid (línea
  266), cambiarla ahí basta para que encabezado, filas y fila de total general queden alineados
  automáticamente — evita repetir `grid-template-columns` en tres lugares.
- **Alternatives considered**: usar una tabla HTML (`<table>`) en vez de CSS Grid — descartado por
  ser un cambio de estructura mucho más grande que lo que pide el spec, y porque el resto de la
  pantalla (incluida la fila de árbol jerárquico que reutiliza clases `.fila-arbol__*`) ya depende
  de este layout de grid.

## Decisión 4: Reacomodo del ícono — solo orden de marcado, sin nueva lógica

- **Decision**: En `campo-lectura-registro-masivo.blade.php` (líneas 25-38), se invierte el orden
  de marcado dentro del `@if ($lecturaDelPeriodo !== null && ! $modoEdicion)`: el `<button>` con el
  ícono `bi-check-circle-fill` pasa a declararse antes que el `<span class="cifra">` del valor, y
  su clase de margen (`ms-2`, margen a la izquierda) pasa a `me-2` (margen a la derecha) para
  conservar el mismo espaciado visual entre ícono y valor, ahora en el orden inverso.
- **Rationale**: El comportamiento de clic (`hx-get` a `editarInline`), el `aria-label` y el
  `title` del tooltip ya están en el propio `<button>` — reordenar los dos elementos hermanos no
  toca ninguno de esos atributos, cumpliendo FR-006 (mismo comportamiento, solo distinta posición).
- **Alternatives considered**: usar `flex-direction: row-reverse` por CSS en vez de reordenar el
  HTML — descartado porque invertiría también el orden de lectura para lectores de pantalla (el
  ícono es `aria-hidden`, pero el `<button>` completo pasaría a anunciarse antes del valor visible
  en el DOM real, lo cual es exactamente lo que FR-006 pide — reordenar el marcado ya produce el
  orden de lectura correcto sin necesidad de CSS adicional).

## Brecha de pruebas encontrada (a cerrar en tasks.md)

Ninguna prueba actual de `RegistroMasivoLecturasControllerTest.php` verifica el encabezado de la
tabla ni el orden de los elementos dentro de una celda — las pruebas existentes de FR-005/FR-017
(edición en línea) solo comprueban valores, no marcado/orden. Se necesitan pruebas nuevas que
confirmen: (a) el encabezado "Consumo" está presente y en el orden esperado, (b) para una fila
completada, la celda de consumo contiene el valor de `consumo_calculado`, (c) para una fila con
lectura anterior pero sin lectura del periodo, la celda de consumo existe con el marcador de "sin
dato" en el HTML inicial (el recálculo en vivo con un valor tipeado no es verificable sin
navegador, ver Technical Context), y (d) el ícono de check aparece antes que el `<span>` del valor
en el HTML (orden de nodos, no solo presencia).
