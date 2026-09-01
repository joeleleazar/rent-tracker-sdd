# Quickstart — Validación manual de la feature 044

Prerrequisitos: `composer install`, `npm install && npm run build`, base con seeders
(`php artisan migrate:fresh --seed`), sesión iniciada como Master o Administrador.

## US1 — Carga masiva de lecturas

1. Ir a **Registrar Lecturas** (`/lecturas/registro-masivo`), elegir un periodo.
2. Pulsar **Descargar plantilla** → se baja `lecturas-plantilla-YYYY-MM.xlsx` con una fila por local,
   `local_id`, nombre, lectura anterior y lectura actual (precargada si ya había).
3. Editar el `.xlsx`: poner una `Lectura Actual` válida en un local y una inválida (menor que la
   anterior) en otro. Guardar.
4. Pulsar **Importar archivo**, elegir el archivo → aparece la **vista previa** en la misma pantalla:
   la fila inválida con `badge` rojo y su motivo; el resto verdes.
5. Corregir la celda inválida en la tabla → su `badge` pasa a verde.
6. **Confirmar importación** → redirección con aviso efímero "N creadas, M actualizadas, K omitidas".
7. Verificar en la grilla manual (recargando) que los valores quedaron.
8. Reimportar y confirmar el **mismo** archivo → el aviso reporta 0 creadas y no cambia nada
   (idempotencia).
9. Subir un archivo con columnas de recibos → se rechaza con mensaje, sin tabla.

## US2 — Carga masiva de recibos

1. Ir a **Emitir Recibos** (`/recibos/registro-masivo`), elegir periodo.
2. **Descargar plantilla** → `.xlsx` con `Renta`, `Luz`, una columna por concepto activo y `Total`.
3. En una fila cambiar `Renta` y un concepto (no tocar `Total`); en otra fila teclear un `Total`
   distinto de la suma. Guardar.
4. **Importar archivo** → vista previa: la primera fila muestra el `Total sugerido` recalculado; la
   segunda respeta el total tecleado.
5. **Confirmar** → aviso "N creados, M actualizados, K omitidos". Abrir un recibo afectado y verificar
   renta, conceptos y total.
6. Reimportar el mismo archivo y confirmar → sin duplicados, `total()` igual.
7. Fila de un local sin contrato activo → aparece inválida con su motivo.

## US3 — Cobro por QR

1. Emitir un recibo y abrir su **comprobante** (`/recibos/{id}/comprobante`) → hay un QR en la esquina
   con la leyenda "Escanee para registrar el pago". Imprimir preview: el QR entra en la página.
2. En el **inicio** hay una card **Cobro por QR** y un ítem de menú equivalente.
3. Abrir **Cobro por QR** (`/cobro`):
   - Con cámara + HTTPS: apuntar al QR → carga el formulario rápido del recibo.
   - Sin cámara: usar **Ingresar número** → escribir el nº del recibo → mismo formulario.
4. En el formulario rápido: local, periodo, total y saldo pendiente; registrar un **pago parcial**
   (monto < saldo, medio "Efectivo", sin evidencia) → aviso efímero "Pago registrado correctamente" y
   el saldo baja.
5. Registrar el pago restante con una **evidencia** (imagen) → el recibo pasa a saldado y la vista
   muestra "Este recibo ya está saldado".
6. Escanear / ingresar un recibo **anulado** → aviso "Este recibo está anulado", sin formulario.
7. Abrir `/cobro/recibo/{id}` sin firma o con la firma alterada → `403` con mensaje para reintentar.

## Tests automatizados

```
php artisan test --filter=Importacion
php artisan test --filter=CobroQr
php artisan test tests/Feature/RegistroMasivoLecturas  # regresión specs/015-016
php artisan test tests/Feature/RegistroMasivoRecibos   # regresión specs/023
php artisan test --filter=Pago                          # regresión specs/032/035
```
