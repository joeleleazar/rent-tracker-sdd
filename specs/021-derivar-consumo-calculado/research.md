# Research: Consumo Calculado en el Momento en vez de Almacenado

## Contexto

El Technical Context del plan no dejó `NEEDS CLARIFICATION` de stack — la única pregunta de alcance
real (Q1, criterio de "sin lectura anterior" unificado) ya se resolvió en `/speckit-specify`. Esta
investigación mapea, con evidencia leída directamente del código, cada punto de escritura y lectura
de `consumo_calculado` que existe hoy, para que ninguno quede afuera del cambio.

**Nota de entorno**: igual que specs/016-020, usar el binario de PHP de Herd
(`C:\Users\joel5\.config\herd\bin\php.bat`) para `artisan`/`pest` en esta máquina.

## Decisión 1: accessor con el mismo nombre de atributo, sin renombrar nada

- **Decision**: `LecturaMedidor` declara un accessor moderno de Eloquent
  (`Illuminate\Database\Eloquent\Casts\Attribute`) con el método `consumoCalculado()` — Eloquent lo
  expone automáticamente como `$lectura->consumo_calculado`, el mismo nombre que la columna que
  reemplaza. El cálculo: `number_format((float) $this->lectura_actual - (float) ($this
  ->lectura_anterior ?? 0), 2, '.', '')` — mismo formato de string con 2 decimales que ya producía
  el cast `decimal:2` de la columna.
- **Rationale**: Confirmado por búsqueda en todo `resources/views/` y `app/` — los 3 lugares de
  solo lectura (`campo-lectura-registro-masivo.blade.php`, `locaciones/lecturas/index.blade.php`,
  `locaciones/recibos/create.blade.php`) acceden siempre a `->consumo_calculado` como una propiedad
  simple, nunca en una consulta SQL (`WHERE`, `ORDER BY`, agregación) — un accessor cubre el 100%
  de esos usos sin tocarlos. Mantener el mismo nombre y el mismo formato de salida (string de 2
  decimales, no un float crudo) es lo que permite que FR-002/SC-001 ("las tres pantallas siguen
  mostrando exactamente lo mismo") se cumpla sin modificar esas vistas en absoluto.
- **Alternatives considered**: exponer un nombre nuevo (ej. `consumoDerivado()` / `->consumo`) —
  descartado porque obligaría a tocar los 3 sitios de lectura sin ningún beneficio; el nombre
  `consumo_calculado` sigue siendo semánticamente correcto (de hecho más preciso ahora: siempre es
  "calculado", nunca meramente "guardado").

## Decisión 2: `lectura_anterior ?? 0` en el accessor, no en cada sitio de escritura

- **Decision**: El criterio de "sin lectura anterior → 0" (FR-005, Q1:A) vive **una sola vez**, en
  el accessor del modelo — no se replica en `LecturaMedidorController`,
  `RegistroMasivoLecturasController` ni en `ImportarLecturasMedidorHistoricas`. Esos tres puntos
  simplemente dejan de escribir `consumo_calculado`; el valor que verá cualquier pantalla se deriva
  siempre del mismo lugar.
- **Rationale**: Es la razón de ser de esta feature — specs/016 y specs/019 tuvieron que corregir
  el mismo tipo de cálculo duplicado en más de un punto de escritura. Centralizarlo en el modelo
  hace estructuralmente imposible que un cuarto punto de escritura futuro (o uno de los tres ya
  existentes) calcule el consumo con un criterio distinto sin que se note — hay un solo lugar que
  lo define.
- **Alternatives considered**: dejar que cada controlador siga calculando `$consumo` en memoria
  (como hoy) solo para mostrarlo en la respuesta, sin guardarlo — descartado porque reintroduce la
  duplicación de lógica que esta feature busca eliminar; el accessor ya cubre esos casos también
  (ej. la respuesta JSON/vista de `actualizarInline` puede leer `$lectura->consumo_calculado` del
  modelo recién guardado en vez de mantener su propia variable `$consumo`).

## Decisión 3: `LecturaMedidorController::update()` también se limpia

- **Decision**: Además de `store()` (ya identificado en la descripción del usuario),
  `LecturaMedidorController::update()` (línea 137, edición de una lectura ya registrada desde el
  flujo individual) también escribe `'consumo_calculado' => $consumo` en su `$lectura->update([...])`
  — se quita esa clave igual que en `store()`.
- **Rationale**: Sin este punto, `update()` seguiría escribiendo un valor en una columna que ya no
  existe, rompiendo con un error de base de datos apenas se aplique la migración de esta feature —
  el mismo tipo de omisión que ya causó un hallazgo tardío en specs/019 (T007, `LecturaMedidorController::store()`
  necesitó un ajuste no anticipado en el plan original). Mapear los 4 puntos de escritura completos
  por adelantado (research.md) evita repetir ese patrón.
- **Alternatives considered**: Ninguna — es un punto de escritura real, encontrado por búsqueda
  exhaustiva (`grep -rn "'consumo_calculado'"`), no una decisión de diseño.

## Decisión 4: dos vistas pierden una rama ya inalcanzable (Q1:A)

- **Decision**: `locaciones/lecturas/index.blade.php` (líneas 65-69) tiene un `@if
  ($lectura->consumo_calculado === null)` mostrando "sin dato anterior"; `locaciones/recibos/create.blade.php`
  (línea 41) tiene `$lectura !== null && $lectura->consumo_calculado !== null`. Con el accessor
  (Decisión 1) y el criterio de 0 (Decisión 2, Q1:A), `consumo_calculado` nunca vuelve a ser `null`
  mientras exista una `$lectura` — esa rama queda inalcanzable. Se simplifican ambas vistas: la
  primera deja de tener rama "sin dato" para el consumo (siempre muestra un valor); la segunda
  queda como `$lectura !== null` a secas (la única condición que sigue siendo real: si no hay
  lectura de ese periodo en absoluto).
- **Rationale**: Principio de no dejar código muerto — una rama que nunca se ejecuta es más
  confusa que útil, y mantenerla sugeriría (incorrectamente) que el caso "sin dato" todavía puede
  ocurrir. Es exactamente el tipo de simplificación que motivó Q1:A: un solo criterio, sin
  excepciones por pantalla.
- **Alternatives considered**: Dejar las ramas "por las dudas" — descartado; el propio Assumptions
  de spec.md ya deja constancia de que este es un cambio de comportamiento visible e intencional,
  no un caso límite a seguir cubriendo.

## Decisión 5 (descubierta al correr T013): los factories de test bypasean `$fillable`

- **Decision**: Al correr la migración `DROP COLUMN` y la suite completa, aparecieron 9 fallos con
  `SQLSTATE[42703]: Undefined column: consumo_calculado` en 4 archivos de test
  (`LecturaMedidorControllerTest.php`, `RegistroMasivoLecturasControllerTest.php` ×5,
  `ReciboControllerTest.php`, `ServicioGeneracionReciboPeriodoTest.php` ×2) — todos ellos pasaban
  `'consumo_calculado' => N` explícito a `LecturaMedidor::factory()->create([...])` desde
  specs/015/019, antes de que existiera el accessor. Se quitaron las 9 claves explícitas: en
  ningún caso la prueba verificaba ese valor directamente (verificaban `total`, encabezados HTTP, u
  orden cronológico), así que quitarlas no cambia lo que cada prueba prueba.
- **Rationale**: A diferencia de lo asumido en el research original ("mass assignment descarta en
  silencio una clave no fillable"), los factories de Eloquent **no** respetan `$fillable` de la
  misma forma que `Model::create()` normal — construyen la instancia con los atributos ya
  combinados sin pasar por el filtro de asignación masiva, así que una clave que ya no corresponde
  a ninguna columna real termina en el `INSERT` tal cual y PostgreSQL la rechaza. Este hallazgo
  solo se pudo confirmar corriendo la suite completa después de la migración (T013), no por
  búsqueda estática de escritura de producción (research.md original solo mapeó los 4 puntos de
  escritura de código de aplicación, no las factories de test).
- **Alternatives considered**: Ninguna — es una corrección de las pruebas ya escritas, no una
  decisión de diseño nueva.

## Brecha de pruebas a cerrar en tasks.md

- `tests/Feature/LecturaMedidorControllerTest.php:24` (`expect($lectura->consumo_calculado)
  ->toBeNull()`) verifica exactamente el comportamiento que Q1:A cambia a propósito — pasa a
  esperar `'{lectura_actual}.00'` (consumo = lectura actual, 0 como anterior).
- `tests/Feature/LecturaMedidorControllerTest.php:65,104` y
  `tests/Feature/RegistroMasivoLecturasControllerTest.php:69` ya esperaban un valor numérico
  concreto (no `null`) — deben seguir pasando sin cambios una vez que el accessor reproduce la
  misma aritmética.
- `tests/Unit/LecturaMedidorTest.php:25-30` ("...se castean como decimal") prueba hoy un cast de
  columna que deja de existir — se reescribe como una prueba del accessor
  (`'el consumo se calcula a partir de lectura_actual y lectura_anterior'`), agregando también el
  caso sin lectura anterior (que antes no cabía en esa prueba, porque el cast no calculaba nada).
- Ninguna prueba actual verifica que `ImportarLecturasMedidorHistoricas`,
  `LecturaMedidorController::update()` ni `DatabaseSeeder` dejen de escribir la columna — se agrega
  cobertura mínima donde ya existe una prueba de esos flujos (el comando de importación no tiene
  test dedicado hoy; queda fuera de esta feature agregarle uno nuevo, solo se verifica que sigue
  corriendo sin error tras quitarle esa escritura).
