# Contrato: Ver los Recibos Generados de una Locación y Periodo

## `GET recibos.registroMasivo.recibosDelPeriodo`

`/recibos/registro-masivo/{locacion}/recibos?periodo=YYYY-MM`

Busca todos los recibos de `$locacion` para el periodo dado, **de cualquier estado** (a diferencia de
`exclusion-recibos-anulados.md`, aquí se listan también los anulados — este endpoint audita qué existe,
no calcula disponibilidad), ordenados por `created_at` ascendente (orden de emisión).

- **0 recibos**: no debería alcanzarse desde la UI (el botón que enlaza aquí solo se muestra si hay al
  menos uno) — si se accede directo por URL, redirige a `recibos.registroMasivo.index` con ese periodo.
- **1 recibo**: redirige directamente a `recibos.show` de ese recibo.
- **2 o más recibos**: renderiza `recibos/registro-masivo/recibos-del-periodo.blade.php`, una lista simple
  (una fila por recibo: conceptos que cubre, estado, total, enlace "Ver Detalle" a `recibos.show`).

## `recibos/registro-masivo/index.blade.php` y `estado-recibo-locacion.blade.php`

Cada fila de locación agrega la acción "Ver Recibos" (enlace visible, con ícono, no escondido detrás de un
menú) enlazando a esta ruta con el periodo actualmente visible, mostrada siempre que exista al menos un
recibo (de cualquier estado) para esa locación y periodo — independientemente de si también se muestra
"Generar Recibo" (ambas pueden coexistir: una locación con conceptos aún disponibles y al menos un recibo
ya emitido para otro concepto muestra las dos acciones).

`RegistroMasivoRecibosController::datosDelPeriodo()` ya agrupa `$recibosPorLocacion` — se reutiliza esa
misma colección (sin filtrar por estado) para decidir si la fila muestra la acción "Ver Recibos", sin
consultas adicionales por locación.
