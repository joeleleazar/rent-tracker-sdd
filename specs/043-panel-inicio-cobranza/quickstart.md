# Quickstart: Verificación del Panel de Inicio

Guía de validación end-to-end. Combina pruebas automatizadas (Pest) con una verificación manual en el
navegador y una medición de rendimiento.

## Prerrequisitos

- Dependencias instaladas (`composer install`, `npm install`), base migrada.
- Binario PHP de Herd: `C:\Users\joel5\.config\herd\bin\php84\php.exe`.
- Datos con al menos: un recibo no anulado con saldo pendiente y fecha límite ya vencida (moroso), otro con
  saldo pendiente y fecha límite futura (próximo vencimiento), un recibo pagado por completo, un recibo
  anulado, y un contrato `estado = 'activo'` con `fecha_fin` dentro de 5 días.

## Puesta en marcha

```bash
npm run dev
"C:/Users/joel5/.config/herd/bin/php84/php.exe" artisan serve
```

Iniciar sesión con un usuario Master y (en otra sesión) con un Administrador.

---

## Escenario 1 — El panel es la pantalla de inicio (US4 / FR-001, FR-003)

1. Iniciar sesión como Master → se aterriza en `/dashboard` y se ve el panel (no el árbol de locaciones).
2. Cerrar sesión, iniciar como Administrador → mismo panel.
3. Sin sesión, abrir `/dashboard` directamente → redirección a `login`.

**Esperado**: ambos perfiles ven el panel; el invitado no.

## Escenario 2 — Solo lectura (US4 / FR-002, SC-008)

1. Revisar el panel completo.

**Esperado**: no hay botón "Registrar pago", "Anular recibo" ni "Editar contrato"; las únicas interacciones
son los dos filtros del bloque de morosos y los enlaces a detalle de recibo / detalle de contrato.

## Escenario 3 — Listado de morosos y su resumen (US1 / FR-005, FR-010–FR-017)

1. Ubicar el recibo moroso sembrado en la tabla.

**Esperado**: aparece con inquilino, local (breadcrump corto), periodo, total, pagado, saldo pendiente, fecha
límite (último sábado del mes de su periodo) y días de atraso = "hoy − esa fecha". Las filas van de mayor a
menor atraso. El recibo pagado y el anulado NO están. Las tarjetas de resumen (recibos morosos, inquilinos,
monto adeudado vencido) cuadran con las filas, y la suma de los cuatro tramos de antigüedad = monto adeudado
vencido.

## Escenario 4 — Estado vacío de morosos (US1 / FR-014)

1. En un entorno sin ningún recibo moroso, abrir el panel.

**Esperado**: donde iría la tabla se muestra el estado vacío del proyecto con "No hay recibos vencidos
impagos".

## Escenario 5 — Filtros de morosos y su efecto en las tarjetas (US1 / FR-018–FR-022, SC-005)

1. Aplicar `tramo = "31 a 60"`.
2. Aplicar además `locacion = <una galería o piso>`.

**Esperado**: la tabla muestra solo los recibos morosos que están en ese tramo **y** cuya locación cuelga de
esa rama (locación elegida + descendientes). Las tarjetas de resumen y el desglose por antigüedad se
**recalculan** sobre ese subconjunto. Quitar los filtros restaura el total del negocio. Ningún dato cambió.
Repetir con JavaScript deshabilitado usando el botón "Filtrar" → mismo resultado por recarga.

## Escenario 6 — Próximos vencimientos (US2 / FR-023–FR-027)

1. Ubicar el recibo con saldo pendiente y fecha límite futura.

**Esperado**: aparece en el bloque de próximos vencimientos (no en morosos), con días restantes ≥ 0, orden
por fecha límite ascendente; la tarjeta resumen indica cantidad y suma de saldos en plazo. Un recibo con
fecha límite == hoy aparece aquí con 0 días restantes, no en morosos.

## Escenario 7 — Indicadores del periodo (US3 / FR-028–FR-031)

1. Comparar las cinco stat cards contra los datos del mes calendario en curso.

**Esperado**:
- "Facturado del periodo" = Σ totales de recibos no anulados con periodo = mes en curso.
- "Cobrado de recibos del periodo" = Σ pagos imputados a esos recibos, sin importar cuándo se pagaron.
- "Recaudado este mes" = Σ pagos con fecha de pago dentro del mes en curso, de cualquier periodo, excluyendo
  pagos de recibos anulados. (Verificar que un pago de un recibo de un mes anterior, hecho este mes, suma
  aquí pero NO en "cobrado de recibos del periodo".)
- "Tasa de cobranza" = cobrado-de-recibos-del-periodo ÷ facturado, en %. Con facturado 0 → "—" / "sin datos".
- "Cartera total por cobrar" = Σ saldos pendientes de todos los recibos no anulados de cualquier periodo.

## Escenario 8 — Contratos por vencer, grupos acumulativos (US3 / FR-032, SC-005a)

1. Con el contrato `activo` que vence en 5 días, revisar el bloque.

**Esperado**: ese contrato aparece en los tres grupos (7, 15 y 30 días). Un contrato que vence en 20 días
aparece solo en el grupo de 30. Un contrato con `fecha_fin` ya pasada, o con `estado` distinto de `activo`,
no aparece en ninguno. Cada contrato listado enlaza a su detalle.

## Escenario 9 — Rendimiento (SC-006)

1. Sembrar ≥ 300 recibos no anulados (varios periodos, con y sin pagos) y ≥ 100 contratos.
2. Cargar `/dashboard` y medir el tiempo hasta render completo y el número de consultas SQL.

**Esperado**: render en < 2 s; ≤ 5 consultas para todo el panel (1 si no hay filtro de locación activo, +1
si lo hay).

---

## Verificación automatizada

```bash
# Unit del servicio de cálculo
"C:/Users/joel5/.config/herd/bin/php84/php.exe" artisan test --filter=ServicioPanelCobranza

# Feature del controlador
"C:/Users/joel5/.config/herd/bin/php84/php.exe" artisan test --filter=PanelInicio

# Suite completa — sin regresiones (incluye ajuste de las pruebas que asumían
# que `dashboard` redirige a locaciones.index)
"C:/Users/joel5/.config/herd/bin/php84/php.exe" artisan test

# Assets
npm run build
```

## Verificación de interfaz

- Revisión con el skill `impeccable` (`/impeccable audit` o `polish`) sobre
  `resources/views/panel/**` y el ítem de navegación reetiquetado en
  `resources/views/components/layouts/app-bootstrap.blade.php`; aplicar hallazgos.
- Si la fila de "stat cards" del panel es un patrón nuevo, documentarla en `DESIGN.md` (`/impeccable
  document`).
- Sin scroll horizontal en 375 px ni en 1280 px; montos con `.cifra` alineados en columna; badges de tramo
  de atraso con color semántico y etiqueta de texto (nunca solo color).
