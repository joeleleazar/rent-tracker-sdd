# Research: Panel de Inicio — Estado de Cobranza

Todas las aclaraciones de la spec quedaron resueltas antes de este plan (ver
`checklists/requirements.md`). Las decisiones abiertas son de implementación: cómo consultar los datos sin
romper el objetivo de <2 s y cómo encajar en lo que ya existe.

## §1. No persistir la fecha límite de pago

**Decisión**: derivar la fecha límite en cada request con el servicio existente
`App\Services\ServicioCalculoFechaLimitePago::calcular(Carbon $mes)`, pasándole `$recibo->periodo`. No se
agrega columna ni migración.

**Rationale**: el servicio ya implementa exactamente la regla pedida (último sábado del mes calendario; si el
mes termina en sábado, ese día) y es **una función pura sin consultas**. Evaluarla en PHP para unos cientos
de recibos es de microsegundos; no es el cuello de botella. Persistir la fecha añadiría una migración, un
backfill, y acoplamiento con el camino de escritura de recibos (specs/023 emisión masiva, edición de
periodo) para cero beneficio medible. La spec dejó la persistencia como "opción a evaluar" — se evalúa y se
descarta.

**Alternativas consideradas**:
- Columna `fecha_limite_pago` calculada al emitir/editar el recibo: descartada por lo anterior.
- Expresión SQL en la consulta (`WITH`/`generate_series` para el último sábado): descartada — mueve una
  regla de negocio ya encapsulada en un servicio probado hacia SQL crudo, en contra del Principio I.

## §2. Agregados `withSum` en vez de hidratar `conceptos` y `pagos`

**Decisión**: la consulta base del panel es

```
Recibo::query()
    ->where('estado', '!=', 'anulado')
    ->withSum('conceptos as suma_conceptos', 'monto')
    ->withSum('pagos as suma_pagos', 'monto')
    ->with(['contrato.inquilinos', 'locacion'])
    ->get()
```

y en PHP: `total = (float) monto_renta + (float) suma_conceptos`, `pagado = (float) suma_pagos`,
`saldo = max(0, total - pagado)`. Se filtra `saldo > 0` en PHP y luego se parte en morosos / próximos
vencimientos según la fecha límite.

**Rationale**: `SeguimientoPagosController` (specs/032) hoy hace `->with(['conceptos','pagos'])` y llama
`$recibo->total()` / `montoPagado()`, pero está acotado a **un** periodo (decenas de recibos). El panel
abarca **todos** los periodos con saldo abierto; hidratar dos colecciones hijas por recibo para cientos de
recibos es el riesgo de rendimiento real. `withSum` genera subconsultas escalares y no hidrata colecciones —
una sola consulta, coste plano. La misma colección resultante alimenta morosos, próximos vencimientos,
cartera total por cobrar y el facturado/cobrado del periodo (filtrando por `periodo` en PHP), manteniendo el
panel en ~3–5 consultas.

**Cartera total por cobrar**: `Σ max(0, total_i − pagado_i)` **por recibo**, nunca `Σtotal − Σpagos` global
(un recibo sobrepagado no debe "tapar" la deuda de otro). Se reutiliza el `saldo` ya calculado por fila.

**Alternativas consideradas**:
- `HAVING` en SQL para traer solo recibos con saldo > 0: reduce filas transferidas, pero Eloquent no expone
  `having` sobre `withSum` de forma limpia y obliga a `selectRaw` + `groupBy`. Se deja como escalón de
  optimización documentado si el volumen crece más allá de "cientos".
- Mantener el patrón `with(['conceptos','pagos'])` de specs/032 por consistencia: descartado por el volumen;
  se prioriza el presupuesto de <2 s. El cálculo de negocio sigue siendo idéntico (mismos números).

## §3. IDs de la rama de locaciones para el filtro

**Decisión**: un helper `ServicioJerarquiaLocaciones::idsDeRama(int $locacionId): array<int>` que devuelve el
id dado más todos sus descendientes. Implementación con **`WITH RECURSIVE`** de PostgreSQL sobre
`locaciones (id, locacion_padre_id)`, parametrizada vía `DB::select`, con prueba unitaria.

**Rationale**: `Locacion` es lista de adyacencia (`locacion_padre_id`) y ya tiene `ancestros()` (camino hacia
arriba) pero no descendientes. Un CTE recursivo resuelve la rama completa en una consulta, sin importar la
profundidad. El filtro por rama (FR-019) necesita exactamente ese conjunto de ids para un `whereIn` sobre
`recibos.locacion_id`.

**Alternativas consideradas**:
- Recorrido en PHP desde un mapa `id → padre_id` de todas las locaciones (son pocas centenas): totalmente
  viable y sin SQL crudo; se documenta como alternativa equivalente y aceptable si se prefiere no introducir
  un CTE. Coste: una consulta `Locacion::all(['id','locacion_padre_id'])` + BFS en memoria.
- `ServicioConstruccionArbolLocaciones` (ya existe, arma el árbol para seguimiento/registro masivo): útil
  para poblar el `<select>` del filtro, pero devuelve una estructura de nodos para render, no un set plano de
  ids; se usa para el control del filtro, no para resolver la rama.

**Nota**: el `<select>` del filtro de locación se puebla con las locaciones que pueden encabezar una rama
(galería, piso, sector, pasillo) más los locales; se puede reutilizar el árbol ya existente para mostrarlas
indentadas.

## §4. Definición de "moroso" y partición del listado

**Decisión**: sobre la colección base (§2), para cada recibo con `saldo > 0`:
`fechaLimite = ServicioCalculoFechaLimitePago->calcular($recibo->periodo)`.
- `fechaLimite < hoy` (a medianoche) → **moroso**; `diasDeAtraso = hoy->diffInDays(fechaLimite)` (entero,
  positivo).
- `fechaLimite >= hoy` → **próximo vencimiento**; `diasRestantes = hoy->diffInDays(fechaLimite)`.

`hoy = now()->startOfDay()`. Un recibo con fecha límite == hoy cuenta como próximo vencimiento con 0 días
restantes (FR / Edge Case), no como moroso.

**Tramos de antigüedad**: `1..30 → "1 a 30"`, `31..60`, `61..90`, `>90 → "más de 90"`. Frontera inclusiva
arriba: 30 días de atraso cae en "1 a 30", 31 en "31 a 60". La suma de los cuatro montos es idéntica al
"monto adeudado vencido" (SC-003) porque son una partición del mismo conjunto.

**Rationale**: coincide literalmente con FR-005/FR-009/FR-016. Ordenar por `diasDeAtraso` desc equivale a
ordenar por `fechaLimite` asc — se puede ordenar por la fecha para evitar recalcular.

## §5. Indicadores del periodo y "recaudado este mes"

**Decisión** (resuelve Q1 = C, dos indicadores):
- **Facturado del periodo** = `Σ total_i` sobre los recibos de la colección base cuyo `periodo` es el mes
  calendario de `now()`.
- **Cobrado de recibos del periodo** = `Σ suma_pagos_i` sobre esos mismos recibos (pagos imputados a recibos
  del mes en curso, **sin importar `fecha_pago`**). Numerador de la tasa de cobranza.
- **Tasa de cobranza del periodo** = `cobradoDeRecibosDelPeriodo / facturadoDelPeriodo * 100`; si
  `facturadoDelPeriodo == 0` → mostrar `—` / "sin datos" (guarda de división por cero, FR-030).
- **Recaudado este mes** = consulta aparte:
  `Pago::whereBetween('fecha_pago', [inicioMes, finMes])->whereHas('recibo', fn($q) => $q->where('estado','!=','anulado'))->sum('monto')`.
  Pagos con `fecha_pago` dentro del mes calendario en curso, cualquier periodo del recibo; excluye pagos de
  recibos anulados (FR-004).
- **Cartera total por cobrar** = `Σ saldo_i` (clamp por recibo) sobre **toda** la colección base.

**Rationale**: la colección base ya trae todo lo necesario para 4 de los 5 indicadores sin consultas extra;
solo "recaudado este mes" necesita filtrar `pagos` por `fecha_pago`, que la colección base (agregada) no
permite. `Pago` castea `fecha_pago` a `date`.

## §6. Contratos por vencer

**Decisión** (resuelve Q2 = A): consulta
`Contrato::where('estado', 'activo')->whereBetween('fecha_fin', [hoy, hoy->copy()->addDays(30)])->with(['locacion','inquilinos'])->orderBy('fecha_fin')->get()`,
y en PHP se arman **tres grupos acumulativos**: `<= hoy+7`, `<= hoy+15`, `<= hoy+30` (un contrato a 5 días
aparece en los tres). Cada grupo se renderiza como lista de contratos con enlace a `contratos.show`.

**Rationale**: el enum de `contratos.estado` es `['borrador','activo','vencido','rescindido']`; "vigente" en
el sentido del panel = `'activo'`. `ServicioNotificacionVencimientoContrato` (specs/009) ya usa la lógica de
hitos acumulativos 30/15/7 con `$diasParaVencer > $dias → continue`, pero filtra `estado != 'rescindido'`
(incluye borrador y vencido) y su propósito es enviar correos una sola vez (columnas `notificado_*_en`). El
panel es más estricto (`estado = 'activo'`) y **no** consulta ni escribe esas columnas de notificación — es
solo un recordatorio visual en pantalla. Un contrato con `fecha_fin` pasada no entra (el `whereBetween`
empieza en hoy); FR-032 y US3 AS7 lo confirman.

**Alternativas consideradas**:
- Reutilizar `ServicioNotificacionVencimientoContrato`: su API está orientada a "enviar y marcar", no a
  "listar para mostrar"; acoplar el panel a sus columnas de notificación sería confuso. Se comparte el
  criterio (acumulativo, por `fecha_fin`) pero no el código.
- Grupos excluyentes 0–7 / 8–15 / 16–30: descartado por el usuario (Q2 = A).

## §7. Cableado de la ruta de inicio

**Decisión**: cambiar el cuerpo de `Route::get('/dashboard', ...)` en `routes/web.php` de
`fn () => redirect()->route('locaciones.index')` a `[ControladorPanelInicio::class, 'index']`, conservando el
**nombre de ruta `dashboard`**. El ítem del sidebar que hoy apunta a `route('dashboard')` con el texto
"Locaciones" e ícono `bi-buildings` se reetiqueta a "Inicio" (o "Panel") con un ícono propio (p. ej.
`bi-clipboard-data` o `bi-house-door`, a confirmar en la revisión `impeccable`); el ítem "Gestionar
Locaciones" → `locaciones.index` no cambia.

**Rationale**: `AuthenticatedSessionController` hace `redirect()->intended(route('dashboard'))` y la ruta raíz
`/` redirige a `dashboard`; reutilizar ese nombre hace que el panel sea el destino post-login y de la raíz
**sin tocar** el controlador de sesión ni la ruta `/` (FR-001). El `dashboard` actual es un `redirect()` de
relleno heredado de specs/013, no una pantalla real, así que no se pierde nada.

**Riesgo**: pruebas o vistas existentes que asuman que `dashboard` redirige a `locaciones.index`. Mitigación:
`grep` de `route('dashboard')` y de `assertRedirect(route('dashboard'))` / `->assertRedirect('/locaciones')`
en `tests/` durante la implementación; ajustar las que correspondan (SC-007 exige que ningún camino de
navegación quede roto).

## §8. Filtros con degradación elegante

**Decisión**: un `<form method="GET" action="{{ route('dashboard') }}">` con dos controles —`<select
name="tramo">` (5 opciones: todos / 1-30 / 31-60 / 61-90 / +90) y `<select name="locacion">` (árbol de
locaciones)— que se autoenvía con `hx-get` + `hx-trigger="change"` apuntando a la misma ruta y
`hx-target`/`hx-select` al contenedor del bloque de morosos. Sin JavaScript, el `change` no dispara nada y el
usuario usa un botón "Filtrar" `type="submit"` visible; con `hx-boost` del layout, ese submit ya es
asíncrono.

**Rationale**: es el patrón exacto ya usado en `pagos/seguimiento/index.blade.php` y
`recibos/registro-masivo/index.blade.php` (navegación de periodo con `<form method="GET">` + `hx-get`).
Reusarlo mantiene la coherencia y la degradación (FR-020). El controlador lee `request()->query('tramo')` y
`request()->query('locacion')`, valida contra listas blancas y aplica ambos filtros a la colección de
morosos antes de calcular el resumen (FR-022: las tarjetas se recalculan sobre lo filtrado).

## §9. Verificación

- Unit (`ServicioPanelCobranzaTest`): recibo anulado / pagado / con fecha límite futura NO es moroso; recibo
  no anulado con saldo > 0 y fecha límite pasada SÍ; saldo con pagos ≥ total → 0 y no moroso; días de atraso
  contra "hoy − último sábado del mes del periodo"; recibo con fecha límite == hoy → próximo vencimiento, 0
  días; tramos en 1/30/31/60/61/90/91; Σ tramos == monto adeudado vencido; contrato a 5 días en los 3 grupos,
  contrato a 20 días solo en el de 30, contrato `fecha_fin` ayer en ninguno, contrato `estado != activo`
  excluido; tasa de cobranza con facturado 0 → "sin datos"; "recaudado este mes" ignora pagos de recibos
  anulados y cuenta pagos de periodos anteriores con `fecha_pago` de este mes.
- Feature (`PanelInicioTest`): Master y Administrador → 200 en `dashboard` y ven el panel; invitado →
  redirect a `login`; listados en el orden correcto; los tres estados vacíos; `?tramo=` y `?locacion=`
  combinados devuelven las filas correctas y las tarjetas cuadran con ellas; cada fila enlaza a
  `recibos.show` y cada contrato a `contratos.show`; la respuesta no contiene formularios de registrar pago /
  anular recibo / editar contrato.
- Rendimiento: `quickstart.md` incluye un escenario con ≥ 300 recibos y ≥ 100 contratos sembrados y la
  medición del tiempo de render (< 2 s) y del número de consultas.
- `php artisan test` completo en verde + `npm run build` sin errores + revisión `impeccable` de la vista y el
  sidebar.
