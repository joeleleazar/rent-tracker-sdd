# Contrato: Generación de Recibo como Página Propia (sin modal)

## Rutas retiradas

- `GET recibos.registroMasivo.modal` (`/recibos/registro-masivo/{locacion}/modal`)
- `POST recibos.registroMasivo.store` (`/recibos/registro-masivo/{locacion}`)

Junto con `RegistroMasivoRecibosController::modal()`/`store()`,
`resources/views/recibos/registro-masivo/partials/modal-recibo.blade.php`,
`resources/views/recibos/registro-masivo/partials/error-modal-recibo.blade.php`, el contenedor de modal
compartido en `index.blade.php` (specs/023) y `SolicitudGuardarReciboRegistroMasivo`.

## Rutas reutilizadas (sin cambio de firma)

- `GET locaciones.recibos.create` (`/locaciones/{locacion}/recibos/crear?periodo=YYYY-MM`)
- `POST locaciones.recibos.store` (`/locaciones/{locacion}/recibos`)

## Cambio de comportamiento

En `recibos/registro-masivo/index.blade.php` (y su parcial `estado-recibo-locacion.blade.php`), el botón
"Generar Recibo" de cada fila deja de ser:

```html
<button hx-get="{{ route('recibos.registroMasivo.modal', [...]) }}" hx-target="#contenido-modal-recibo" ...>
```

y pasa a ser un enlace normal (sin `hx-get`, navegación completa intencional — es justamente lo que pide
la User Story 2):

```html
<a href="{{ route('locaciones.recibos.create', ['locacion' => $locacion, 'periodo' => $periodo->format('Y-m')]) }}">
```

`ReciboController::create()` ya resuelve `?periodo=` (`resolverPeriodo(request()->query('periodo'))`), así
que la locación y el periodo llegan correctos a la página sin cambios en el controlador para ese punto.

## Página `locaciones/recibos/create.blade.php` — refuerzos (User Story 2, FR-007/FR-008)

Contenido ya presente hoy, sin cambio: contrato activo (o su ausencia), consumo/lectura del periodo,
prorrateo de renta, conceptos disponibles con monto sugerido, conceptos ya cubiertos con enlace a su
recibo (ver también `exclusion-recibos-anulados.md`).

Contenido nuevo en esta página (título/encabezado ya indica locación y periodo — FR-007 ya cumplido por el
`<h2>` existente "Emitir Recibo del Periodo — {{ $locacion->nombre }}" + el selector de periodo visible
justo debajo; no requiere cambio):

- Botón "Guardar Borrador" y estado de autoguardado — ver `borrador-recibo.md`.

## `recibos.show` tras confirmar

Sin cambio: `locaciones.recibos.store` sigue redirigiendo a `recibos.show` con el mensaje de éxito ya
existente, sea cual sea la pantalla desde la que se llegó a la generación (individual o desde el registro
masivo).
