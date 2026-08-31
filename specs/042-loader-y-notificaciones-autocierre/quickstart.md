# Quickstart: Verificación de Notificaciones Efímeras y Barra de Carga

Guía de validación end-to-end. El comportamiento temporizado y la pausa por hover se comprueban en el
navegador (el proyecto no tiene navegador de Pest configurado); la no-regresión se comprueba con la suite y
el build.

## Prerrequisitos

- Dependencias instaladas (`composer install`, `npm install`).
- Base de datos migrada con datos de prueba suficientes para generar recibos/pagos.
- Binario PHP de Herd para artisan/pest: `C:\Users\joel5\.config\herd\bin\php84\php.exe`.

## Puesta en marcha

```bash
# Terminal 1 — assets en modo desarrollo
npm run dev

# Terminal 2 — servidor (o usar el dominio de Herd)
"C:/Users/joel5/.config/herd/bin/php84/php.exe" artisan serve
```

Iniciar sesión con un usuario válido.

---

## Escenario 1 — La notificación se autocierra a los 8 s (US1 / SC-001)

1. Ir a "Conceptos de Gasto Fijo" y crear un concepto (o editar cualquier registro que muestre flash de
   éxito).
2. Al volver al listado aparece el banner verde "…correctamente".
3. **Sin mover el ratón sobre el banner**, contar el tiempo: el banner **debe** desvanecerse y desaparecer en
   **8 segundos o menos**.

**Esperado**: el banner se va solo; el resto de la página no se altera.

## Escenario 2 — El hover detiene el temporizador (US1 / FR-002 / SC-002)

1. Repetir una acción que muestre el banner.
2. Colocar el puntero sobre el banner y **mantenerlo ahí > 8 segundos**.
3. El banner **debe** seguir visible todo ese tiempo.
4. Retirar el puntero.

**Esperado**: al retirar el puntero, el temporizador se reinicia entero y el banner se cierra dentro de los
8 s siguientes (FR-003).

## Escenario 3 — Foco de teclado también pausa (US1 / FR-002)

1. Mostrar un banner.
2. Con `Tab`, llevar el foco al botón "Cerrar" (×) del banner y dejarlo ahí > 8 s.

**Esperado**: mientras el foco está dentro del banner no se autocierra; al sacar el foco, se reinicia el
conteo.

## Escenario 4 — Cierre manual (US1 / FR-004)

1. Mostrar un banner.
2. Hacer clic en el botón "Cerrar" (×).

**Esperado**: el banner se cierra de inmediato con la transición `fade`, sin esperar al temporizador.

## Escenario 5 — Notificación de error también se autocierra (US1 / Q1 / SC-001)

1. Provocar un error de operación que muestre `x-mensaje-alerta tipo="error"` como banner de resumen
   (por ejemplo, intentar una acción bloqueada por una invariante de negocio).
2. Observar el banner rojo.

**Esperado**: se autocierra con las mismas reglas (8 s, pausa por hover). Los errores de validación **por
campo** (`@error` junto al input) siguen mostrándose de forma persistente — esos no se tocan.

## Escenario 6 — Barra de carga en una navegación lenta (US2 / SC-004)

1. Abrir DevTools → Network → throttling "Slow 3G".
2. Hacer clic en un enlace del sidebar (p. ej. "Registro de Pagos").

**Esperado**: aparece una barra fina en el borde superior de la ventana dentro de ~1 s, avanza mientras
carga y desaparece al mostrarse la nueva sección.

## Escenario 7 — La barra se retira ante un error de red (US2 / FR-010 / SC-005)

1. Con throttling activo, iniciar una navegación y luego DevTools → Network → "Offline" antes de que termine
   (o cerrar el servidor).

**Esperado**: la barra desaparece (no queda corriendo). Con el servidor caído, aparece además el aviso de
error de conectividad ya existente.

## Escenario 8 — Un envío de formulario NO dispara la barra (US2 / Q2 / SC-006)

1. Con throttling activo, enviar un formulario (crear/editar cualquier registro).

**Esperado**: durante el envío, el botón se deshabilita y muestra "Guardando…"; **no** aparece la barra
superior por el envío en sí. Al renderizarse la vista destino, se ve su notificación flash (que a su vez se
autocierra).

## Escenario 9 — Sin parpadeo en navegación rápida (US2 / FR-011)

1. Sin throttling, navegar entre dos secciones livianas.

**Esperado**: la barra no se percibe (la navegación termina por debajo del umbral de ~150 ms).

## Escenario 10 — `prefers-reduced-motion` (FR-005 / FR-014)

1. Activar "reducir movimiento" en el SO (o emularlo en DevTools → Rendering → *Emulate CSS prefers-reduced-motion*).
2. Repetir los escenarios 1 y 6.

**Esperado**: el banner se cierra sin animación de desvanecido; la barra aparece/desaparece sin animar su
ancho. El comportamiento (tiempos, pausa) es el mismo.

---

## Verificación de no-regresión (SC-007 / FR-016 / FR-017)

```bash
# Suite completa — debe quedar 100% verde (433 pruebas + la nueva del componente)
"C:/Users/joel5/.config/herd/bin/php84/php.exe" artisan test

# Prueba del contrato del componente
"C:/Users/joel5/.config/herd/bin/php84/php.exe" artisan test --filter=ComponenteMensajeAlerta

# Build de assets — sin errores
npm run build
```

## Verificación documental

- `.specify/memory/constitution.md`: sección "Mensajes de Estado y Feedback" reescrita, Sync Impact Report
  presente, versión **2.2.0**.
- `DESIGN.md`: sección "Mensaje / Alert" sin "Persistent (no auto-dismiss)"; subsección de la barra de carga
  añadida.
- Comentario de encabezado de `resources/views/components/mensaje-alerta.blade.php` actualizado (ya no dice
  "no se oculta automáticamente").
- Revisión con el skill `impeccable` (`/impeccable audit` o `polish`) sobre el componente, el layout y los
  estilos nuevos, con hallazgos aplicados.
