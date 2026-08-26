# Research: Corregir Cobertura de Conceptos y Edición de Renta en Recibos

## Decisión 1 — Reincorporar Renta a la lista de conceptos ofrecidos al editar

**Decisión**: En `ReciboController::edit()`, además de `$conceptosDisponibles->concat($recibo->conceptos->pluck('conceptoGastoFijo'))`,
agregar explícitamente el concepto "Renta" del catálogo a esa colección cuando `$recibo->monto_renta !== null`
(es decir, cuando este recibo ya la incluye), antes de aplicar `unique('id')->sortBy('orden')->values()`.

**Rationale**: Causa raíz confirmada por inspección directa del código (`app/Http/Controllers/ReciboController.php`
líneas 134-148): `conceptosDisponibles()` calcula qué conceptos NO están ya cubiertos por ningún recibo
vigente del periodo — y cuando el recibo que se está editando ya cubre Renta, `conceptosDisponibles()`
correctamente la excluye (ya está cubierta, por este mismo recibo). El paso siguiente reincorpora "los
conceptos que este recibo ya tiene" leyendo `$recibo->conceptos` — pero esa es la relación `recibo_conceptos`,
y Renta nunca vive ahí: se guarda directamente como `monto_renta` en la fila del propio recibo (decisión de
diseño de specs/005, reafirmada en specs/024 research.md Decisión 2 — "Renta nunca es un concepto dinámico
más"). El resultado es que Renta desaparece de la lista sin ninguna forma de volver a agregarla desde la
vista de edición. La vista (`edit.blade.php`) ya está preparada para renderizarla correctamente — usa
`$concepto->esRenta()` exactamente igual que el resto del bucle — así que el fix es enteramente del lado
del controlador, sin cambios de vista.

**Alternativas consideradas**: Cambiar `edit.blade.php` para tratar a Renta como un campo separado, fuera
del bucle de `$conceptosDisponibles` (como hacía el diseño anterior a specs/024) — rechazada porque
reintroduce la duplicación de lógica que specs/024 eliminó deliberadamente, y porque el bucle actual ya
funciona correctamente para Renta en los demás casos (cuando el recibo NO la incluye todavía) — el defecto
está puntualmente en qué se le entrega al bucle, no en el bucle mismo.

## Decisión 2 — Verificación reforzada de que ningún concepto se muestra cubierto sin un recibo vigente

**Decisión**: Agregar pruebas Feature explícitas (no solo confiar en las ya existentes de specs/026) que
reproduzcan exactamente el escenario reportado: un concepto con un valor de referencia configurado en el
contrato mostrado en `/recibos/registro-masivo` sin ningún recibo vigente que lo incluya, y confirmar que
se muestra disponible, no cubierto. Se repite el mismo chequeo específicamente para Renta.

**Rationale**: Verificación en vivo contra el entorno de desarrollo real (`curl` autenticado a
`/recibos/registro-masivo` para agosto y septiembre 2026) confirmó que, con el código actual, "Internet" ya
se renderiza con la clase `badge bg-light text-dark border` (disponible) y no con `badge bg-secondary`
(cubierto) — es decir, el invariante ya se cumple con los datos actuales del entorno. No se encontró una
causa de código que reproduzca el defecto reportado. Es posible que el reporte del usuario refleje un
estado transitorio de una sesión de pruebas anterior (un recibo que en ese momento sí cubría "Internet" y
luego fue anulado como parte de esa misma verificación). De todas formas, se agrega cobertura de prueba
explícita para este caso puntual: el costo es bajo y cierra cualquier duda residual sobre el invariante ya
exigido por specs/026, dejando además una regresión permanente si algo lo rompiera en el futuro.

**Alternativas consideradas**: No agregar nada, apoyándose solo en que specs/026 ya prueba el caso general
(un recibo anulado deja de cubrir sus conceptos) — rechazada porque el usuario reportó un síntoma concreto
que merece su propia prueba con ese nombre y esos datos exactos, para que quede documentado que se
investigó y no solo se asumió que ya estaba resuelto.
