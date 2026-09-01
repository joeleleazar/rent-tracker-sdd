# Contrato de Interfaz: Panel de Inicio

No hay API HTTP nueva de datos (JSON), ni escritura. El único contrato es el de **una ruta de lectura** que
renderiza HTML y su comportamiento frente a los parámetros de filtro.

---

## A. Ruta

| | |
|---|---|
| Método | `GET` |
| URI | `/dashboard` (nombre de ruta **`dashboard`**, reutilizado) |
| Middleware | `['auth', 'cuenta.activa']` — **sin** `perfil.master` |
| Acción | `App\Http\Controllers\ControladorPanelInicio@index` |
| Respuesta | `200` con la vista `panel.inicio` (HTML) |
| Invitado | `302` a `login` |
| Escritura | ninguna — la ruta no muta datos bajo ninguna combinación de parámetros |

La ruta `/` autenticada y `redirect()->intended(route('dashboard'))` de
`AuthenticatedSessionController` quedan apuntando a esta acción sin cambios propios.

### Parámetros (query string, todos opcionales)

| Parámetro | Tipo | Valores válidos | Inválido / ausente |
|---|---|---|---|
| `tramo` | string | `1-30`, `31-60`, `61-90`, `90+` | se ignora → sin filtro de tramo |
| `locacion` | int | id de una locación existente | se ignora → sin filtro de rama |

- Ambos parámetros pueden combinarse (AND).
- Solo afectan al **bloque de morosos** (tabla + tarjetas de resumen + desglose por antigüedad).
- No afectan al bloque de próximos vencimientos ni al de indicadores.

---

## B. Forma de la vista (bloques y datos que recibe)

La vista recibe del controlador (nombres orientativos):

```
morosos            => Collection<FilaMoroso>        // ya filtrada y ordenada por díasDeAtraso desc
resumenMorosidad   => ResumenMorosidad             // recalculado sobre `morosos` (post-filtro)
filtros            => { tramo: ?string, locacion: ?int, locacionesDisponibles: array }
proximos           => Collection<FilaProximoVencimiento>  // ordenada por fechaLimite asc
resumenProximos    => { cantidad: int, montoTotal: float }
indicadores        => IndicadoresCobranza
contratosPorVencer => { dentro7: Collection, dentro15: Collection, dentro30: Collection }
```

Ver `data-model.md §2` para la definición de cada estructura derivada.

### B.1 Bloque Morosos (US1)

- **Tarjetas de resumen**: `cantidadRecibos`, `cantidadInquilinos`, `montoAdeudadoVencido` (con `S/` y
  `.cifra`).
- **Desglose por antigüedad**: cuatro tarjetas/celdas —"1 a 30", "31 a 60", "61 a 90", "más de 90"— cada una
  con `{ cantidad, monto }`. `Σ monto == montoAdeudadoVencido`.
- **Filtros**: `<form method="GET" action="{{ route('dashboard') }}">` con `<select name="tramo">` y
  `<select name="locacion">`; auto-envío por `hx-get`+`hx-trigger="change"` sobre el contenedor del bloque, y
  botón `type="submit"` "Filtrar" visible para el camino sin JavaScript.
- **Tabla** (`table-responsive` + `table-hover`), columnas en este orden: Inquilino · Local (breadcrumb) ·
  Periodo · Total (`S/`) · Pagado (`S/`) · Saldo pendiente (`S/`) · Fecha límite · Días de atraso (badge de
  color semántico por tramo). Cada fila enlaza a `route('recibos.show', $fila->recibo)`.
- **Estado vacío**: si `morosos` está vacío (con o sin filtros), el componente de estado vacío del proyecto
  con el mensaje **"No hay recibos vencidos impagos"** (sin filtros) o el mensaje de "ningún resultado para
  el filtro" (con filtros) — nunca una tabla con encabezado y cero filas.

### B.2 Bloque Próximos vencimientos (US2)

- **Tarjeta de resumen**: `cantidad` + `montoTotal` (`S/`, `.cifra`).
- **Tabla**: Inquilino · Local · Periodo · Saldo pendiente (`S/`) · Fecha límite · Días restantes. Orden por
  `fechaLimite` asc. Cada fila enlaza a `route('recibos.show', $fila->recibo)`.
- **Estado vacío**: "No hay pagos próximos a vencer".

### B.3 Bloque Indicadores (US3)

- **Cinco stat cards**: Facturado del periodo · Cobrado de recibos del periodo · Recaudado este mes · Tasa de
  cobranza del periodo (`%` o "—" si facturado 0) · Cartera total por cobrar. Montos con `S/` y `.cifra`.
- **Contratos por vencer**: tres grupos rotulados "Vencen en 7 días", "en 15 días", "en 30 días"
  (acumulativos). Cada grupo lista sus contratos (local + inquilino principal + `fechaFin` + días restantes),
  cada uno enlazando a `route('contratos.show', $contrato)`. Grupo sin contratos → línea "ninguno" discreta,
  no se oculta el rótulo.

---

## C. Contrato negativo (no-regresión)

- La respuesta **no** contiene ningún `<form>` que apunte a rutas de escritura de pagos/recibos/contratos
  (`pagos.store`, `recibos.update`, `recibos.estado.update`, `contratos.update`, …) ni botones "Registrar
  pago" / "Anular" / "Editar".
- Ninguna ruta, controlador, Form Request ni dato existente cambia de comportamiento. El único cambio en
  código existente es: (a) el cuerpo de `Route::get('/dashboard')` pasa de `redirect()` a la acción del
  controlador (mismo nombre de ruta); (b) el `<li>` del sidebar que apunta a `route('dashboard')` cambia de
  etiqueta e ícono.
- La suite de pruebas existente sigue en verde; las pruebas que hoy afirmen que `dashboard` **redirige** a
  `locaciones.index` se actualizan para afirmar que **renderiza** el panel (SC-007).
- Aplicar cualquier combinación de `tramo` + `locacion` no cambia ningún registro (FR-037 / SC-005).

---

## D. Rendimiento (verificable)

- El panel completo se renderiza en **< 2 s** con ≥ 300 recibos no anulados y ≥ 100 contratos (SC-006).
- Presupuesto: **≤ 5 consultas** a la base para todo el panel (ver `plan.md` → Performance Goals). Se puede
  verificar con un `DB::listen` / contador de consultas en el test de rendimiento del `quickstart.md`.
