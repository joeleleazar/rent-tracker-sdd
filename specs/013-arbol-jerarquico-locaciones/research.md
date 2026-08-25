# Research: Árbol Jerárquico Horizontal de Locaciones

**Feature**: `013-arbol-jerarquico-locaciones` | **Date**: 2026-08-23

**Revisión 2026-08-23**: tras ver la primera iteración (tarjetas horizontales conectadas por líneas), el usuario pidió una tabla jerárquica indentada con columnas (Nombre/Locación, Estado, Tipo, Acciones) en su lugar, y un nuevo campo "Tipo". §3 queda reemplazada por §7; se agregan §8 y §9.

## 1. Consolidación de `/dashboard` y `/locaciones` (FR-002, Assumption A-001)

**Decision**: `GET /locaciones` (`LocacionController@index`) pasa a renderizar el árbol jerárquico completo (todas las locaciones, no solo alquilables). La ruta `GET /dashboard` (closure inline en `routes/web.php`) se reemplaza por un `redirect()->route('locaciones.index')`, y `resources/views/dashboard.blade.php` se elimina.

**Rationale**: La especificación pide una sola vista, no dos rutas que muestren contenido equivalente mantenido por separado. Redirigir en vez de duplicar la vista evita que ambas plantillas diverjan con el tiempo (violaría el espíritu de "no se requiere 2 vistas por separado" incluso si técnicamente hubiera 2 URLs). `locaciones.index` es el nombre de ruta más descriptivo para el propósito ("ver locaciones"), y ya es usado como destino de navegación en varias vistas existentes (ej. botón "Cancelar" de `contratos/create.blade.php`).

**Alternatives considered**:
- Mantener ambas vistas Blade renderizando el mismo árbol (sin redirect): rechazado, generaría dos plantillas idénticas que mantener en paralelo, contradiciendo el pedido de consolidación.
- Eliminar `/dashboard` por completo (404): rechazado sin necesidad — la ruta `dashboard` puede seguir usándose como alias post-login sin costo, con un simple redirect.

## 2. Construcción del árbol sin consultas N+1 (SC-003, FR-008)

**Decision**: `ServicioConstruccionArbolLocaciones::construir()` ejecuta una única consulta (`Locacion::orderBy('nombre')->get()`), agrupa el resultado en memoria por `locacion_padre_id` (`Collection::groupBy`), y arma recursivamente los nodos raíz (`locacion_padre_id === null`) usando esa agrupación ya cargada — sin llamar a `locacionesHijas()` (relación Eloquent) dentro de la recursión, que dispararía una consulta por nivel/nodo. Se aplica un límite defensivo de profundidad (`MAXIMO_PROFUNDIDAD_ARBOL`, mismo valor que `Locacion::MAXIMO_SALTOS_ANCESTROS` = 1000) para detener la recursión ante datos corruptos, igual que ya hace `Locacion::ancestros()`.

**Rationale**: Con hasta 1,000 locaciones (Asunción A-002 de `specs/001`), una consulta única agrupada en memoria es orders-of-magnitude más eficiente que N consultas recursivas, y es la técnica estándar para renderizar árboles completos desde una tabla adyacente en Laravel. La salvaguarda de profundidad es consistente con la ya existente en el modelo, evitando duplicar la lógica de protección con un valor o criterio distinto.

**Alternatives considered**:
- CTE recursiva de PostgreSQL (`WITH RECURSIVE`): rechazada por complejidad innecesaria dado el volumen esperado (cientos, no millones, de filas); una carga completa en memoria es más simple de mantener y suficientemente rápida.
- Recorrer con `locacionesHijas()` recursivamente (lazy): rechazada explícitamente, causaría N+1.

## 3. Renderizado visual del árbol horizontal (SUPERSEDIDA — ver §7)

**Decision original (2026-08-23, primera iteración)**: Componente Blade recursivo con `display: flex; flex-direction: row` para ramas horizontales (padre a la izquierda, hijos apilados verticalmente a la derecha) y líneas de conexión vía `border-left`/`border-top` (técnica de "árbol de organigrama"). **El usuario vio esta implementación funcionando y pidió reemplazarla** por una tabla indentada con columnas — ver §7 para la decisión vigente. Se conserva este registro por trazabilidad histórica, no como decisión activa.

## 4. Colapsar/expandir ramas (FR-006, US3)

**Decision**: Cada nodo con hijos envuelve su sub-árbol en el componente `collapse` nativo de Bootstrap 5 (`data-bs-toggle="collapse"`, `data-bs-target="#hijos-locacion-{id}"`), con un ícono `bi-chevron-right`/`bi-chevron-down` que rota según el estado `aria-expanded` del botón (mismo patrón CSS ya usable por el proyecto, sin JS adicional). Estado inicial: expandido por defecto (consistente con "ver de un vistazo la estructura completa", US1), permitiendo al administrador contraer manualmente las ramas que no necesita (US3). El estado de expansión/contracción no se persiste en servidor (Assumption A-004) — vive únicamente en el DOM de la sesión del navegador.

**Rationale**: Bootstrap 5 `collapse` ya está empaquetado (`resources/js/bootstrap.js`, ver `vite.config.js`) y es explícitamente el patrón recomendado por la Constitución (Principio III: "se permite y fomenta el uso de... acordeones"). No requiere `hx-boost` ni una petición al servidor (es una interacción puramente de presentación), por lo que no aplica la excepción de interactividad asíncrona con htmx (`specs/011`), reservada para operaciones de escritura (crear/editar/eliminar).

**Alternatives considered**:
- Persistir el estado de expansión en el servidor (sesión o base de datos): rechazado, agrega complejidad de escritura a una feature de solo visualización sin que ningún requisito lo exija (Assumption A-004).
- Implementar colapsado con JavaScript propio (sin Bootstrap `collapse`): rechazado, reinventa un componente ya disponible y probado en el proyecto.

## 5. Distinción visual alquilable/contenedora (FR-003) y acciones por nodo (FR-005)

**Decision**: Cada nodo es una tarjeta compacta (`card`) con: nombre de la locación, un `badge` (`text-bg-success` "Alquilable" / `text-bg-secondary` "No Alquilable"), y el nombre como enlace a `locaciones.show` (que ya expone Editar, Ver Contratos y Eliminar). No se agregan botones de acción adicionales directamente sobre el nodo.

**Rationale**: `locaciones.show` ya es la pantalla de gestión completa de una locación (Editar, Ver Contratos si es alquilable, Eliminar) desde `specs/001`; enlazar el nodo directamente ahí satisface FR-005 (acceso a gestión sin abandonar el contexto del árbol para "encontrar" la locación) sin duplicar botones dentro de cada nodo, lo que mantendría los nodos compactos y con el espaciado legible que pidió el usuario en vez de sobrecargarlos con 3 acciones cada uno.

**Alternatives considered**:
- Dropdown de acciones por nodo (Ver/Editar/Contratos) embebido en cada tarjeta: rechazado por ahora, añade densidad visual innecesaria cuando `locaciones.show` ya cubre esas acciones a un clic de distancia (cumple igualmente SC-002, "máximo 2 interacciones").

## 6. Framework de pruebas

**Decision**: Pest, consistente con el resto del proyecto.

**Rationale**: Ya adoptado por el proyecto.

**Alternatives considered**: Ninguna.

## 7. Renderizado visual como tabla jerárquica indentada (revisión 2026-08-23; reemplaza §3) — FR-001, FR-007, pedido de estilo del usuario

**Decision**: Reemplazar el componente de tarjetas conectadas por una parcial recursiva `resources/views/locaciones/partials/fila-arbol-locacion.blade.php` que renderiza **una fila por locación** usando CSS Grid con columnas fijas (`grid-template-columns: minmax(16rem, 1fr) 8rem 8rem 10rem` — Nombre/Locación, Estado, Tipo, Acciones), reutilizando la misma estructura de datos ya construida por `ServicioConstruccionArbolLocaciones` (sin cambios en el servicio). La indentación de cada fila es `padding-left: {profundidad} * $arbol-indentacion-nivel` (variable nueva en `bootstrap.scss`), con el control de expandir/contraer (`bi-chevron-right`/`bi-chevron-down`) y el ícono de tipo antepuestos al nombre dentro de la primera columna. Toda la tabla vive en un contenedor `overflow-x: auto` (igual que antes) para que la indentación acumulada en jerarquías profundas no rompa el layout de la página (FR-007).

**Rationale**: El usuario proporcionó un mockup concreto (tabla con encabezado de columnas, filas indentadas, íconos por tipo) y confirmó explícitamente reemplazar el estilo anterior. CSS Grid por fila (en vez de un `<table>` HTML nativo) permite que cada fila siga siendo un `<div>` independiente que se pueda envolver individualmente en el `collapse` de Bootstrap para ocultar su sub-árbol — un `<table>` nativo no admite ocultar un grupo arbitrario de `<tr>` de forma anidada sin JavaScript adicional para gestionar `rowspan`/agrupamiento, mientras que con Grid cada nivel es simplemente otro contenedor recursivo.

**Alternatives considered**:
- `<table>` HTML nativo con `<tr>` por fila: rechazado, dificulta anidar el `collapse` de Bootstrap por grupos de filas hijas sin JS adicional.
- Mantener las tarjetas horizontales y solo agregar las columnas Tipo/Acciones dentro de la tarjeta: rechazado, el usuario pidió explícitamente el cambio de estilo completo a tabla indentada, no un ajuste incremental de las tarjetas.

## 8. Campo "Tipo" de locación (FR-010, Assumption A-005)

**Decision**: Nueva columna `locaciones.tipo`, `enum` con `CHECK` constraint (mismo patrón documentado en `create_contratos_table` para `estado`: "`enum()` en el driver pgsql se compila como varchar + CHECK constraint"), valores `galeria`, `piso`, `sector`, `pasillo`, `local`; **nullable**, sin backfill de las locaciones ya existentes (mismo precedente que `inquilinos.apellidos`/`nombres` de `specs/003`: no hay forma confiable de inferir el tipo correcto de datos históricos sin revisión manual). Se valida como obligatorio (`required|in:galeria,piso,sector,pasillo,local`) en `SolicitudGuardarLocacion` para toda locación nueva o editada a partir de esta revisión. Un mapa estático `Locacion::TIPOS` (`['galeria' => ['etiqueta' => 'Galería', 'icono' => 'bi-building'], ...]`) centraliza la etiqueta e ícono `bi-*` por tipo, usado tanto en el `<select>` de los formularios como en la columna Tipo de la tabla jerárquica.

**Rationale**: El usuario confirmó explícitamente una lista fija predefinida (no texto libre) para poder asociar un ícono consistente por tipo y evitar variantes de escritura inconsistentes. Un `enum`/`CHECK` a nivel de base de datos, igual que `contratos.estado`, mantiene la validación también a nivel de datos (no solo de formulario), consistente con el Principio I de la Constitución.

**Alternatives considered**:
- Texto libre: rechazado explícitamente por el usuario (ver pregunta de clarificación respondida).
- Derivar el tipo automáticamente de la profundidad del nodo (raíz=Galería, nivel 2=Piso, etc.): rechazado explícitamente por el usuario — no coincide con jerarquías reales que intercalan niveles como "Sector"/"Pasillo" en orden variable (ver mockup proporcionado).
- Backfill automático de las locaciones existentes según su nombre (ej. "Piso" en el nombre → tipo piso): rechazado por el mismo motivo que en `specs/003` — heurísticas de texto libre no son confiables y podrían asignar un tipo incorrecto silenciosamente.

## 9. Acción rápida "Agregar" con padre preseleccionado (FR-011)

**Decision**: El botón "+" de cada fila enlaza a `route('locaciones.create', ['locacion_padre_id' => $locacion->id])`. `LocacionController@create` lee `request()->integer('locacion_padre_id')` y lo pasa a la vista como valor por defecto del `<select>` de locación padre (`old('locacion_padre_id', $locacionPadreId)`), reutilizando el formulario de creación completo ya existente (tamaño, ubicación física, descripción, tipo, es_alquilable) sin duplicar lógica de validación/guardado.

**Rationale**: Reutilizar el formulario completo evita duplicar reglas de negocio (ej. `ServicioValidacionJerarquiaLocacion` para prevenir ciclos) en un flujo "inline" paralelo. Un query string es la forma más simple de pasar el padre preseleccionado sin sesión ni estado adicional, y es coherente con patrones de Laravel ya usados en el proyecto (ej. `old()` para repoblar formularios tras un error de validación).

**Alternatives considered**:
- Formulario inline dentro de la tabla (crear sin navegar): rechazado explícitamente por el usuario en la clarificación de esta revisión.
