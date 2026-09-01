# Contrato — Cobro por QR

Todas las rutas bajo `['auth','cuenta.activa']` (Master y Administrador). Prefijo `/cobro`.

## QR en el comprobante

`ServicioCodigoQrRecibo::dataUri(Recibo $recibo): string` → `data:image/png;base64,…` de un QR que
codifica `URL::signedRoute('cobro.recibo', $recibo)` (URL absoluta, sin expiración).
`resources/views/locaciones/recibos/comprobante.blade.php` lo embebe como
`<img alt="Código para registrar el pago" src="{{ $codigoQrDataUri }}" width="96" height="96">` en la
esquina inferior del `#comprobante-recibo`, con leyenda pequeña. Se imprime (`@media print`).

## `GET /cobro` → `cobro.index`

Vista de escaneo:

- Bloque de **cámara** (`<div id="lector-qr">`) activado por `resources/js/cobro-qr.js` con
  `html5-qrcode`; al decodificar un texto, si es una URL de `cobro.recibo` de este host, navega a ella.
- Bloque de **ingreso manual** siempre visible: `<form method="GET" action="/cobro/buscar">` con
  `<input name="numero" inputmode="numeric">` → botón "Buscar recibo".
- Si no hay cámara / permiso denegado / `html5-qrcode` ausente → solo se muestra el ingreso manual (sin
  error ruidoso, FR-026). Requiere JS solo la cámara.

## `GET /cobro/buscar?numero=N` → `cobro.buscar`

- `numero` no numérico o sin `Recibo` → `302` de vuelta a `cobro.index` con
  `withErrors(['numero' => 'No se encontró un recibo con ese número.'])`, foco en el campo.
- `Recibo` encontrado → `302` a `cobro.recibo` **firmada** para ese recibo (se reusa el mismo destino
  que el QR, así el resto del flujo es idéntico).

## `GET /cobro/recibo/{recibo}` → `cobro.recibo` (middleware `signed`)

- Firma inválida/adulterada → `403` (página con "El enlace no es válido. Escanee de nuevo o ingrese el
  número.") (FR-030).
- Firma válida:
  - `recibo.estado == 'anulado'` → vista `cobro/recibo` en modo **aviso**: "Este recibo está anulado."
  - `recibo.saldoPendiente() == 0` → modo **aviso**: "Este recibo ya está saldado."
  - en otro caso → modo **formulario rápido**:
    - resumen: local (ruta jerárquica), periodo, `total()`, `montoPagado()`, `saldoPendiente()`
    - `<form method="POST" action="/cobro/recibo/{recibo}/pago">`:
      `monto` (`input-group` `S/`, default = saldo pendiente), `fecha_pago` (default hoy),
      `medio_pago` (`<select>`: Efectivo / Transferencia / Depósito / Yape-Plin / Otro — opcional),
      `evidencia` (`<input type="file">` opcional, imagen o PDF)

## `POST /cobro/recibo/{recibo}/pago` → `cobro.pago.store` (middleware `signed` no; `auth` sí)

Form Request `SolicitudRegistrarCobroRapido`: `monto` `required numeric gt:0`, `fecha_pago` `required
date before_or_equal:today`, `medio_pago` `nullable string max:60`, `evidencia` `nullable file mimes:jpg,jpeg,png,pdf max:5120`.

Comportamiento:

1. `ServicioGestionPagosRecibo::registrar($recibo, ['monto','fecha_pago','medio_pago'], auth()->id())`
   (misma validación de saldo/anulado/monto que `recibos/show`; excepciones → `back()->withErrors`).
2. Si viene `evidencia` → se reutiliza la lógica de `EvidenciaPagoController::store` sobre el `Pago`
   recién creado.
3. `302` a `cobro.recibo` firmada del mismo recibo con `session('mensaje')` efímero "Pago registrado
   correctamente." — la vista vuelve a mostrar el resumen actualizado (o el aviso "ya está saldado" si
   se completó).

## Menú y panel

- `components/layouts/app-bootstrap.blade.php`: ítem `Cobro por QR` con `bi-qr-code-scan`, `active` en
  `request()->routeIs('cobro.*')`, ubicado tras "Registro de Pagos".
- `panel/partials/acceso-cobro-qr.blade.php`: `card` con ícono, título, texto breve y botón/enlace a
  `cobro.index`; incluida al inicio de `panel/inicio.blade.php`.
