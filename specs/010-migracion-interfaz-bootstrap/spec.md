# Feature Specification: Migración de la Interfaz a Bootstrap 5

**Feature Branch**: `010-migracion-interfaz-bootstrap`

**Created**: 2026-08-21

**Status**: Draft

**Input**: User description: "Migrar toda la interfaz de usuario del sistema (actualmente construida con Tailwind CSS v4 + Alpine.js sobre Blade, cubriendo las 9 features 001-009 ya implementadas) a Bootstrap 5.3, manteniendo exactamente los mismos principios de accesibilidad Senior-First ya vigentes y sin cambiar ninguna regla de negocio, validación, ruta, controlador ni modelo ya implementados — es exclusivamente un reemplazo del framework CSS/JS de presentación. Priorizar en el mismo orden de criticidad que las features originales: P1 (001-004), P2 (005, 007, 009), P3 (006 con gráfico de consumo, 008)."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Migración de las Vistas Fundamentales del Negocio (Priority: P1)

Como Administrador, quiero que las pantallas de locaciones, contratos, representantes y condiciones/costos del contrato (features 001 a 004) se vean y se comporten igual que hoy pero construidas con componentes Bootstrap 5 (cards, tablas responsive, modales, formularios), para seguir gestionando el núcleo del negocio sin perder ninguna funcionalidad ni el nivel de accesibilidad Senior-First ya alcanzado.

**Why this priority**: Son las pantallas de mayor uso diario y las que sustentan todo el resto del sistema (un contrato no puede existir sin una locación, un representante o sus costos). Migrarlas primero permite validar tempranamente que el nuevo sistema de componentes cumple los mismos estándares de accesibilidad antes de extenderlo al resto de la aplicación.

**Independent Test**: Se puede verificar navegando el CRUD completo de locaciones (listado, detalle con breadcrumb, creación, edición, eliminación bloqueada con hijas) y el de contratos (creación con validación de solapamiento, representantes, costos y documentos) usando exclusivamente componentes Bootstrap 5, y comprobando que toda la suite de pruebas automatizadas de esas features sigue pasando sin modificar su lógica de aserción de negocio.

**Acceptance Scenarios**:

1. **Given** el listado de locaciones alquilables, **When** el Administrador lo visualiza, **Then** se presenta con tarjetas o tabla responsive de Bootstrap 5, manteniendo tipografía mínima de 18px, contraste WCAG AA/AAA y el breadcrumb jerárquico truncado a 3 niveles.
2. **Given** el formulario de creación de un contrato con representantes, costos y garantía, **When** el Administrador lo completa y guarda, **Then** el sistema guarda el contrato exactamente con las mismas reglas de validación ya vigentes (solapamiento, mínimo un representante, exactamente un Principal), mostrando los errores en componentes de alerta Bootstrap en vez de los componentes Tailwind actuales.
3. **Given** un intento de eliminar una locación con sub-locaciones asociadas, **When** el Administrador confirma la eliminación en el modal Bootstrap, **Then** el sistema bloquea la operación con el mismo mensaje explícito ya definido, mostrado en un modal Bootstrap en vez del modal Alpine.js actual.

---

### User Story 2 - Migración de las Vistas del Flujo de Facturación (Priority: P2)

Como Administrador, quiero que las pantallas de lecturas de medidor, recibos por periodo, estado y envío de recibos, y garantía del contrato (features 005, 007 y 009) usen componentes Bootstrap 5, para completar la migración del flujo operativo mensual de cobro sin perder ninguna validación ni comportamiento actual.

**Why this priority**: Cierra el segundo bloque de funcionalidad más usado (el ciclo mensual de facturación), una vez que el bloque fundamental (P1) ya está migrado y validado.

**Independent Test**: Se puede verificar registrando una lectura de medidor, generando un recibo con prorrateo, cambiando su estado (pendiente/pagado/anulado), generando la imagen para WhatsApp y registrando la resolución de una garantía, todo con componentes Bootstrap 5, y comprobando que la suite de pruebas de esas features sigue pasando.

**Acceptance Scenarios**:

1. **Given** un periodo con lectura de medidor registrada, **When** el Administrador genera el recibo, **Then** el formulario de conceptos editables se presenta con `input-group` de Bootstrap con el símbolo "S/", conservando la misma lógica de cálculo de consumo y prorrateo.
2. **Given** un recibo pendiente, **When** el Administrador cambia su estado a "Pagado", **Then** el selector de estado (implementado hoy con botones Senior-First) se reemplaza por un `btn-group`/`btn-check` de Bootstrap con el mismo comportamiento y confirmación donde corresponda.
3. **Given** un contrato con garantía entregada, **When** el Administrador registra la resolución con retención parcial, **Then** el formulario de resolución (monto devuelto, retenido, motivo) se presenta con componentes Bootstrap, conservando la validación de cuadre exacto y el mensaje de error correspondiente.

---

### User Story 3 - Migración de las Vistas Complementarias y Panel de Consumo Histórico (Priority: P3)

Como Administrador, quiero que el historial de lecturas de medidor (feature 006) y el panel de prorrateo/alertas de pago (feature 008) usen componentes Bootstrap 5, incluyendo un gráfico visual del consumo histórico que hoy no existe, para completar la migración de todas las pantallas del sistema y ganar una vista más clara de tendencias de consumo.

**Why this priority**: Son pantallas de consulta y configuración de menor frecuencia de uso que las de P1/P2; el gráfico de consumo es una mejora visual nueva, no una migración de algo existente, por lo que se prioriza al final.

**Independent Test**: Se puede verificar accediendo al historial de lecturas de una locación con 6 o más periodos registrados y comprobando que se muestra un gráfico de líneas o barras con la tendencia de consumo, además de la tabla histórica ya existente migrada a Bootstrap; y accediendo al panel de configuración de alertas de pago para comprobar que el formulario y el listado de alertas usan componentes Bootstrap.

**Acceptance Scenarios**:

1. **Given** una locación con historial de 6 periodos de lectura de medidor, **When** el Administrador visualiza su historial, **Then** se muestra un gráfico de consumo por periodo además de la tabla histórica, ambos con tipografía y contraste Senior-First.
2. **Given** el panel de configuración general, **When** el Administrador ajusta los días de anticipación para la alerta de fecha límite de pago, **Then** el formulario se presenta con componentes Bootstrap, conservando la misma validación y persistencia ya implementadas.

### Edge Cases

- **Lógica de negocio embebida en las vistas actuales**: Algunas vistas Blade actuales calculan o derivan presentación con lógica no trivial (ej. el indicador de omisión "..." del breadcrumb truncado, o el color condicional de un badge según el estado del recibo o de la garantía). Al migrar a Bootstrap, esa lógica de derivación de datos (ya sea en el modelo/helper PHP o en la vista) MUST preservarse exactamente igual; solo cambia el marcado HTML/CSS que la presenta, nunca el criterio de negocio que decide qué mostrar.
- **Pruebas automatizadas existentes basadas en contenido HTML**: La suite Pest actual incluye aserciones sobre el contenido visible de las vistas (`assertSee`, `assertSessionHasErrors`) para verificar el comportamiento de cada feature. La migración de componentes MUST verificarse contra la suite completa existente sin modificar las aserciones de negocio; si una aserción depende de un texto visible que se mantiene igual, debe seguir pasando sin cambios. Solo se ajustan aserciones que dependieran de una estructura de marcado específica ya obsoleta (si las hubiera), nunca el criterio de negocio verificado.
- **Coexistencia temporal de Tailwind y Bootstrap durante la migración incremental**: Mientras la migración avanza feature por feature (P1 → P2 → P3), ambas hojas de estilo convivirán en el proyecto. El sistema MUST seguir siendo completamente funcional y visualmente consistente en cada etapa intermedia: las vistas ya migradas se ven con Bootstrap, las pendientes se ven con Tailwind, sin que una interfiera visualmente con la otra (ej. sin clases de un framework aplicadas por error sobre marcado del otro).
- **Retiro del sistema de estilos anterior**: Una vez completadas las tres historias de usuario (todas las vistas migradas), el sistema de utilidades Tailwind/Alpine específico de esta aplicación (clases `btn-senior-*`, `campo-senior`, `etiqueta-senior`, componente `x-modal`) deja de usarse en el marcado nuevo. Su remoción definitiva del proyecto se limita a este punto final, no a cada migración parcial.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST presentar todas las vistas de las 9 features ya implementadas (001 a 009) usando componentes de Bootstrap 5.3 (cards, tablas responsive, modales, formularios, badges, grupos de botones) en lugar de los componentes Tailwind/Alpine.js custom actuales, sin alterar ninguna ruta, controlador, modelo, servicio o regla de validación ya implementada.
- **FR-002**: El sistema MUST conservar, en cada vista migrada, los mismos estándares de accesibilidad Senior-First ya vigentes: tipografía base mínima de 18px, contraste WCAG AA/AAA, botones y áreas táctiles de mínimo 48x48px, navegación plana sin menús desplegables complejos, y confirmación explícita mediante modal antes de cualquier acción destructiva o irreversible.
- **FR-003**: El sistema MUST migrar las vistas en tres bloques de prioridad: (P1) locaciones, contratos, representantes y condiciones/costos de contrato; (P2) lecturas de medidor, recibos por periodo, estado y envío de recibos, y garantía de contrato; (P3) historial de lecturas de medidor con gráfico de consumo y prorrateo/alertas de pago.
- **FR-004**: El sistema MUST mantener el comportamiento visible de cada pantalla (mensajes de error, mensajes de éxito, datos mostrados, condiciones de habilitación/deshabilitación de controles) idéntico al actual durante y después de la migración, verificable mediante la suite de pruebas automatizadas existente.
- **FR-005**: El sistema MUST agregar un gráfico visual de consumo histórico de medidor (por periodo) a la vista de historial de lecturas (feature 006), como mejora nueva incluida en el alcance de esta migración.
- **FR-006**: El sistema MUST permitir que, durante la migración incremental, las vistas ya migradas (Bootstrap) y las pendientes (Tailwind/Alpine) coexistan sin conflicto visual ni funcional en la misma aplicación.
- **FR-007**: El sistema MUST retirar el sistema de estilos Tailwind/Alpine.js específico de esta aplicación (utilidades y componentes Senior-First custom) únicamente al completarse la migración de las tres historias de usuario, no antes.

### Key Entities

*No aplica: esta feature no introduce ni modifica entidades de datos; reutiliza exactamente el mismo modelo de datos de las features 001 a 009.*

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de las pantallas de las 9 features existentes quedan migradas a componentes Bootstrap 5, sin ninguna vista pendiente en Tailwind/Alpine al finalizar las tres historias de usuario.
- **SC-002**: El 100% de las pruebas automatizadas existentes (191 al momento de esta especificación) continúan pasando sin modificar ninguna aserción de regla de negocio, solo ajustes de aserciones dependientes de marcado obsoleto si corresponde.
- **SC-003**: Todas las vistas migradas cumplen el estándar de accesibilidad del proyecto (tipografía mínima 18px, contraste mínimo 4.5:1, áreas táctiles mínimas 48x48px), verificado pantalla por pantalla.
- **SC-004**: Un Administrador puede completar los mismos flujos críticos (registrar un contrato completo, generar y cobrar un recibo) en el mismo tiempo o menos que con la interfaz actual, sin necesidad de reaprender la ubicación de ninguna acción.

## Assumptions

- **A-001**: Bootstrap 5.3 y sus componentes JavaScript nativos (modal, collapse, dropdown) cubren, sin necesidad de Alpine.js, toda la interactividad ligera que hoy provee Alpine (apertura/cierre de modales de confirmación, formularios secundarios inline).
- **A-002**: No se requiere ningún cambio de UX/flujo respecto al sistema actual: esta migración es exclusivamente visual/técnica, no una nueva ronda de diseño de experiencia de usuario.
- **A-003**: El gráfico de consumo histórico (FR-005) se implementa con una librería de gráficos ligera compatible con Bootstrap (p. ej. Chart.js), sin que esta especificación fije la herramienta exacta; esa decisión se documentará en la fase de planificación técnica.
- **A-004**: La migración no requiere mantener ambas interfaces disponibles simultáneamente para el usuario final (no es un feature flag por usuario); la coexistencia temporal (Edge Case) es solo un estado intermedio del desarrollo, no una opción visible para el Administrador.
- **A-005**: Los datos y el volumen de uso (hasta ~1,000 locaciones, cientos de contratos) siguen siendo los mismos que en las especificaciones 001-009; esta migración no cambia los supuestos de escala ya documentados.
