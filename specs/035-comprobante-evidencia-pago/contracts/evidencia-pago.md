# Contrato: subir y consultar la evidencia de un pago

## Rutas

| Método | Ruta | Nombre | Acción |
|---|---|---|---|
| POST | `/pagos/{pago}/evidencia` | `pagos.evidencia.store` | Sube (o reemplaza) el archivo de evidencia de ese pago |
| GET | `/pagos/{pago}/evidencia` | `pagos.evidencia.show` | Descarga/muestra el archivo de evidencia ya subido |

Ambas exigen sesión autenticada, igual que el resto de `routes/web.php`.

## `pagos.evidencia.store` — `SolicitudSubirEvidenciaPago`

| Campo | Regla | Mensaje si falla |
|---|---|---|
| `archivo` | `required`, `file`, `mimes:pdf,jpg,jpeg,png`, `max:10240` (10 MB) | "Debe seleccionar un archivo." / "El archivo debe ser una imagen (JPG/PNG) o un PDF." / "El archivo no puede superar los 10 MB." |

**Efecto** (research.md Decisión 3, dentro de una `DB::transaction`):
1. Si el pago ya tenía una evidencia (`tieneEvidencia()` es `true`), se borra el archivo físico anterior
   del disco `local`.
2. Se guarda el archivo nuevo en `pagos/{id}/` del disco `local`.
3. Se actualizan `evidencia_ruta`, `evidencia_nombre_archivo` y `evidencia_tipo` (`pdf` si el mime es
   `application/pdf`, `imagen` en cualquier otro caso admitido) con los datos del archivo nuevo.

**Precondición de negocio**: ninguna — a diferencia de registrar/editar/eliminar un pago (specs/032), subir
evidencia sobre un pago de un recibo ya anulado sigue permitido (el pago en sí ya existía antes de anular el
recibo, y la evidencia documenta un hecho pasado, no una operación nueva sobre el recibo).

## `pagos.evidencia.show`

Sin cuerpo de solicitud. Responde el archivo ya guardado (`Storage::disk('local')->response(...)`, mismo
mecanismo que ya usa `DocumentoContratoController::show()`), con su `evidencia_nombre_archivo` original.

**Caso sin evidencia**: si el pago todavía no tiene ninguna evidencia subida, la ruta responde 404 — el
punto de entrada visible para el usuario (el detalle del recibo) ya distingue este caso con un indicador de
"pendiente de evidencia" (spec.md FR-008) en vez de mostrar un enlace roto.
