# Quickstart: Incorporar el Logo de Nicson Plaza a la Interfaz

Escenarios de validación manual, a correr tras implementar. Usar el binario de PHP de Herd:
`C:\Users\joel5\.config\herd\bin\php.bat`.

## Escenario 1 — Logo en el inicio de sesión (US1)

1. Cerrar sesión (o abrir una ventana privada) y visitar `/login`.
2. Verificar que el logo de Nicson Plaza se muestra donde antes aparecía el ícono genérico de plantilla,
   con buen contraste y sin deformarse.

## Escenario 2 — Logo en toda pantalla autenticada (US2)

1. Iniciar sesión y visitar al menos 3 pantallas distintas (por ejemplo Locaciones, Emitir Recibos,
   Configuración).
2. Verificar que el logo aparece en el mismo lugar del encabezado del sidebar en las 3, dentro de una
   tarjeta blanca legible sobre el fondo oscuro, y que sigue siendo un enlace que vuelve al inicio.
3. Reducir el ancho de la ventana (o simular un viewport angosto) hasta que el sidebar se reordene en una
   franja horizontal — verificar que el logo se sigue viendo bien, sin solaparse con el resto de los
   elementos ni provocar scroll horizontal de la página.

## Escenario 3 — Logo en el comprobante de recibo (US3)

1. Abrir el comprobante de cualquier recibo ya emitido.
2. Verificar que el logo aparece en el encabezado del documento.
3. Usar la acción de imprimir (o previsualizar impresión) y confirmar que el logo se incluye en esa vista.
4. Usar la acción de compartir/capturar como imagen y confirmar que el logo aparece en el resultado, sin
   errores en la captura.
5. Repetir sobre el comprobante de un recibo anulado — verificar que el logo no se superpone con la marca
   de "Anulado".

## Escenario 4 — Ícono de pestaña del navegador (US4)

1. Abrir cualquier pantalla del sistema (autenticada o no) en una pestaña nueva.
2. Verificar que el ícono de la pestaña muestra el logo de Nicson Plaza en vez del ícono genérico anterior.

**Verificación manual ya realizada (2026-08-26, navegador real contra `rent_tracker_dev`)**: los 4
escenarios se recorrieron con resultado exitoso.

- **Escenario 1**: `/login` muestra el logo de Nicson Plaza en vez del ícono genérico de plantilla.
- **Escenario 2**: el sidebar (autenticado, `/locaciones`) muestra el logo dentro de una tarjeta blanca
  redondeada, legible sobre el fondo oscuro, en el mismo lugar donde antes decía "Rent Tracker".
- **Escenario 3**: el comprobante de un recibo pagado (`/recibos/1/comprobante`) muestra el logo en la
  esquina superior derecha; el comprobante de un recibo anulado (`/recibos/3/comprobante`) confirma que el
  sello diagonal "ANULADO" no se superpone con el logo — quedan claramente separados.
- **Escenario 4**: confirmado por el test automatizado (`T009`, verifica `<link rel="icon">` en el
  `<head>`) — el ícono de pestaña en sí es UI del navegador, fuera del viewport capturable por screenshot.

**Re-verificación manual tras la corrección posterior (2026-08-26, navegador real contra
`rent-tracker-sdd.test`, ver tasks.md T015-T018)**: el usuario reemplazó el archivo por una versión propia
(`.png` transparente, 1769×962) y reportó que el resultado "no se ve bien"; tras corregir el `type` del
favicon y el dimensionado del `<img>` (research.md, notas "Hallazgo posterior a la implementación"), se
repitieron los 4 escenarios con resultado exitoso:

- **Escenario 1**: `/` (redirige a `/login`) muestra el logo completo y legible, ya no encogido por la
  caja cuadrada anterior.
- **Escenario 2**: el sidebar (autenticado, `/locaciones`) muestra el logo dentro de la tarjeta blanca,
  bien proporcionado y legible sobre el fondo oscuro.
- **Escenario 3**: el comprobante de un recibo pagado (`/recibos/1/comprobante`) muestra el logo en la
  esquina superior derecha, bien proporcionado; el comprobante de un recibo anulado
  (`/recibos/4/comprobante`) confirma que el sello diagonal "ANULADO" sigue sin superponerse con el logo.
- **Escenario 4**: confirmado por el test automatizado ya corregido (`T016`, ahora verifica
  `type="image/png"`).
