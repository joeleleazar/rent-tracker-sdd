# Research: Navegación a Contratos y Recibos desde las Vistas de Locación

## Contexto

El Technical Context del plan no dejó ningún `NEEDS CLARIFICATION` — el stack, las rutas y los
controladores involucrados ya existen en el proyecto. Esta investigación documenta las
decisiones de reutilización tomadas a partir del código ya presente en el repositorio, en vez
de evaluar alternativas externas.

**Revisión 2026-08-24** (tras `/speckit-clarify`): las Decisiones 1-3 documentan el trabajo ya
completado sobre `locaciones/show.blade.php` (US2). Las Decisiones 4-6 son nuevas, para el
trabajo pendiente sobre `fila-arbol-locacion.blade.php` (US1, ahora P1/MVP).

## Decisión 1: Reutilizar la ruta `locaciones.recibos.index` ya existente

- **Decision**: El nuevo botón "Ver Recibos" en `locaciones/show.blade.php` apunta a la ruta
  ya registrada `locaciones.recibos.index` (`routes/web.php`), resuelta por
  `ReciboController@index`, que ya devuelve `locacion.recibos()->orderByDesc('periodo')->get()`
  — es decir, el historial completo requerido por FR-006.
- **Rationale**: La ruta y el controlador ya cumplen exactamente lo que pide FR-001/FR-006
  (historial completo, estado vacío manejado por la vista `locaciones/recibos/index.blade.php`
  sin lanzar error cuando la colección está vacía). No hay ninguna razón para crear una ruta o
  vista nueva.
- **Alternatives considered**: Crear una vista combinada nueva que liste recibos embebidos
  dentro de `locaciones/show.blade.php` — descartada porque el usuario aclaró explícitamente
  que el requisito es *navegación* ("ir al historial de recibos"), no una vista combinada ni
  edición en línea (ver Assumptions de `spec.md`).

## Decisión 2: Reutilizar `contratos.index` como historial + puerta de entrada al CRUD

- **Decision**: El botón "Ver Contratos" ya existente sigue apuntando a `contratos.index`
  (`ContratoController@index`), que ya lista contratos vigentes y finalizados de la locación y
  ya enlaza, desde esa misma pantalla, a `contratos.create` y a `contratos.show` →
  `contratos.edit`.
- **Rationale**: Verificado en `resources/views/contratos/index.blade.php` (vía
  `ContratoController`) y `resources/views/contratos/show.blade.php:69` (botón "Editar
  Contrato" ya presente). FR-002/FR-003/FR-004 ya están satisfechos por este flujo existente;
  el plan solo necesita una prueba que formalice/proteja este comportamiento (ver Fase 1).
- **Alternatives considered**: Ninguna — no aplica introducir una ruta o pantalla nueva para
  cumplir historias ya resueltas por el código existente.

## Decisión 3: Condición de visibilidad (`es_alquilable`) y patrón visual del nuevo botón

- **Decision**: El botón "Ver Recibos" se agrega dentro del mismo bloque
  `@if ($locacion->es_alquilable)` que ya envuelve al botón "Ver Contratos" en
  `resources/views/locaciones/show.blade.php:48-50`, con la clase `btn btn-outline-secondary`
  y el ícono `bi-receipt` (el mismo ícono que ya usa `contratos/show.blade.php:71` para "Ver
  Recibos" hacia esta misma ruta).
- **Rationale**: Reutilizar el criterio y el patrón visual ya establecido evita introducir una
  regla de negocio nueva y mantiene la consistencia de iconografía exigida por el Principio VI
  de la constitución (mismo ícono para el mismo concepto en toda la aplicación).
- **Alternatives considered**: Mostrar el enlace a recibos incondicionalmente (sin el gate de
  `es_alquilable`) — descartada porque contradice FR-005 y el Edge Case de la spec sobre
  locaciones no alquilables/organizativas.

## Decisión 4: Componente `Dropdown` nativo de Bootstrap 5 para la columna Acciones de la fila

- **Decision**: La columna Acciones de `fila-arbol-locacion.blade.php` reemplaza los botones
  sueltos "Editar" (y, para locaciones alquilables, los nuevos "Ver Contratos"/"Ver Recibos")
  por un único botón trigger (`btn btn-sm btn-outline-secondary dropdown-toggle`,
  `data-bs-toggle="dropdown"`) que despliega un `<ul class="dropdown-menu">` con esas
  opciones como `<li><a class="dropdown-item">`. El botón "+" (crear hija) queda fuera del
  menú, tal como se decidió en la sesión de clarificación (FR-010).
- **Rationale**: El componente `Dropdown` ya forma parte del bundle JS de Bootstrap importado
  en `resources/js/bootstrap.js` (incluye Popper) — no se agrega ninguna dependencia nueva. Se
  activa por completo vía atributos `data-bs-*`, sin JS custom, igual que el `Collapse` ya usado
  en la misma parcial para expandir/contraer hijos. El Principio III de la constitución lo
  autoriza explícitamente para mejorar "eficiencia y densidad" en tablas.
- **Alternatives considered**: Agregar los 2 botones nuevos sueltos junto a "Editar" (4 controles
  en la columna Acciones) — descartada en la clarificación por riesgo de desborde en filas
  profundamente indentadas, que es justamente el Edge Case y FR-011 que motivó esta decisión.

## Decisión 5: `data-bs-strategy="fixed"` para evitar que el contenedor con scroll recorte el menú

- **Decision**: El `<div class="dropdown">` de cada fila usa el atributo
  `data-bs-strategy="fixed"` documentado por Bootstrap 5 para dropdowns dentro de contenedores
  con overflow — evita que Popper posicione el menú relativo a su ancestro con scroll.
- **Rationale**: `.tabla-arbol-locaciones` ya tiene `overflow-x: auto` (specs/013, FR-007, para
  no producir scroll a nivel de página completa). Sin esta estrategia, un menú desplegado cerca
  del borde de una fila profundamente indentada podría recortarse o quedar inaccesible dentro de
  ese contenedor — exactamente el riesgo que describe el nuevo Edge Case y FR-011 de `spec.md`.
  `data-bs-strategy="fixed"` es la solución que la propia documentación de Bootstrap 5 recomienda
  para este escenario (dropdown dentro de una tabla/contenedor con scroll), sin JS adicional.
- **Alternatives considered**: Cambiar `.tabla-arbol-locaciones` a `overflow: visible` —
  descartada porque reintroduciría el scroll de página completa que specs/013 (FR-007) ya
  resolvió explícitamente para filas anchas o muy indentadas.

## Decisión 6: Trigger del menú — solo ícono con `aria-label`, sin texto visible

- **Decision**: El botón trigger del menú usa únicamente el ícono `bi-three-dots-vertical` (sin
  texto visible) con `aria-label="Acciones para {{ $locacion->nombre }}"`, igual que el patrón
  ya usado por el botón "+" en la misma fila (`aria-label="Agregar locación hija de..."`, sin
  texto visible).
- **Rationale**: Consistencia directa con un patrón ya existente en el mismo componente
  (`fila-arbol-locacion.blade.php:44-48`), que ya demuestra que un botón icono-solo con
  `aria-label` descriptivo es el estándar aceptado en esta tabla para acciones de fila cuando el
  ancho es limitado. El ícono de 3 puntos verticales es la convención universal de "más
  acciones" (kebab menu), reconocible sin necesidad de etiqueta visible.
- **Alternatives considered**: Botón con texto "Acciones" visible — descartada por ser el único
  control de la fila con etiqueta de texto además de "Editar" (que desaparece dentro del menú),
  inconsistente con el patrón icono-solo ya establecido por el botón "+" vecino.

## Resultado

Todas las incógnitas quedaron resueltas reutilizando rutas, controladores y patrones visuales
ya existentes, más un componente Bootstrap ya empaquetado (`Dropdown`) usado por primera vez de
forma consistente con las convenciones ya vigentes en el mismo archivo. No quedan
`NEEDS CLARIFICATION` pendientes para la Fase 1.
