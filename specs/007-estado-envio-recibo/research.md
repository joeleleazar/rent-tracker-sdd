# Research: Estado de Recibos y Envío por WhatsApp o Impresión

**Feature**: `007-estado-envio-recibo` | **Date**: 2026-08-20

## 1. Generación de la imagen del recibo: client-side vs. servidor

**Decision**: La imagen del recibo se genera en el navegador del Administrador mediante la librería `html2canvas` (npm, MIT, empaquetada por Vite en el build de producción, sin cargarse desde un CDN externo), capturando la misma vista Blade `recibos/comprobante.blade.php` que se usa para impresión. El flujo es: el Administrador presiona "Enviar por WhatsApp" → JS captura el DOM del comprobante a un `canvas` → se convierte a `Blob` (`canvas.toBlob`) → se invoca `navigator.share({ files: [...] })` (Web Share API de nivel 2, con soporte de archivos en navegadores Chromium/Android modernos) para que el propio Administrador elija WhatsApp u otra app instalada y el destinatario. El servidor Laravel no genera, almacena ni transmite ninguna imagen — solo sirve el HTML del comprobante.

**Rationale**: Generar imágenes de forma confiable en el servidor requeriría extensiones PHP como Imagick o GD con soporte de renderizado de texto/layout complejo, o un servicio headless de captura de pantalla (ej. Puppeteer/Chromium sin interfaz), ninguno de los cuales está garantizado en un entorno de shared hosting (`specs/002-gestion-contratos/research.md` §2 ya estableció esta misma restricción para evitar Docker/procesos persistentes). Además, la especificación exige explícitamente que el sistema NO almacene ni gestione números de teléfono ni credenciales de WhatsApp (A-003) y que se apoye en "el mecanismo nativo de compartir del dispositivo o navegador" (FR-007) — la Web Share API con archivos es exactamente ese mecanismo nativo, y delega en el propio sistema operativo/navegador la decisión de qué apps ofrecer (WhatsApp, correo, Bluetooth, etc.), sin que el servidor participe del envío.

**Alternatives considered**:
- Generar la imagen en el servidor con Imagick/GD y servirla como descarga: rechazado, además de la incertidumbre de disponibilidad de Imagick en shared hosting, requeriría reimplementar en PHP el mismo layout que ya existe en Blade/CSS, duplicando el diseño en dos tecnologías distintas.
- Integración directa con la API de WhatsApp Business Cloud API: rechazado explícitamente por la especificación (A-003: "no requiere que el sistema almacene ni gestione credenciales de una cuenta de WhatsApp Business").
- Generar un PDF del recibo con `barryvdh/laravel-dompdf` y compartir el PDF en vez de una imagen: rechazado como mecanismo principal porque la especificación pide explícitamente una "imagen" (FR-006), no un PDF; se documenta como alternativa razonable si en el futuro se requiere adjuntar el recibo por correo electrónico en vez de WhatsApp.

## 2. Vista de impresión: CSS de impresión nativo vs. generación de PDF en servidor

**Decision**: La "vista de impresión" (FR-008) es la misma vista Blade `recibos/comprobante.blade.php` con una hoja de estilos `@media print` dedicada (oculta botones/navegación, ajusta márgenes y tamaños de fuente para papel), invocada mediante `window.print()` del propio navegador del Administrador. No se genera un archivo PDF en el servidor.

**Rationale**: La Asunción A-002 de la especificación confirma que "la imagen generada del recibo reutiliza la misma información y formato que la vista de impresión", lo que permite una única vista Blade para ambos casos de uso (impresión e imagen), reduciendo duplicación. `window.print()` con CSS de impresión es soportado nativamente por todos los navegadores modernos sin dependencias adicionales, consistente con el enfoque de minimizar dependencias de servidor no garantizadas en shared hosting.

**Alternatives considered**:
- `barryvdh/laravel-dompdf` para generar un PDF descargable: viable técnicamente (es una librería PHP pura, sin binarios externos, por lo que sí funcionaría en shared hosting), pero rechazado como mecanismo principal porque la especificación no pide un archivo descargable sino "una vista de impresión legible" (FR-008), y agregar una dependencia adicional no es necesario cuando el navegador ya resuelve el caso de uso.

## 3. Reglas de transición de estado y limpieza de fechas (FR-002 a FR-005)

**Decision**: `ServicioCambioEstadoRecibo::cambiar(Recibo $recibo, string $nuevoEstado, bool $confirmado): void`, ejecutado dentro de `DB::transaction`: (a) si `$nuevoEstado === 'anulado'` o `$recibo->estado === 'anulado'` (es decir, la transición es hacia o desde "anulado"), exige `$confirmado === true` o lanza una excepción de dominio (`CambioEstadoReciboRequiereConfirmacionException`) que el controlador traduce en la respuesta 422 con el modal de confirmación; (b) al entrar en `pagado`, asigna `fecha_pago = now()` y limpia `fecha_anulacion = null`; (c) al entrar en `anulado`, asigna `fecha_anulacion = now()` y limpia `fecha_pago = null`; (d) al entrar en `pendiente` (reversión), limpia ambas fechas.

**Rationale**: Centralizar la regla en un Service (en vez de un cast/mutator del modelo) permite testear unitariamente cada combinación de transición sin pasar por HTTP, y mantiene el modelo `Recibo` como un contenedor de datos simple, consistente con el patrón ya usado en `ServicioValidacionSolapamientoContrato` (specs/002) y `ServicioAsociacionRepresentantesContrato` (specs/003).

**Alternatives considered**:
- Máquina de estados formal con paquete de terceros (ej. `spatie/laravel-model-states`): rechazado por sobre-ingeniería — la especificación exige explícitamente transiciones libres sin restricción de secuencia (FR-005), lo cual no necesita un grafo de estados formal, solo una regla de confirmación y limpieza de fechas.

## 4. Ausencia de roles/permisos

**Decision**: Consistente con `specs/001-006`, las rutas de esta feature solo verifican `middleware(['auth'])`.

**Rationale**: Ver `specs/004-condiciones-contrato-recibo/research.md` §5 — ninguna especificación previa introdujo un sistema de roles.

**Alternatives considered**: Ninguna nueva — decisión ya documentada y reutilizada.

## 5. Framework de pruebas

**Decision**: Pest, consistente con `specs/001-006`.

**Rationale**: Ya adoptado por el proyecto.

**Alternatives considered**: Ninguna — decisión ya tomada a nivel de proyecto.
