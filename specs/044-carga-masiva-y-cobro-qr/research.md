# Research — Carga Masiva por Plantilla y Cobro por QR

## Decisión 1 — Formato y librería de plantilla / importación

- **Decisión**: plantilla `.xlsx` generada con `maatwebsite/excel` 4.0.2 (`FromCollection` +
  `WithHeadings`, igual que `ExportacionRegistroMasivoLecturas`). La importación acepta `.xlsx` y `.csv`
  y se parsea con `Excel::toCollection(new ImportacionXxxImport, $archivo)` usando `WithHeadingRow` para
  mapear por nombre de columna, no por posición.
- **Rationale**: la librería ya está instalada y probada en el proyecto; `WithHeadingRow` tolera
  reordenamiento de columnas y hace explícito el rechazo cuando falta una columna esperada (FR-010).
- **Alternativas**: `ToModel` (acopla el parseo al upsert y no permite vista previa editable previa);
  `league/csv` directo (no cubre xlsx); PhpSpreadsheet a mano (reinventa lo que la librería ya da).

## Decisión 2 — La vista previa editable no se persiste

- **Decisión**: `previsualizarImportacion()` recibe el archivo, lo parsea, valida fila por fila y
  devuelve un parcial htmx con una `<table>` de inputs (un `<input type="hidden">` por `local_id` y
  campos editables por columna). `confirmarImportacion()` recibe **esos inputs del formulario**, no el
  archivo — el archivo no se guarda en servidor ni en sesión.
- **Rationale**: FR-013 lo pide explícitamente; evita colisión con el autoguardado de borrador de la
  grilla manual (que sí es server-side); mantiene el flujo sin estado y idempotente.
- **Alternativas**: guardar el archivo temporal + token en sesión (más piezas, expira, hay que limpiar);
  reusar `borradores_lectura_medidor` (mezcla dos mecanismos con semántica distinta).
- **Degradación**: sin JS la vista previa se renderiza igual (es HTML), pero la re-edición de celdas con
  recálculo de total sugerido y el marcado dinámico de filas requieren JS; se muestra un aviso.

## Decisión 3 — Semántica de `upsert` por `(locacion_id, periodo)`

- **Lecturas**: `lecturas_medidor` tiene única `(locacion_id, periodo)`. La confirmación hace, dentro de
  una `DB::transaction`, `LecturaMedidor::updateOrCreate(['locacion_id','periodo'], [...])` por cada
  fila válida. `lectura_anterior` se resuelve como hoy (última lectura real anterior; `null` si no hay).
  `total` se calcula `round(consumo * tarifaGlobalVigente, 2)` salvo que la fila traiga un `total`
  numérico explícito (paralelo a specs/019).
- **Recibos**: `recibos` **no** tiene única `(locacion_id, periodo)` (specs/023 la quitó para permitir
  varios recibos por locación/periodo con conceptos disjuntos). Para la carga masiva se asume **un
  recibo por locación/periodo** (el caso normal): la confirmación busca el `Recibo` **vigente** de esa
  locación/periodo; si hay exactamente uno lo **actualiza** (renta, luz vía `lectura_medidor_id`,
  `recibo_conceptos` reemplazados) reutilizando `ServicioGeneracionReciboPeriodo::actualizar()`; si no
  hay ninguno lo **crea** con `::generar()`; si ya hay **más de uno** vigente, la fila se marca inválida
  con el motivo "esta locación tiene varios recibos en el periodo; edítelos individualmente" (no se
  intenta adivinar cuál).
- **Rationale**: respeta la invariante de no-superposición de conceptos de specs/023 sin duplicar su
  lógica; el caso multi-recibo es minoritario y peligroso de automatizar.
- **Idempotencia**: `updateOrCreate` / actualizar-con-los-mismos-valores no cambia nada observable;
  `recibo_conceptos` se borra y recrea con montos idénticos (mismo patrón que
  `ServicioGeneracionReciboPeriodo::actualizar()`), sin cambio de `total()`.

## Decisión 4 — Validación por fila (motivos)

Fila **inválida** (se marca, no se guarda, no aborta el lote):

- `local_id` ausente, no numérico, o que no corresponde a una locación alquilable/activa (FR-002,
  FR-016, FR-021).
- Periodo de la fila (si la plantilla lo trae como columna informativa) distinto al seleccionado.
- **Lecturas**: `lectura_actual` vacía, no numérica, negativa, o menor que la lectura anterior real
  (reusa el criterio de `ServicioCalculoConsumoMedidor` + el "consumo negativo sin confirmar" ya
  existente; en importación no hay checkbox de confirmación, así que consumo negativo ⇒ inválida con
  motivo explícito).
- **Recibos**: algún monto no numérico o negativo; locación sin contrato activo en el periodo
  (`Locacion::contratoActivoEnPeriodo()` → `null`); columna de concepto que ya no existe en el catálogo
  (se ignora esa columna con aviso a nivel de importación, no invalida la fila).

El resumen final (`session('mensaje')` efímero) reporta: `N creadas, M actualizadas, K omitidas`. Las
filas omitidas quedan visibles en la vista previa con su `badge` de error y su motivo (persistente).

## Decisión 5 — Total sugerido de recibo en la vista previa

- **Decisión**: `totalSugerido(fila) = montoRenta + luz + Σ conceptos`. En la tabla, editar cualquier
  componente recalcula la celda "total sugerido" vía JS; el `<input name="...[total]">` solo se
  sobreescribe con el sugerido **mientras el usuario no lo haya tocado** (bandera `data-editado`). Al
  confirmar, si `total` viene numérico se respeta; si viene vacío se usa el sugerido recalculado en
  servidor. Igual criterio que specs/019 (Decisión 2/4 de su research).
- **Rationale**: coherencia con el comportamiento ya establecido del total editable de recibos.

## Decisión 6 — Columnas dinámicas por concepto en la plantilla de recibos

- **Decisión**: `PlantillaRecibosExport::headings()` = `['local_id','Locación','Contrato','Renta (S/)',
  'Luz (S/)', <nombre de cada ConceptoGastoFijo activo no-protegido>, 'Total (S/)']`. "Renta" y "Luz"
  son columnas fijas (son los dos conceptos `protegidos` con fuente de valor especial — `esRenta()` /
  `esLuz()`); el resto de conceptos activos son columnas por `nombre`. Al importar, cada columna de
  concepto se re-mapea a su `concepto_gasto_fijo_id` por `nombre` **contra el catálogo vigente**; una
  columna cuyo nombre ya no existe se ignora con aviso; un concepto nuevo del catálogo que no está en el
  archivo entra con su valor por defecto (`ValorConceptoContrato` del contrato, o 0).
- **Rationale**: el catálogo es dinámico (specs/024); mapear por nombre visible es lo que el usuario
  edita, y la resolución contra el catálogo vigente evita cargar montos a un concepto equivocado.
- **Riesgo**: dos conceptos con el mismo `nombre`. Mitigación: el catálogo ya trata `nombre` como
  identificador de UI; si hubiera colisión, se usa el de menor `orden` y se avisa.

## Decisión 7 — QR del comprobante y enlace firmado

- **Decisión**: nueva ruta `GET /cobro/recibo/{recibo}` con nombre `cobro.recibo` y middleware `signed`.
  El comprobante embebe `<img alt="Código para cobro" src="{{ $codigoQrDataUri }}">` donde
  `$codigoQrDataUri` lo produce `ServicioCodigoQrRecibo::dataUri($recibo)` =
  `URL::signedRoute('cobro.recibo', $recibo)` → PNG base64 vía `endroid/qr-code`. Sin expiración (un
  recibo impreso se cobra semanas después); la firma solo impide falsificar/enumerar ids.
- **Ubicación**: esquina inferior del `#comprobante-recibo`, ~96 px, con una leyenda pequeña "Escanee
  para registrar el pago". Respeta `@media print` (se imprime) y no interfiere con `html2canvas` (es un
  `data:` URI, no una fuente `oklch`).
- **Rationale**: `URL::signedRoute` es el mecanismo nativo de Laravel para enlaces no adulterables;
  `endroid/qr-code` genera PNG sin dependencias nativas. Memoria del proyecto: el comprobante es un
  documento de impresión — el cambio es mínimo y aditivo (una imagen en una esquina).
- **Alternativas**: id crudo en el QR (enumerable, sin integridad); token propio en una tabla (reinventa
  `signed`); barcode Code128 (menos robusto en móvil, descartado en la aclaración con el usuario).

## Decisión 8 — Dependencias nuevas sin red en el entorno de implementación

- **Preferido**: `composer require endroid/qr-code` y `npm i html5-qrcode`, luego `npm run build`.
- **Fallback si no hay red**:
  - QR: `ServicioCodigoQrRecibo` genera un `<svg>` de matriz QR con una implementación mínima propia
    (o, si tampoco es viable, un enlace corto legible + código numérico impreso grande) — el contrato
    de `dataUri()` / `svg()` no cambia para el resto del código.
  - Escaneo: la vista `cobro/index` arranca directamente en modo "ingresar número de recibo"; el bloque
    de cámara se muestra solo si `window.Html5Qrcode` existe. El fallback manual es de todos modos
    obligatorio (FR-026), así que la feature es utilizable sin la librería JS.
- **Rationale**: el usuario pidió dejar todo corriendo de noche; la parte de cámara no puede validarse
  sin hardware + HTTPS de todos modos, así que no debe ser un bloqueante duro.

## Decisión 9 — `medio_pago` en `pagos`

- **Decisión**: migración aditiva `pagos.medio_pago` `string(60)` **nullable**. `Pago::$fillable` +=
  `medio_pago`. `SolicitudRegistrarCobroRapido` valida `medio_pago` como `nullable|string|max:60`.
  `ServicioGestionPagosRecibo::registrar()` persiste `medio_pago` si viene en `$datos` (cambio
  retrocompatible: la clave es opcional). El resto del flujo de pagos (specs/032) no la envía y queda
  igual.
- **Rationale**: el usuario pidió "medio de pago" en el formulario rápido; nullable no toca datos ni
  tests existentes.
- **Alternativa**: no persistirlo (se pierde información que el usuario pidió capturar). Descartada.

## Decisión 10 — Acceso de US3 (perfiles)

- **Decisión**: rutas de cobro bajo `Route::middleware(['auth','cuenta.activa'])` — la misma pila que
  `dashboard`. Sin `perfil.master`, sin `@can`. La card del panel y el ítem de menú se muestran a todo
  usuario autenticado.
- **Rationale**: "Master y Administrador" = todos los perfiles que hoy pueden entrar a la app; no hay un
  tercer perfil con acceso restringido. Evita middleware/permiso nuevo sin valor.

## Decisión 11 — Reconciliación de estados de recibo

- El enum real de `recibos.estado` es `pendiente | pagado | anulado` (no existe "emitido" ni "borrador"
  como estado de un `Recibo` persistido; "borrador" es la tabla `borradores_recibo`).
- **Habilita el formulario rápido de cobro** ⇔ `estado != 'anulado'` **y** `saldoPendiente() > 0`.
- **Bloquea con aviso**: `estado == 'anulado'` → "Este recibo está anulado."; `saldoPendiente() == 0` →
  "Este recibo ya está saldado." El spec se lee con esta equivalencia (borrador ≡ no aplica aquí).
