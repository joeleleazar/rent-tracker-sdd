# Implementation Plan: Carga Masiva por Plantilla y Cobro por QR

**Branch**: `044-carga-masiva-y-cobro-qr` | **Date**: 2026-09-01 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/044-carga-masiva-y-cobro-qr/spec.md`

## Summary

Tres cortes independientes sobre pantallas ya existentes, entregados en una sola rama con un commit por
user story:

1. **US1 — Carga masiva de lecturas por plantilla (P1)**: en `lecturas.registroMasivo.index` se agregan
   "Descargar plantilla" (xlsx del periodo, una fila por locación alquilable, con `local_id`, nombre,
   lectura anterior de referencia y lectura actual precargada si ya existe) e "Importar archivo" (xlsx o
   csv → **vista previa editable htmx en la misma pantalla**, sin borrador persistido → confirmar →
   **upsert** por `(locacion_id, periodo)` con validación por fila y resumen de creadas/actualizadas/
   omitidas). La grilla manual de specs/015-016 y su autoguardado quedan intactos.
2. **US2 — Carga masiva de recibos por plantilla (P1)**: mismo par de acciones en
   `recibos.registroMasivo.index`. La plantilla tiene una fila por locación **con contrato activo en el
   periodo**, con columnas `monto_renta`, `luz` y **una columna por cada `ConceptoGastoFijo` activo** más
   `total`. Vista previa editable con total sugerido recalculado (respetando un total tecleado a mano,
   specs/019). Confirmar → upsert de `Recibo` + `recibo_conceptos` por `(locacion_id, periodo)`,
   reutilizando `ServicioGeneracionReciboPeriodo` (con una variante de "reemplazo completo" que no choque
   con la regla de no-superposición de specs/023 cuando hay un único recibo por locación/periodo).
3. **US3 — Cobro por QR desde el inicio (P2)**: QR discreto (PNG data-URI, `endroid/qr-code`) en
   `resources/views/locaciones/recibos/comprobante.blade.php` que codifica `URL::signedRoute` a una ruta
   nueva `cobro.recibo`. Card en `panel/inicio.blade.php` + ítem de menú "Cobro por QR" (visibles para
   todo usuario autenticado; Master y Administrador ya comparten esa pila de middleware). Vista de
   escaneo con `html5-qrcode` (cámara) + **fallback manual** por número de recibo. Al resolver el recibo,
   un **formulario rápido nuevo** (local, periodo, total, saldo + monto/fecha/medio de pago/evidencia
   opcional) que delega en `ServicioGestionPagosRecibo::registrar()` y en `EvidenciaPagoController`
   (mismo efecto que registrar el pago desde `recibos/show`). Se ofrece solo si el recibo **no está
   anulado y tiene saldo pendiente > 0**; anulado o saldado → aviso.

**Reconciliaciones de terminología (ver research.md):**

- El spec habla de recibo "emitido / en borrador / anulado / saldado". En este código un `Recibo`
  persistido **ya está emitido**; el borrador es la tabla aparte `borradores_recibo` y no es un
  `Recibo`. El enum real de `recibos.estado` es `pendiente | pagado | anulado`. Por lo tanto la
  condición operativa de US3 es: **habilita cobro** ⇔ `estado != 'anulado' && saldoPendiente() > 0`;
  **bloquea con aviso** ⇔ `estado == 'anulado'` (anulado) o `saldoPendiente() == 0` (ya saldado).
- "Perfiles Master y Administrador" para US3 no requiere middleware nuevo: la ruta vive bajo
  `['auth','cuenta.activa']` igual que `dashboard`, que ambos perfiles ya comparten. No se agrega
  `perfil.master` ni un permiso nuevo.
- `pagos` no tiene columna `medio_pago`. Se agrega **nullable** (`string(60)`) en una migración
  aditiva; solo el formulario rápido de US3 la completa por ahora, el resto del flujo de pagos la
  ignora sin cambios de comportamiento.

## Technical Context

**Language/Version**: PHP 8.3, Laravel 12.x (Illuminate v12), Blade, PostgreSQL 15+. Mismo stack del
proyecto.

**Primary Dependencies**:
- Existentes: `maatwebsite/excel` 4.0.2 (plantillas xlsx + parseo de importación con `ToCollection` +
  `WithHeadingRow`), Bootstrap 5.3 + Bootstrap Icons, htmx (`hx-boost` + swaps parciales),
  `barryvdh/laravel-dompdf` (no se toca).
- **Nuevas**: `endroid/qr-code` (PHP, `composer require` — genera el PNG data-URI del QR del
  comprobante) y `html5-qrcode` (npm, escaneo por cámara en la vista de cobro; se importa en un
  `resources/js/cobro-qr.js` nuevo y se agrega a `vite.config.js`). Si `composer require` / `npm i` no
  tienen red en el entorno de implementación, ver research.md Decisión 8 (fallback: QR como `<svg>`
  inline propio y captura manual sin cámara) — la feature no se bloquea, pero US3 no puede verificarse
  end-to-end sin cámara + HTTPS (queda a cargo del usuario).

**Storage**: PostgreSQL. **1 migración aditiva**: `pagos.medio_pago` nullable. **0 tablas nuevas**, 0
columnas más. La "vista previa de importación" es un artefacto transitorio en memoria de la petición —
no se persiste (FR-013). US1/US2 hacen `INSERT`/`UPDATE` sobre `lecturas_medidor`, `recibos` y
`recibo_conceptos` ya existentes.

**Testing**: Pest 4.x. Feature tests de cada endpoint nuevo (descarga de plantilla: encabezados y filas
esperadas; importación: vista previa, validación por fila, upsert crear/actualizar/omitir, idempotencia,
archivo desalineado / de otro periodo / de la otra función; cobro: resolución por enlace firmado y por
número, formulario ofrecido/bloqueado según estado y saldo, registro de pago con y sin evidencia, enlace
manipulado → 403). Unit tests de los servicios nuevos (parser/validador de filas, calculadora de total
sugerido de recibo, generador de plantilla). Los tests existentes de specs/015/016/023/032/035 deben
seguir verdes (regresión).

**Target Platform**: Aplicación web Laravel; navegadores de escritorio y móviles modernos. El escaneo por
cámara requiere contexto seguro (HTTPS) y permiso de cámara.

**Project Type**: Aplicación web monolítica existente. Sin cambio de estructura: se agregan
controladores/servicios/exports/imports/requests, parciales Blade sobre las dos pantallas de registro
masivo y dos vistas nuevas (escaneo + formulario rápido de cobro), y rutas.

**Performance Goals**:
- Descarga de plantilla y confirmación de importación utilizables con ≥ 300 filas (SC-001/SC-003):
  batch-fetch de locaciones/lecturas/recibos/conceptos **antes** del bucle (mismo criterio anti-N+1 de
  specs/018), y confirmación en **una** `DB::transaction` con `upsert()` por lote donde aplique.
- Vista previa renderizada en < 2 s para ese volumen; es HTML de una tabla, sin JS pesado.
- Cobro: de "abrir" a "pago registrado" < 30 s escaneando / < 45 s por número (SC-006) — la vista de
  cobro es una sola pantalla, el formulario rápido no navega hasta enviar.

**Constraints**:
- **No romper lo existente**: la grilla manual de lecturas/recibos, su autoguardado de borrador, la
  exportación Excel/PDF de specs/015 y el flujo de pagos de specs/032/035 conservan su comportamiento.
- **Upsert idempotente** por `(locacion_id, periodo)` (FR-008/FR-012): reimportar y confirmar el mismo
  archivo no crea duplicados ni cambios espurios.
- **Validación por fila** (FR-006/FR-007): una fila inválida se marca y se omite, nunca aborta el lote;
  la confirmación del subconjunto válido es atómica (FR-011).
- **Emparejamiento por `local_id`** técnico de la plantilla (FR-002); columna alterada/vacía ⇒ fila
  inválida.
- **Tarifa por kWh** sigue siendo el input global de la pantalla; la plantilla de lecturas no la lleva
  (FR-015). El total de la lectura se persiste con la tarifa vigente al confirmar (igual criterio que
  specs/019).
- **Total de recibo** editable que gana sobre el recalculado (FR-020, specs/019). Catálogo de conceptos
  resuelto al importar (FR-022): columna de concepto inexistente se ignora con aviso.
- **QR discreto** que no rompe el layout de impresión del comprobante ni la captura `html2canvas` (esa
  vista usa CSS propio con hex, sin `oklch`; el QR va como `<img src="data:image/png;base64,…">`).
- **Enlace de recibo no adulterable**: `URL::signedRoute` + middleware `signed` en la ruta; enlace
  inválido ⇒ 403 con mensaje para reintentar (FR-030).
- **htmx, no Alpine** (Principio VI). Degradación elegante: descarga de plantilla y formulario rápido
  (vía número manual) funcionan sin JS; vista previa editable y cámara requieren JS y lo informan.
- **Notificaciones efímeras** specs/042 para éxito/error; errores por fila/campo persistentes.
- **Español** en todo el código nuevo (Principio II). Montos `decimal:2` y operaciones en
  `DB::transaction` (Principio V).
- Toda vista Blade nueva o modificada pasa la revisión `impeccable` antes de cerrarse (Principio VI).

**Scale/Scope** (estimado):
- **Rutas nuevas**: `lecturas.registroMasivo.plantilla`, `lecturas.registroMasivo.importar.previsualizar`,
  `lecturas.registroMasivo.importar.confirmar`; ídem `recibos.registroMasivo.*`;
  `cobro.index`, `cobro.buscar`, `cobro.recibo` (signed), `cobro.pago.store`.
- **Controladores**: métodos nuevos en `RegistroMasivoLecturasController` y
  `RegistroMasivoRecibosController`; `ControladorCobroQr` nuevo.
- **Servicios nuevos**: `ServicioPlantillaLecturas`, `ServicioImportacionLecturas` (parseo + validación
  + upsert), `ServicioPlantillaRecibos`, `ServicioImportacionRecibos`, `ServicioCodigoQrRecibo` (genera
  el data-URI y arma/verifica el enlace firmado).
- **Export/Import (maatwebsite)**: `PlantillaLecturasExport`, `PlantillaRecibosExport`,
  `ImportacionLecturasImport`, `ImportacionRecibosImport` (o parseo directo con `Excel::toCollection`).
- **Form Requests**: `SolicitudConfirmarImportacionLecturas`, `SolicitudConfirmarImportacionRecibos`,
  `SolicitudRegistrarCobroRapido`.
- **Vistas**: parciales `importar-lecturas` / `vista-previa-lecturas` sobre
  `lecturas/registro-masivo/`; ídem recibos; `cobro/index.blade.php` (escáner + fallback),
  `cobro/recibo.blade.php` (formulario rápido / aviso); card nueva en `panel/partials/`; ítem de menú en
  `components/layouts/app-bootstrap.blade.php`; QR en el comprobante.
- **JS**: `resources/js/importacion-vista-previa.js` (recálculo de total sugerido y edición en la tabla),
  `resources/js/cobro-qr.js` (html5-qrcode + fallback).
- **Migración**: 1 (`pagos.medio_pago` nullable).
- **Modelos**: `Pago::$fillable` += `medio_pago`.
- **Tests**: ~7–9 archivos nuevos.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Stack Moderno (PHP/Laravel/PostgreSQL)**: Cumple — Eloquent + `upsert()`/`DB::transaction`, sin
  SQL crudo sin parametrizar; lógica en Services desacoplados; una única migración aditiva con columna
  `nullable` (sin romper datos existentes). `maatwebsite/excel` ya es dependencia del proyecto;
  `endroid/qr-code` es una librería PHP estándar sin binarios nativos.
- **II. Español**: Cumple — `ServicioImportacionLecturas`, `ServicioPlantillaRecibos`,
  `ControladorCobroQr`, `SolicitudRegistrarCobroRapido`, métodos `previsualizar()`, `confirmar()`,
  `filasValidas()`, `totalSugerido()`, `verificarEnlaceFirmado()`; columnas/keys `local_id`,
  `medio_pago`; vistas en `resources/views/cobro/` y parciales `*-importacion-*`. PHPDoc en español.
- **III. Diseño Moderno e Intuitivo**: Aplica — `card` para la card de cobro del panel y para el
  resumen del recibo en el formulario rápido; `table` + `table-hover` para la vista previa;
  `input-group` con prefijo `S/` en todo monto editable (renta, luz, conceptos, total, monto de pago);
  `badge` semántico para el estado de fila (válida/errónea) y del recibo; `Modal` no es necesario (no
  hay acción destructiva nueva; la confirmación de importación es un paso de formulario, no un borrado);
  estados vacíos con el componente del proyecto; `@media print` intacto en el comprobante.
- **IV. Pruebas Exhaustivas**: Aplica — Feature tests de todos los endpoints nuevos y Unit tests de los
  servicios de parseo/validación/total/QR (ver Testing). Ningún camino nuevo sin cobertura; regresión de
  las suites de specs/015/016/023/032/035.
- **V. Integridad y Seguridad Transaccional**: Cumple — confirmación de importación y registro de pago
  en `DB::transaction`; montos `decimal:2`; CSRF en todos los formularios; enlace de recibo firmado y
  verificado (no se confía en un id crudo del QR); subida de evidencia validada por
  `EvidenciaPagoController` ya existente.
- **VI. Bootstrap 5 + htmx + impeccable**: Aplica — solo componentes Bootstrap 5.3, iconografía
  consistente (`bi-upload` importar, `bi-file-earmark-arrow-down` plantilla, `bi-qr-code-scan` cobro,
  `bi-cash-coin` pago), contraste con la paleta real del proyecto, responsive sin scroll horizontal,
  `aria-*` en íconos y alertas, htmx para la vista previa (no Alpine), degradación elegante. Cada vista
  nueva/modificada pasa `/impeccable` antes de cerrar su tarea.

**Resultado del gate**: PASS. Sin violaciones que justificar → sin sección de Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/044-carga-masiva-y-cobro-qr/
├── plan.md              # Este archivo
├── research.md          # Fase 0 — decisiones
├── data-model.md        # Fase 1 — entidades y reglas
├── quickstart.md        # Fase 1 — guía de validación manual
├── contracts/           # Fase 1 — contratos de endpoints y formatos de plantilla
│   ├── plantilla-lecturas.md
│   ├── importar-lecturas.md
│   ├── plantilla-recibos.md
│   ├── importar-recibos.md
│   └── cobro-qr.md
├── checklists/
│   └── requirements.md
└── tasks.md             # Fase 2 (/speckit-tasks)
```

### Source Code (repository root)

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── RegistroMasivoLecturasController.php   # + plantilla(), previsualizarImportacion(), confirmarImportacion()
│   │   ├── RegistroMasivoRecibosController.php    # + plantilla(), previsualizarImportacion(), confirmarImportacion()
│   │   └── ControladorCobroQr.php                 # NUEVO — index(), buscar(), recibo() [signed], registrarPago()
│   └── Requests/
│       ├── SolicitudConfirmarImportacionLecturas.php   # NUEVO
│       ├── SolicitudConfirmarImportacionRecibos.php    # NUEVO
│       └── SolicitudRegistrarCobroRapido.php           # NUEVO
├── Services/
│   ├── ServicioPlantillaLecturas.php             # NUEVO — filas del periodo → arreglo para el export
│   ├── ServicioImportacionLecturas.php           # NUEVO — parsear + validar por fila + upsert en transacción
│   ├── ServicioPlantillaRecibos.php              # NUEVO
│   ├── ServicioImportacionRecibos.php            # NUEVO — incluye totalSugerido() y alineación de conceptos
│   └── ServicioCodigoQrRecibo.php                # NUEVO — data-URI del QR + armar/verificar enlace firmado
├── Exports/
│   ├── PlantillaLecturasExport.php               # NUEVO (FromCollection + WithHeadings)
│   └── PlantillaRecibosExport.php                # NUEVO (columnas dinámicas por concepto)
├── Imports/
│   ├── ImportacionLecturasImport.php             # NUEVO (ToCollection + WithHeadingRow)
│   └── ImportacionRecibosImport.php              # NUEVO
└── Models/
    └── Pago.php                                  # + 'medio_pago' en $fillable

database/migrations/
└── 2026_09_01_000000_agregar_medio_pago_a_pagos_table.php   # NUEVO (nullable)

resources/
├── js/
│   ├── importacion-vista-previa.js               # NUEVO (edición de tabla + total sugerido)
│   └── cobro-qr.js                               # NUEVO (html5-qrcode + fallback manual)
└── views/
    ├── lecturas/registro-masivo/
    │   ├── index.blade.php                       # + barra de acciones "Descargar plantilla" / "Importar"
    │   └── partials/
    │       ├── acciones-importacion.blade.php    # NUEVO
    │       └── vista-previa-importacion.blade.php# NUEVO
    ├── recibos/registro-masivo/
    │   ├── index.blade.php                       # + misma barra de acciones
    │   └── partials/
    │       ├── acciones-importacion.blade.php    # NUEVO
    │       └── vista-previa-importacion.blade.php# NUEVO
    ├── panel/partials/
    │   └── acceso-cobro-qr.blade.php             # NUEVO (card del panel de inicio)
    ├── panel/inicio.blade.php                    # + @include del acceso directo
    ├── cobro/
    │   ├── index.blade.php                       # NUEVO (escáner + fallback manual)
    │   └── recibo.blade.php                      # NUEVO (formulario rápido / aviso por estado)
    ├── locaciones/recibos/comprobante.blade.php  # + <img> del QR firmado
    └── components/layouts/app-bootstrap.blade.php# + ítem de menú "Cobro por QR"

routes/web.php                                    # + rutas de plantilla/importación y de cobro

tests/Feature/  y  tests/Unit/                    # ~7–9 archivos nuevos
```

**Structure Decision**: se conserva la estructura monolítica del proyecto. Las dos pantallas de registro
masivo reciben una barra de acciones adicional y dos parciales; el cobro por QR es un controlador nuevo
con dos vistas y un servicio de QR; la única migración es aditiva y nullable.

## Complexity Tracking

No aplica — el Constitution Check pasa sin violaciones.
