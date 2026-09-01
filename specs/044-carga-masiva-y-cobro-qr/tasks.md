---
description: "Task list — Carga Masiva por Plantilla y Cobro por QR"
---

# Tasks: Carga Masiva por Plantilla y Cobro por QR

**Input**: Design documents from `/specs/044-carga-masiva-y-cobro-qr/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md

**Tests**: INCLUIDOS — la Constitución (Principio IV) exige cobertura de todo camino lógico nuevo; CI
bloquea con tests rojos o sin cobertura.

**Entrega**: una sola rama `044-carga-masiva-y-cobro-qr`, **un commit por user story** (US1 → US2 → US3),
más un commit de Setup/Foundational y uno de Polish si aplica.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: puede ir en paralelo (archivo distinto, sin dependencias pendientes)
- Rutas de archivo exactas en cada tarea

---

## Phase 1: Setup (infraestructura compartida)

**Purpose**: dependencias nuevas y andamiaje común a las tres historias.

- [X] T001 Ejecutar `composer require endroid/qr-code` y confirmar el autoload; si no hay red, marcar el
  fallback de research.md Decisión 8 y anotarlo en `specs/044-carga-masiva-y-cobro-qr/research.md`.
- [X] T002 Ejecutar `npm install html5-qrcode`, agregar su import a un nuevo `resources/js/cobro-qr.js`
  vacío y registrarlo como entry en `vite.config.js`; correr `npm run build`. Si no hay red, aplicar el
  fallback (cámara opcional) y anotarlo.
- [X] T003 Crear la migración `database/migrations/2026_09_01_000000_agregar_medio_pago_a_pagos_table.php`
  que agrega `pagos.medio_pago` `string(60)` **nullable**; `up()`/`down()` simétricos, PHPDoc en español
  citando specs/044. Ejecutar `php artisan migrate`.
- [X] T004 [P] Agregar `'medio_pago'` a `App\Models\Pago::$fillable` en `app/Models/Pago.php`.
- [X] T005 [P] Añadir helpers de test compartidos (factories que falten) en `database/factories/` para
  `LecturaMedidor`, `Recibo`, `ReciboConcepto`, `ConceptoGastoFijo`, `Pago` — verificar que existan y
  cubran los campos usados por esta feature; completar solo lo faltante.

**Checkpoint**: dependencias instaladas, columna `medio_pago` disponible.

---

## Phase 2: Foundational (prerequisitos bloqueantes)

**Purpose**: piezas transversales que US1 y US2 comparten y US3 no toca.

**⚠️ CRITICAL**: ninguna historia arranca hasta terminar esta fase.

- [X] T006 Crear `app/Support/Importacion/FilaImportada.php` (o equivalente): DTO simple con
  `local_id`, `valores` (array), `estado` (`valida`/`invalida`), `motivos` (array<string>), `accion`
  (`crear`/`actualizar`/`omitir`). PHPDoc en español.
- [X] T007 [P] Crear `app/Support/Importacion/ResultadoImportacion.php`: contador inmutable
  `creadas`, `actualizadas`, `omitidas` con un método `mensaje(): string` que arma
  `"Importación: N creadas, M actualizadas, K omitidas."` (y variantes de género para recibos vía
  parámetro).
- [X] T008 [P] Crear `resources/js/importacion-vista-previa.js` (entry en `vite.config.js`): al editar
  una celda de la tabla de vista previa, recalcula "consumo"/"total sugerido" de esa fila y actualiza
  su `badge` de estado; respeta `data-editado` en el input de total. Sin dependencias externas.
- [X] T009 Definir el bloque de rutas nuevas en `routes/web.php` dentro del grupo
  `['auth','cuenta.activa']`, en el orden correcto (las rutas literales `/lecturas/registro-masivo/...`
  y `/recibos/registro-masivo/...` **antes** de cualquier `{recibo}`/`{lectura}` que las pudiera
  capturar): `lecturas.registroMasivo.plantilla`, `lecturas.registroMasivo.importar.previsualizar`,
  `lecturas.registroMasivo.importar.confirmar`, ídem `recibos.registroMasivo.*`, y `cobro.index`,
  `cobro.buscar`, `cobro.recibo` (con `->middleware('signed')`), `cobro.pago.store`. Dejar los métodos
  de controlador referenciados aunque aún no existan (se crean en sus fases).

**Checkpoint**: contratos de ruta y utilidades compartidas listos.

---

## Phase 3: User Story 1 — Carga masiva de lecturas por plantilla (P1) 🎯 MVP

**Goal**: descargar plantilla xlsx del periodo, importar xlsx/csv, vista previa editable con validación
por fila, confirmar con upsert `(locacion_id, periodo)` y resumen. Grilla manual intacta.

**Independent Test**: quickstart.md §US1 — plantilla con una fila válida y una inválida (lectura menor
que la anterior), corregir en la vista previa, confirmar, verificar en la grilla y reimportar para
comprobar idempotencia.

### Tests para User Story 1 ⚠️ (escribir primero, deben fallar)

- [X] T010 [P] [US1] `tests/Unit/ServicioImportacionLecturasTest.php`: parseo de filas, cada motivo de
  invalidez (local_id vacío/no numérico/no alquilable, lectura vacía/no numérica/negativa/menor que la
  anterior, periodo distinto), y `total` explícito vs. calculado con tarifa global.
- [X] T011 [P] [US1] `tests/Unit/ServicioPlantillaLecturasTest.php`: una fila por locación alquilable en
  orden de árbol, precarga de `lectura_actual` si existe, `Lectura Periodo Anterior` = última real
  anterior, ausencia de columna de tarifa.
- [X] T012 [P] [US1] `tests/Feature/ImportacionLecturasControllerTest.php`: descarga de plantilla
  (headers `periodo`/`local_id`/… + nº de filas); `previsualizar` devuelve tabla con badges;
  `previsualizar` con plantilla de recibos → 422; `previsualizar` con archivo cuya columna `periodo` no
  coincide con el periodo de pantalla → 422; **sin estado**: tras `previsualizar` no hay filas nuevas en
  `lecturas_medidor` ni registro en `borradores_lectura_medidor`, y una segunda visita a `index` no
  restaura la vista previa; `confirmar` crea+actualiza+omite y redirige con el mensaje; todas inválidas →
  back con errores y nada persistido; **idempotencia** (confirmar dos veces = mismo estado); la grilla
  manual y su borrador siguen respondiendo igual (regresión ligera).

### Implementación para User Story 1

- [X] T013 [P] [US1] `app/Services/ServicioPlantillaLecturas.php`: método `filas(Carbon $periodo): array`
  reutilizando `Locacion::alquilables()` + `ServicioConstruccionArbolLocaciones` + batch-fetch de
  lecturas del periodo y anteriores (patrón anti-N+1 de specs/018). Devuelve arreglos con
  `local_id`, `Locación`, `Lectura Periodo Anterior`, `Lectura Actual`.
- [X] T014 [P] [US1] `app/Exports/PlantillaLecturasExport.php` (`FromCollection` + `WithHeadings`):
  encabezados exactos de `contracts/plantilla-lecturas.md` (primera columna `periodo` = `Y-m`), filas
  desde `ServicioPlantillaLecturas`.
- [X] T015 [P] [US1] `app/Imports/ImportacionLecturasImport.php` (`ToCollection` + `WithHeadingRow`):
  normaliza encabezados y expone la colección cruda; lanza/expone un error si falta `periodo`,
  `local_id` o `Lectura Actual`.
- [X] T016 [US1] `app/Services/ServicioImportacionLecturas.php`:
  `previsualizar(UploadedFile $archivo, Carbon $periodo): array<FilaImportada>` (rechaza el archivo si
  falta un encabezado esperado o si la columna `periodo` no coincide con `$periodo`; luego parseo +
  validación por fila, sin tocar BD, resolviendo `lectura_anterior` real y `accion`
  crear/actualizar/omitir) y
  `confirmar(array $filas, Carbon $periodo): ResultadoImportacion` (revalida y hace
  `LecturaMedidor::updateOrCreate(['locacion_id','periodo'], [...])` de las válidas dentro de **una**
  `DB::transaction`, `total` = explícito o `round(consumo * tarifaGlobal, 2)`). Depende de T006/T007.
- [X] T017 [US1] `app/Http/Requests/SolicitudConfirmarImportacionLecturas.php`: `periodo` `required
  date_format:Y-m-d`; `filas` `required array`; `filas.*.local_id` `required integer`;
  `filas.*.lectura_actual` `nullable numeric`; `filas.*.total` `nullable numeric`. Mensajes en español.
- [X] T018 [US1] Métodos en `app/Http/Controllers/RegistroMasivoLecturasController.php`:
  `plantilla(Request)` → `Excel::download`; `previsualizarImportacion(Request)` (valida `archivo`
  `mimes:xlsx,csv max:5120` + `periodo`, devuelve el parcial de vista previa o un 422 con alerta);
  `confirmarImportacion(SolicitudConfirmarImportacionLecturas)` (llama al servicio, redirige con
  `session('mensaje')` efímero o `back()->withErrors()->withInput()` si todas inválidas). PHPDoc citando
  specs/044.
- [X] T019 [P] [US1] `resources/views/lecturas/registro-masivo/partials/acciones-importacion.blade.php`:
  botón "Descargar plantilla" (`bi-file-earmark-arrow-down`, `hx-boost="false"`) + `<form>` de subida
  con `<input type="file" name="archivo">` (`hx-post` a `previsualizar`, `hx-target`
  `#vista-previa-importacion-lecturas`, `hx-encoding="multipart/form-data"`).
- [X] T020 [US1]
  `resources/views/lecturas/registro-masivo/partials/vista-previa-importacion.blade.php`: `<table>`
  con `filas[i][local_id]` hidden + `filas[i][lectura_actual]` (`input-group`), celdas de referencia,
  `badge` de estado y `motivos` persistentes; contador "N válidas · M con error"; botón "Confirmar
  importación" deshabilitado si 0 válidas; alerta de rechazo `<x-mensaje-alerta tipo="error">` cuando
  el controlador marca archivo no aceptable. Carga `@vite('resources/js/importacion-vista-previa.js')`.
- [X] T021 [US1] Integrar en `resources/views/lecturas/registro-masivo/index.blade.php`: incluir
  `acciones-importacion` en la fila de controles (junto a Exportar) y un contenedor
  `<div id="vista-previa-importacion-lecturas">` bajo la card; no alterar la grilla ni el autoguardado.
- [ ] T022 [US1] Ejecutar `/impeccable polish` sobre `index.blade.php` y los dos parciales nuevos;
  aplicar hallazgos; registrar en `DESIGN.md` si corresponde.
- [X] T023 [US1] Correr `php artisan test --filter=ImportacionLecturas` y
  `php artisan test tests/Feature/RegistroMasivoLecturasControllerTest.php tests/Unit/LecturaMedidorTest.php tests/Unit/ServicioCalculoConsumoMedidorTest.php`; todo verde.
- [ ] T024 [US1] `git add -A && git commit` — mensaje: "US1: carga masiva de lecturas por plantilla
  (specs/044)". Co-Authored-By trailer.

**Checkpoint**: US1 funcional y testeable de forma independiente; MVP entregable.

---

## Phase 4: User Story 2 — Carga masiva de recibos por plantilla (P1)

**Goal**: plantilla xlsx con columnas dinámicas por concepto, importar, vista previa editable con total
sugerido (respeta total tecleado), confirmar con upsert de `Recibo` + `recibo_conceptos` por
`(locacion_id, periodo)` reutilizando `ServicioGeneracionReciboPeriodo`.

**Independent Test**: quickstart.md §US2 — editar renta+concepto en una fila y `Total` a mano en otra,
confirmar, abrir un recibo y verificar montos/total, reimportar para idempotencia, fila sin contrato
activo → inválida.

### Tests para User Story 2 ⚠️

- [X] T025 [P] [US2] `tests/Unit/ServicioImportacionRecibosTest.php`: mapeo de columnas de concepto por
  nombre contra el catálogo vigente (columna inexistente ignorada con aviso; concepto nuevo con default);
  `totalSugerido()` = renta+luz+Σconceptos; total explícito distinto → ajuste sobre luz (o el punto que
  use specs/019 — el test fija el comportamiento acordado); motivos de invalidez (monto negativo/no
  numérico, sin contrato activo, >1 recibo vigente, periodo distinto).
- [X] T026 [P] [US2] `tests/Unit/ServicioPlantillaRecibosTest.php`: encabezados = fijas + una por
  `ConceptoGastoFijo` activo no protegido + `Total`; precarga desde el recibo vigente único; derivación
  cuando no hay recibo; marcador cuando hay varios.
- [X] T027 [P] [US2] `tests/Feature/ImportacionRecibosControllerTest.php`: descarga (headers dinámicos,
  primera columna `periodo`); `previsualizar` (tabla + aviso de columna ignorada); `previsualizar` con
  plantilla de lecturas → 422; `previsualizar` con `periodo` de archivo distinto al de pantalla → 422;
  **sin estado** (nada persistido en `previsualizar`); `confirmar` crea vía `generar()` y actualiza vía
  `actualizar()` (verifica `recibo_conceptos` y `Recibo::total()`); todas inválidas → back;
  **idempotencia**; regresión: la tabla/acciones actuales de `recibos.registroMasivo.index` siguen igual.

### Implementación para User Story 2

- [X] T028 [P] [US2] `app/Services/ServicioPlantillaRecibos.php`: `columnasConcepto(): Collection`
  (`ConceptoGastoFijo::activos()->ordenados()` sin protegidos) y `filas(Carbon $periodo): array` con
  batch-fetch de contratos activos, recibos vigentes del periodo y `ValorConceptoContrato`; deriva luz
  con `ServicioGeneracionReciboPeriodo::calcularMontoLuzSugerido()`.
- [X] T029 [P] [US2] `app/Exports/PlantillaRecibosExport.php` (`FromCollection` + `WithHeadings`):
  encabezados dinámicos con `periodo` como primera columna, filas desde `ServicioPlantillaRecibos`.
- [X] T030 [P] [US2] `app/Imports/ImportacionRecibosImport.php` (`ToCollection` + `WithHeadingRow`):
  expone la colección cruda + la lista de encabezados detectados (para el aviso de columna ignorada y
  para validar `periodo`/`local_id`/`Total`).
- [X] T031 [US2] `app/Services/ServicioImportacionRecibos.php`: `previsualizar(UploadedFile, Carbon)`
  (rechaza si falta encabezado esperado o si `periodo` del archivo ≠ `$periodo`) y
  `confirmar(array $filas, Carbon): ResultadoImportacion`. `confirmar` recorre filas válidas en **una**
  `DB::transaction`: 0 recibos vigentes → `ServicioGeneracionReciboPeriodo::generar()`; 1 →
  `::actualizar()`; >1 → omitir con motivo. Mapea `conceptos` `{id: monto}` incluyendo luz; aplica la
  regla de `total` explícito de `contracts/importar-recibos.md`. **Bloqueante antes de cerrar US2**:
  leer `specs/019-total-editable-recibos/` y confirmar el punto real donde el total editable se refleja
  (concepto luz u otro); si difiere de la suposición, adoptar el mecanismo real y actualizar T025 y
  `contracts/importar-recibos.md`.
- [X] T032 [US2] `app/Http/Requests/SolicitudConfirmarImportacionRecibos.php`: `periodo`, `filas`
  `required array`, `filas.*.local_id` `required integer`, `filas.*.renta|luz|total` `nullable numeric`,
  `filas.*.conceptos` `array`, `filas.*.conceptos.*` `nullable numeric`.
- [X] T033 [US2] Métodos en `app/Http/Controllers/RegistroMasivoRecibosController.php`: `plantilla`,
  `previsualizarImportacion`, `confirmarImportacion` — mismo patrón que US1, `session('mensaje')` en
  género masculino ("N creados…").
- [X] T034 [P] [US2]
  `resources/views/recibos/registro-masivo/partials/acciones-importacion.blade.php` — análogo a US1.
- [X] T035 [US2]
  `resources/views/recibos/registro-masivo/partials/vista-previa-importacion.blade.php`: tabla con
  `renta`, `luz`, un input por `conceptos[<id>]`, `total` (con `data-editado`), celda "Total sugerido",
  badges/motivos, aviso de columna ignorada, botón confirmar. `@vite` del JS de vista previa.
- [X] T036 [US2] Integrar en `resources/views/recibos/registro-masivo/index.blade.php`: barra de
  acciones + `<div id="vista-previa-importacion-recibos">`; no tocar la tabla existente.
- [ ] T037 [US2] `/impeccable polish` sobre `recibos/registro-masivo/index.blade.php` y los parciales
  nuevos; aplicar hallazgos; `DESIGN.md` si corresponde.
- [X] T038 [US2] `php artisan test --filter=ImportacionRecibos` +
  `tests/Feature/RegistroMasivoRecibosControllerTest.php` +
  `tests/Unit/ServicioGeneracionReciboPeriodoTest.php tests/Unit/ReciboTest.php`; todo verde.
- [X] T039 [US2] `git commit` — "US2: carga masiva de recibos por plantilla (specs/044)".

**Checkpoint**: US1 y US2 funcionan de forma independiente.

---

## Phase 5: User Story 3 — Cobro por QR desde el inicio (P2)

**Goal**: QR firmado en el comprobante, acceso directo en inicio + menú, vista de escaneo con cámara +
fallback manual, formulario rápido que delega en `ServicioGestionPagosRecibo` y evidencia; bloqueo por
recibo anulado/saldado; enlace inválido → 403.

**Independent Test**: quickstart.md §US3 — resolver un recibo por número y (si hay cámara/HTTPS) por QR,
registrar pago parcial y luego total con evidencia, probar recibo anulado y enlace alterado.

### Tests para User Story 3 ⚠️

- [ ] T040 [P] [US3] `tests/Unit/ServicioCodigoQrReciboTest.php`: `dataUri()` devuelve
  `data:image/png;base64,` no vacío; el enlace embebido es `URL::signedRoute('cobro.recibo', $recibo)`;
  `verificar()` acepta la URL firmada y rechaza una con la firma alterada.
- [ ] T041 [P] [US3] `tests/Feature/CobroQrControllerTest.php`: `cobro.index` 200 para ambos perfiles;
  `cobro.buscar` con número inexistente → back con error; con número válido → redirect a `cobro.recibo`
  firmada; `cobro.recibo` sin firma → 403; con firma válida y recibo con saldo → formulario;
  recibo anulado → aviso sin formulario; recibo saldado → aviso; `cobro.pago.store` registra el pago
  (saldo baja, estado recalculado) y con `evidencia` adjunta guarda el archivo; monto > saldo → error;
  el comprobante (`recibos.show`… `comprobante`) renderiza un `<img src="data:image/png;base64,`.
- [ ] T042 [P] [US3] `tests/Feature/ComprobanteReciboQrTest.php` (o extender el test existente del
  comprobante): el QR aparece en la vista y no rompe el render.

### Implementación para User Story 3

- [ ] T043 [P] [US3] `app/Services/ServicioCodigoQrRecibo.php`: `enlace(Recibo): string`
  (`URL::signedRoute('cobro.recibo', $recibo)`), `dataUri(Recibo): string` (PNG base64 vía
  `endroid/qr-code`, con fallback SVG propio si la librería no está — research.md Decisión 8),
  `numeroEsValido(string): bool`.
- [ ] T044 [P] [US3] `app/Http/Requests/SolicitudRegistrarCobroRapido.php`: `monto` `required numeric
  gt:0`; `fecha_pago` `required date before_or_equal:today`; `medio_pago` `nullable string max:60`;
  `evidencia` `nullable file mimes:jpg,jpeg,png,pdf max:5120`. Mensajes en español.
- [ ] T045 [US3] Extender `app/Services/ServicioGestionPagosRecibo.php::registrar()` para persistir
  `medio_pago` cuando venga en `$datos` (clave opcional, retrocompatible); no cambiar firmas públicas.
  Añadir caso al `tests/Unit/ServicioGestionPagosReciboTest.php`.
- [ ] T046 [US3] `app/Http/Controllers/ControladorCobroQr.php`:
  `index()` (vista de escaneo), `buscar(Request)` (valida `numero`, resuelve `Recibo`, redirige a
  `cobro.recibo` firmada o back con error), `recibo(Request, Recibo)` (middleware `signed`; elige modo
  formulario/aviso según `estado`/`saldoPendiente()`), `registrarPago(SolicitudRegistrarCobroRapido,
  Recibo)` (llama a `ServicioGestionPagosRecibo::registrar` + evidencia opcional reutilizando la lógica
  de `EvidenciaPagoController`, redirige a `cobro.recibo` firmada con `session('mensaje')`).
- [ ] T047 [US3] Confirmar/afinar el bloque de rutas de T009 para US3 y su orden; `cobro.recibo` con
  `->middleware('signed')`, el resto solo `auth`+`cuenta.activa`.
- [ ] T048 [P] [US3] `resources/js/cobro-qr.js`: inicializa `html5-qrcode` sobre `#lector-qr` si existe
  `window.Html5Qrcode` y hay `getUserMedia`; al decodificar una URL de `cobro.recibo` de este host,
  `window.location = url`; si falla permiso o no hay soporte, oculta el bloque de cámara y deja el
  ingreso manual. Sin romper si el elemento no está en la página.
- [ ] T049 [P] [US3] `resources/views/cobro/index.blade.php`: layout `app-bootstrap`; card con
  `#lector-qr` (oculto por defecto, lo muestra el JS) y `<form method="GET" action="{{ route('cobro.buscar') }}">`
  con `numero` (`inputmode="numeric"`) siempre visible; aviso "necesita cámara y HTTPS" discreto.
  `@vite('resources/js/cobro-qr.js')`.
- [ ] T050 [P] [US3] `resources/views/cobro/recibo.blade.php`: modo **aviso** (`<x-mensaje-alerta>` +
  enlace a `cobro.index`) o modo **formulario** — `card` de resumen (local vía
  `<x-ruta-jerarquia-locacion>`, periodo, `total()`, `montoPagado()`, `saldoPendiente()` con `.cifra` y
  `S/`) + `<form method="POST">` con `monto` (`input-group` `S/`, default saldo), `fecha_pago` (default
  hoy), `medio_pago` (`<select>` opcional), `evidencia` (`<input type="file">` opcional). Errores por
  campo persistentes.
- [ ] T051 [US3] `resources/views/locaciones/recibos/comprobante.blade.php`: agregar
  `<img alt="Código para registrar el pago" src="{{ $codigoQrDataUri }}" width="96" height="96">` +
  leyenda pequeña en la esquina inferior del `#comprobante-recibo`, dentro de reglas que se respeten en
  `@media print`; pasar `$codigoQrDataUri` desde `ReciboController::comprobante()` vía
  `ServicioCodigoQrRecibo`.
- [ ] T052 [P] [US3] `resources/views/panel/partials/acceso-cobro-qr.blade.php`: `card` con
  `bi-qr-code-scan`, título "Cobro por QR", texto breve y botón/enlace a `cobro.index`. Incluir con
  `@include` al inicio de `resources/views/panel/inicio.blade.php`.
- [ ] T053 [US3] `resources/views/components/layouts/app-bootstrap.blade.php`: ítem de menú "Cobro por
  QR" (`bi-qr-code-scan`, `active` con `request()->routeIs('cobro.*')`) tras "Registro de Pagos".
- [ ] T054 [US3] `/impeccable polish` sobre `cobro/index.blade.php`, `cobro/recibo.blade.php`,
  `panel/partials/acceso-cobro-qr.blade.php`, `panel/inicio.blade.php`, `app-bootstrap.blade.php` y el
  cambio del comprobante (`/impeccable audit` para el comprobante por su naturaleza de impresión);
  aplicar hallazgos; actualizar `DESIGN.md`.
- [ ] T055 [US3] `php artisan test --filter=CobroQr` + `--filter=Comprobante` +
  `tests/Unit/ServicioGestionPagosReciboTest.php tests/Feature/EvidenciaPagoControllerTest.php
  tests/Feature/PagoReciboControllerTest.php`; todo verde. `npm run build` sin errores.
- [ ] T056 [US3] `git commit` — "US3: cobro por QR desde el inicio (specs/044)".

**Checkpoint**: las tres historias funcionan de forma independiente.

---

## Phase 6: Polish & transversal

- [ ] T057 [P] Correr la suite completa `php artisan test`; cero regresiones en specs/015/016/023/032/035
  y en el resto.
- [ ] T058 [P] `vendor/bin/pint` (o el linter del proyecto) sobre todos los archivos nuevos/modificados.
- [ ] T059 Revisar `routes/web.php`: nombres, orden y agrupación coherentes con los comentarios de estilo
  existentes (bloques con `// specs/044:` explicando el orden frente a `{recibo}`/`{lectura}`).
- [ ] T060 Ejecutar la validación manual de `specs/044-carga-masiva-y-cobro-qr/quickstart.md` hasta donde
  el entorno lo permita; anotar en el reporte final qué quedó sin verificar (escaneo por cámara: requiere
  dispositivo + HTTPS).
- [ ] T061 Commit de cierre si Polish produjo cambios — "Polish specs/044: lint, rutas, regresión".

---

## Dependencies & Execution Order

- **Setup (Phase 1)** → sin dependencias.
- **Foundational (Phase 2)** → depende de Setup; **bloquea** US1/US2 (comparten DTOs y JS de vista
  previa y el bloque de rutas). US3 solo depende de Setup (T001–T004) y de T009.
- **US1 (Phase 3)** → tras Foundational. Sin dependencia de US2/US3.
- **US2 (Phase 4)** → tras Foundational. Independiente de US1 (archivos distintos), pero comparte
  `RegistroMasivo*Controller` y patrón; se hace después de US1 por claridad y para un commit limpio.
- **US3 (Phase 5)** → tras Setup + T009. Independiente de US1/US2.
- **Polish (Phase 6)** → tras las historias que se vayan a entregar.

### Dentro de cada historia

Tests primero (deben fallar) → servicios/exports/imports → form request → controlador → vistas/parciales
→ revisión `impeccable` → correr tests → commit.

### Paralelizables

- T004, T005 (Setup) entre sí.
- T007, T008 (Foundational) entre sí.
- US1: T010–T012 (tests) juntos; T013–T015 (servicio plantilla, export, import) juntos; T019 aparte.
- US2: T025–T027 juntos; T028–T030 juntos.
- US3: T040–T042 juntos; T043, T044, T048, T049, T050, T052 en su mayoría en paralelo (archivos
  distintos); T045/T046/T051/T053 tocan archivos compartidos y van en serie.

---

## Parallel Example: User Story 1

```text
# Tests de US1 juntos:
Task T010: tests/Unit/ServicioImportacionLecturasTest.php
Task T011: tests/Unit/ServicioPlantillaLecturasTest.php
Task T012: tests/Feature/ImportacionLecturasControllerTest.php

# Luego, en paralelo:
Task T013: app/Services/ServicioPlantillaLecturas.php
Task T014: app/Exports/PlantillaLecturasExport.php
Task T015: app/Imports/ImportacionLecturasImport.php
```

---

## Implementation Strategy

### MVP (solo US1)

Setup → Foundational → Phase 3 (US1) → validar quickstart §US1 → commit. Entregable por sí solo.

### Entrega incremental

US1 (commit) → US2 (commit) → US3 (commit) → Polish (commit). Cada historia agrega valor sin romper la
anterior. Al terminar, la rama `044-carga-masiva-y-cobro-qr` queda lista para revisión del usuario
(sin `push` ni merge).

---

## Notes

- `[P]` = archivos distintos, sin dependencias pendientes.
- Verificar que los tests fallan antes de implementar.
- Un commit por user story (T024, T039, T056) + Setup/Foundational puede ir en el commit de US1 o en uno
  propio previo; Polish en T061.
- No romper: grilla manual de lecturas/recibos y su autoguardado, exportación Excel/PDF de specs/015,
  flujo de pagos de specs/032/035, layout de impresión del comprobante.
- Toda vista Blade nueva/modificada pasa por `/impeccable` antes de cerrar su tarea (Principio VI).
- La verificación end-to-end del escaneo por cámara (US3) requiere dispositivo con cámara + HTTPS y
  queda a cargo del usuario; los tests cubren resolución por número, enlace firmado, estados y registro
  de pago.
