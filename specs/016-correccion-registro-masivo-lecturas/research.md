# Research: Corrección de Lectura Previa y Autoguardado en Registro Masivo

## Contexto

El Technical Context del plan no dejó ningún `NEEDS CLARIFICATION` de stack — esta feature
reutiliza en su totalidad la pila y las decisiones ya tomadas en `specs/015/research.md`. Lo que sí
exige investigación, por Assumptions de `spec.md` ("el diagnóstico de la causa raíz... se
determina durante la fase de planificación/implementación"), es entender **por qué** dos
comportamientos ya implementados y ya probados (18/18 pruebas en verde) no se sostienen en uso
real. Esta investigación documenta lo revisado, lo descartado y el protocolo de reproducción que
debe ejecutarse como primer paso de implementación antes de escribir el fix.

**Nota de entorno**: el PHP por defecto de PATH en esta máquina es 8.0.30 (XAMPP), que no cumple
el mínimo `>= 8.4.1` de `composer.lock`. Todas las verificaciones de esta investigación (incluida
`php artisan test`) se corrieron con el binario de Herd
(`C:\Users\joel5\.config\herd\bin\php.bat`), que trae PHP 8.4.12. Cualquier ejecución futura de
`artisan`/`pest` en esta máquina debe usar ese binario explícitamente.

## Hallazgo H1: La prueba de "lectura anterior" no puede detectar una fila cruzada

- **Lo revisado**: `tests/Feature/RegistroMasivoLecturasControllerTest.php:95-110` — la única
  prueba de FR-006 crea **una** locación con lectura previa y otra sin ninguna, y afirma
  `assertSee('1250.00')` sobre la respuesta completa. `assertSee` busca la cadena en todo el HTML
  de la página, sin verificar en qué fila o celda aparece.
- **Consecuencia**: si existiera hoy (o se introdujera mañana) un defecto que muestre la lectura
  anterior de la locación A en la fila de la locación B, o la de un periodo distinto, esta prueba
  seguiría pasando siempre que el número apareciera en algún lugar de la página. Es exactamente el
  síntoma que describe la User Story 1 del spec ("...ni el de otra locación o periodo").
- **Revisión del código de producción**: `RegistroMasivoLecturasController::datosDelPeriodo()`
  construye `$lecturasAnteriores` con una sola consulta (`whereIn` + `orderByDesc('periodo')` +
  `unique('locacion_id')`) y `fila-registro-masivo.blade.php` la indexa por
  `$lecturasAnteriores->get($locacion->id)` dentro del mismo scope de `$locacion` que se está
  iterando. Se verificó en aislado (fuera de la app, con datos sintéticos de dos locaciones y
  periodos no consecutivos) que `sortByDesc('periodo')->unique('locacion_id')` sí conserva, para
  cada locación, su propio periodo máximo anterior al seleccionado — no se encontró una
  inconsistencia lógica reproducible por lectura estática.
- **Decisión**: no se puede descartar ni confirmar el defecto solo con lectura de código. La
  prueba débil (H1) es en sí misma parte del problema — se reemplaza por una prueba con **múltiples
  locaciones con distintos valores anteriores simultáneos**, verificando cada valor contra el
  `data-lectura-anterior` de su propia celda (`#campo-lectura-{id}`), más los tres escenarios del
  spec no cubiertos hoy: varios periodos anteriores no consecutivos (toma el más reciente), cambio
  de periodo seleccionado (recalcula), y cruce de límite de año.
- **Alternatives considered**: asumir una causa concreta (ej. "es un bug de `unique()`") y
  parchear sin reproducir — descartado porque la lectura estática no sostiene esa hipótesis y
  arriesga un fix que no toca el defecto real.

## Hallazgo H2: Las pruebas de autoguardado nunca pasan por el disparador real

- **Lo revisado**: las tres pruebas de borrador (`tests/Feature/...:112-166`) llaman
  `$this->post(route('lecturas.registroMasivo.borrador'), [...])` directamente — ejercitan el
  controlador y la tabla `borradores_lectura_medidor` correctamente, pero **nunca** pasan por el
  elemento `<div id="autoguardado-borrador" hx-trigger="every 120s" hx-include="#formulario-
  registro-masivo">` de `index.blade.php`, que es la pieza que specs/016 reporta como defectuosa.
  Un feature test de Laravel no ejecuta JavaScript ni el runtime de htmx en el navegador; no puede
  detectar si el `hx-trigger` deja de dispararse, si `hx-include` deja de serializar los campos
  `lecturas[{id}][lectura_actual]`, o si el ciclo deja de repetirse.
- **Revisión del código de producción**: el div de autoguardado, su `hx-include` y el filtro de
  `resources/js/htmx.js` (que ignora el tratamiento visual de "Guardando…" para disparadores que no
  son `<form>`) coinciden exactamente con las Decisiones 4 y 5 de `specs/015/research.md`. No se
  encontró ninguna discrepancia entre lo documentado como diseño original y lo implementado.
  Tampoco hay dependencias nuevas ni configuración de htmx (`htmx.config`) que deshabilite el
  polling.
- **Decisión**: dado que no hay Dusk/Playwright en el proyecto (`composer.json` solo trae
  `pestphp/pest`), la verificación de que el temporizador realmente se dispara y persiste en
  ciclos sucesivos en un navegador real **debe hacerse manualmente** siguiendo el protocolo de
  `quickstart.md` como paso previo a cualquier cambio de código. En paralelo, se agrega una prueba
  de "contrato HTML" (Pest, sin navegador) que falla si `index.blade.php` deja de emitir
  `hx-trigger="every 120s"` o `hx-include="#formulario-registro-masivo"` exactamente — no prueba
  que el navegador ejecute el polling, pero sí evita que una futura edición rompa el cableado sin
  que ninguna prueba lo note (que es, en esencia, cómo specs/016 llegó a existir).
- **Alternatives considered**: instalar Laravel Dusk para probar el polling de extremo a extremo —
  descartado por ahora como fuera de alcance de esta corrección puntual (agregar una dependencia
  nueva de testing no está entre los FR de spec.md); queda como mejora futura si se repite este
  tipo de defecto.

## Protocolo de reproducción (primer paso de implementación, antes del fix)

1. **Lectura anterior**: con al menos dos locaciones alquilables, registrar lecturas para 2-3
   periodos no consecutivos por locación (ej. locación A: mayo y julio; locación B: junio).
   Abrir `/lecturas/registro-masivo?periodo=2026-08` y comparar, fila por fila, el valor mostrado
   en "Lectura Periodo Anterior" contra el valor real en la tabla `lecturas_medidor` para esa
   locación específica. Repetir cambiando el selector de periodo a un mes que cruce de enero a
   diciembre del año anterior.
2. **Autoguardado**: abrir la pantalla, completar 2-3 filas sin guardar, abrir la pestaña de Red
   del navegador y confirmar que una petición `POST` a la ruta de borrador ocurre a los ~120s con
   el payload esperado (`lecturas[...][lectura_actual]`, `periodo`) y responde 200. Esperar un
   segundo ciclo (~240s totales) y confirmar que el borrador en base de datos refleja el valor más
   reciente. Cerrar y reabrir la pantalla para el mismo periodo y confirmar que los campos se
   prellenan solos.
3. Documentar en la tarea de implementación correspondiente (`tasks.md`) el resultado exacto de
   cada paso (qué se vio vs. qué se esperaba) antes de tocar el código de producción — si el
   defecto no reproduce con este protocolo, el hallazgo en sí (junto con las pruebas endurecidas de
   H1/H2) puede ser la corrección completa.
