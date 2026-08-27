# Research: Comprobante de Pago Firmado y Evidencia de Pago

## Decisión 1 — El comprobante de pago se construye con Bootstrap 5 real, no con el CSS propio del comprobante de recibo

**Decisión**: `resources/views/pagos/comprobante.blade.php` es una vista standalone (con su propio `<head>`,
sin el layout de sidebar compartido — mismo criterio de navegación que ya usa "Ver Comprobante" del recibo,
`hx-boost="false"`), pero a diferencia de `locaciones/recibos/comprobante.blade.php` (specs/007/031),
**sí** carga la hoja de estilos real de Bootstrap 5 vía Vite y usa sus componentes (`card`, `btn`,
utilidades `d-print-none`/`d-print-block`) en vez de duplicar CSS hexadecimal propio.

**Rationale**: La única razón por la que el comprobante del recibo evita Bootstrap/Tailwind es que
html2canvas 1.4.x aborta la captura si encuentra `oklch()` en cualquier regla de la página, algo que solo
importa porque esa vista se captura como imagen para compartir por WhatsApp (specs/007 US2). Esta nueva
feature no pide compartir el comprobante de pago por WhatsApp ni capturarlo como imagen — solo imprimirlo
(spec.md FR-004) — así que esa restricción no aplica, y el Principio VI de la constitución (Bootstrap 5
como sistema de componentes oficial y único) se cumple sin necesitar ninguna excepción, a diferencia de su
vecino de dominio.

**Alternativas consideradas**: Copiar el mismo patrón de CSS propio del comprobante del recibo "por
consistencia visual entre comprobantes" — descartada porque introduciría una excepción a Bootstrap sin
ninguna razón técnica real que la justifique (a diferencia del caso original), duplicando CSS que Bootstrap
ya resuelve.

## Decisión 2 — La evidencia se guarda como columnas directas en `pagos`, no como una tabla `documentos_pago` aparte

**Decisión**: 3 columnas nuevas y nullable en `pagos`: `evidencia_ruta`, `evidencia_nombre_archivo`,
`evidencia_tipo` (`pdf`|`imagen`). El archivo físico se guarda en el disco `local` de Laravel, carpeta
`pagos/{id}`, mismo mecanismo (`Storage::disk('local')`) que ya usa `DocumentoContratoController` para los
documentos de contrato.

**Rationale**: `documentos_contrato` es una tabla aparte porque un contrato admite **varios** documentos
(hasta 10 fotos, o un PDF) — una relación 1 a N real. Spec.md ya resuelve explícitamente que un pago admite
**una única** evidencia a la vez (Assumptions: "subir una nueva reemplaza la anterior") — una relación 1 a 1
que no necesita una tabla propia; agregar 3 columnas nullable a `pagos` es la forma más simple y directa de
modelar exactamente esa regla, sin sobre-construir una tabla que nunca tendría más de una fila por pago.

**Alternativas consideradas**: Reutilizar el modelo `DocumentoContrato` (generalizándolo a "documento
polimórfico") para que también sirva a `Pago` — descartada por ser una refactorización mayor de un modelo ya
en uso y probado, para resolver un caso que en realidad es más simple (1 a 1, no 1 a N) que el que ese
modelo fue diseñado para cubrir.

## Decisión 3 — Reemplazar evidencia borra el archivo anterior del disco en la misma transacción

**Decisión**: `EvidenciaPagoController::store()` (usada tanto para la primera subida como para reemplazar
una ya existente) borra el archivo físico anterior del disco (si había uno) y guarda el nuevo, todo dentro
de una única `DB::transaction`.

**Rationale**: Es el mismo patrón que ya usa `DocumentoContratoController::destroy()` (borrar del disco +
actualizar la fila, atómico) — spec.md FR-007 exige explícitamente que subir una evidencia nueva reemplace
la anterior, y dejar el archivo viejo huérfano en el disco (sin fila que lo referencie) sería una fuga de
almacenamiento sin ningún beneficio, dado que spec.md (Assumptions) ya descarta llevar un historial de
varios archivos por pago.

**Alternativas consideradas**: Conservar los archivos anteriores en el disco por si acaso, aunque ya no
estén referenciados — descartada por no aportar ningún valor (spec.md es explícito: no hay historial de
evidencias) y acumular archivos huérfanos indefinidamente.

## Decisión 4 — El comprobante de pago se genera al vuelo, sin persistir un "snapshot" del avance

**Decisión**: `PagoReciboController::comprobante()` calcula el acumulado pagado y el saldo pendiente del
recibo en el momento de la solicitud (`$recibo->montoPagado()`/`saldoPendiente()`, ya existentes desde
specs/032), sin guardar esos valores como una copia fija asociada al pago.

**Rationale**: spec.md (Assumptions) ya resuelve esto explícitamente: "si se abre de nuevo más adelante,
refleja el estado más reciente del recibo" — es el mismo criterio que ya rige el comprobante del recibo
completo (specs/031), que tampoco persiste un snapshot. El desfase entre lo impreso/firmado y lo que el
sistema muestra después (si se edita un pago posterior) es un caso ya cubierto por un Edge Case de spec.md,
resuelto explícitamente a favor de no intentar sincronizar retroactivamente un documento físico ya
entregado.

**Alternativas consideradas**: Persistir el acumulado y el saldo pendiente como columnas del pago en el
momento de exportarlo — descartada por contradecir directamente la Assumption ya resuelta en spec.md, y por
duplicar un dato ya derivable de `montoPagado()`/`saldoPendiente()`.

## Decisión 5 — Tipos y tamaño de archivo admitidos para la evidencia

**Decisión**: `SolicitudSubirEvidenciaPago` acepta un único campo `archivo`, con `mimes:pdf,jpg,jpeg,png` y
`max:10240` (10 MB) — un límite intermedio entre el ya usado para PDF (15 MB) e imágenes (5 MB) de
`documentos_contrato`, ya que aquí ambos tipos comparten el mismo límite en vez de tener uno distinto cada
uno.

**Rationale**: spec.md (Assumptions) ya fija el criterio de tipos admitidos (imagen o PDF, "siguiendo el
mismo criterio ya usado en el sistema para adjuntar documentos") sin fijar un límite de tamaño exacto —
10 MB es un punto intermedio razonable entre los dos límites ya usados en `documentos_contrato`,
suficiente para una foto de celular o un PDF escaneado de una página sin abrir la puerta a archivos
desproporcionados.

**Alternativas consideradas**: Replicar exactamente los límites separados de `documentos_contrato` (15 MB
PDF / 5 MB imagen) — descartada por ser innecesariamente compleja para un único campo de archivo que acepta
ambos tipos indistintamente, a diferencia de `documentos_contrato`, que sí distingue explícitamente entre
subir un PDF o subir fotos como flujos separados.
