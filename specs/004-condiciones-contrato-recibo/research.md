# Research: Condiciones del Contrato y Costos de Referencia para Recibos

**Feature**: `004-condiciones-contrato-recibo` | **Date**: 2026-08-20

## 1. Costos fijos: ¿columnas nuevas en `contratos` o tabla aparte?

**Decision**: Los cuatro costos fijos (`costo_agua`, `costo_luz`, `costo_pasadizo`, `costo_seguridad`) se agregan como columnas `decimal(12,2)` directamente en la tabla `contratos` existente (migración de alteración), y su validación se integra al `SolicitudGuardarContrato` ya existente (`app/Http/Requests/SolicitudGuardarContrato.php`, de `specs/002`) en vez de crear un Form Request completamente aislado.

**Rationale**: La especificación 004 los describe como "campos propios e independientes" del contrato (FR-002), en relación 1-a-1 sin necesidad de historial propio (eso lo cubre `Recibo`, que sí los versiona por periodo). Añadirlos a la misma tabla evita un JOIN innecesario en cada lectura de contrato y es coherente con cómo ya se modela `monto_renta` en la migración de 002. Reutilizar `SolicitudGuardarContrato` (en vez de un Form Request nuevo y desconectado) evita que el formulario de creación/edición de contrato tenga que invocar dos validaciones separadas para guardar un mismo registro; se crea igualmente `SolicitudGuardarCostosContrato` como Form Request dedicado únicamente para el caso de edición rápida de solo costos desde el detalle del contrato (acción secundaria, ver `contracts/`).

**Alternatives considered**:
- Tabla `costos_contrato` 1-a-1 separada: rechazado, sobre-normaliza una relación que siempre es uno-a-uno y nunca se consulta de forma independiente del contrato.
- Un único campo JSON de costos fijos: rechazado, el Principio I exige aprovechar tipos relacionales explícitos de PostgreSQL (columnas tipadas `NUMERIC`), y un JSON dificultaría las validaciones y el Principio IV (pruebas exhaustivas de cada campo).

## 2. `ConfiguracionGeneral`: patrón de fila única (singleton) y extensibilidad futura

**Decision**: Tabla `configuracion_general` con una única fila de id fijo (`id = 1`), poblada mediante un seeder/migración de datos que garantiza su existencia, y accedida exclusivamente a través del helper estático `ConfiguracionGeneral::actual(): self` (`firstOrCreate(['id' => 1], [...valores por defecto...])`). Esta feature solo define `correo_notificaciones_vencimiento`, pero la tabla se diseña sabiendo que `specs/005` añadirá `tarifa_luz_por_unidad` y `specs/008` añadirá `dias_anticipacion_alerta_pago` y `alerta_pago_mes_enviada_en`, ambas mediante migraciones de alteración (`ALTER TABLE configuracion_general ADD COLUMN ...`) sobre esta misma tabla, no tablas nuevas.

**Rationale**: Las tres especificaciones (004, 005, 008) hablan de "configuración a nivel de sistema" en singular y sin relación con ninguna otra entidad (no es una tabla por locación, por contrato, ni por usuario), lo cual es el caso de uso canónico de un patrón de fila única. Consolidarlas en una sola tabla evita crear tres tablas de configuración dispersas (`configuracion_notificaciones`, `configuracion_tarifas`, `configuracion_alertas_pago`) que en la práctica siempre se leerían juntas al renderizar cualquier pantalla administrativa de configuración. El helper `actual()` centraliza el acceso y evita que cada controlador tenga que reimplementar el `firstOrCreate`.

**Alternatives considered**:
- Tabla clave-valor genérica (`configuraciones` con columnas `clave`/`valor`): rechazado, complica el casteo de tipos (decimal vs entero vs string) y las validaciones tipadas por campo que exige el Principio V; una tabla de columnas explícitas es más simple de testear.
- Una tabla de configuración separada por cada especificación (004, 005, 008 con sus propias tablas): rechazado, fragmenta innecesariamente una única pantalla administrativa de "Configuración General" en tres formularios y tres controladores redundantes.

## 3. Envío de correo: síncrono vs. cola

**Decision**: El envío del `Mailable ContratoProximoAVencer` se realiza de forma síncrona (`Mail::to(...)->send(...)`, sin `->queue()`) dentro de `ServicioNotificacionVencimientoContrato`, ejecutado por el comando artisan `contratos:verificar-vencimientos` programado diariamente vía `Schedule::command(...)->daily()` en `routes/console.php`.

**Rationale**: `specs/002-gestion-contratos/research.md` §2 ya estableció que el proyecto evita depender de colas persistentes con workers de larga duración (no garantizados en shared hosting); aunque `QUEUE_CONNECTION=database` está configurado por defecto en `.env.example`, procesar la cola requeriría un worker corriendo continuamente (`php artisan queue:work`), que no es el patrón adoptado por este proyecto (solo se asume el cron de `schedule:run`). Dado que la verificación es diaria y el volumen de contratos es acotado (cientos, no miles, consistente con `specs/002`), el envío síncrono dentro del propio comando programado es suficiente y evita la complejidad operativa de un worker adicional.

**Alternatives considered**:
- `Mail::queue()` con `QUEUE_CONNECTION=database` y un cron adicional para `queue:work --stop-when-empty`: viable pero rechazado por ahora para no introducir un segundo proceso programado cuando el volumen no lo justifica; se documenta como mejora futura si el volumen de contratos crece significativamente.
- Enviar el correo directamente desde un observer del modelo `Contrato` al guardar: rechazado, la verificación de vencimiento debe ejecutarse aunque el contrato no se edite ese día (el paso del tiempo por sí solo activa el hito), lo cual requiere necesariamente un proceso programado, no un evento de guardado.

## 4. Reinicio de hitos de notificación al editar `fecha_fin` (Edge Case)

**Decision**: Los tres campos `notificado_30_dias_en`/`notificado_15_dias_en`/`notificado_7_dias_en` se reinician a `null` automáticamente cuando `fecha_fin` cambia de valor, implementado como lógica explícita en `ContratoController@update` (comparando el valor original vs. el nuevo antes de persistir, dentro de la misma `DB::transaction` que guarda los demás cambios del contrato), no como un Eloquent Observer global.

**Rationale**: Un Observer (`Contrato::updating()`) sería más "automático", pero acoplaría una regla de negocio muy específica de esta feature al ciclo de vida genérico del modelo, dificultando su prueba aislada y su lectura por otro desarrollador que edite `Contrato` por otros motivos (ej. specs 006-009 también modifican `Contrato`). Mantener la comparación explícita en el punto de entrada de la escritura (controlador/Service) hace que la regla sea local, testeable con Pest de forma directa, y fácil de ubicar cuando se audite el cumplimiento de FR de esta especificación.

**Alternatives considered**:
- Eloquent Observer (`ContratoObserver::updating`): rechazado por las razones de acoplamiento anteriores; se documenta como refactor opcional si en el futuro más reglas de "reinicio ante cambio de fecha" se acumulan sobre `Contrato`.
- Recalcular los hitos en cada lectura en vez de persistirlos: rechazado, la especificación exige explícitamente registrar cuándo se envió cada notificación (columnas de marca de tiempo) para evitar duplicados de forma auditable (FR-004).

## 5. Ausencia de roles/permisos (Administrador vs. otros)

**Decision**: Consistente con `specs/001`, `specs/002` y `specs/003` (cuyos contratos de rutas solo verifican `middleware(['auth'])` sin distinguir roles, porque el modelo `User` no tiene campo de rol), la pantalla de `ConfiguracionGeneral` y las acciones de recibo se protegen únicamente con `middleware(['auth'])`. No se introduce un sistema de roles en esta feature.

**Rationale**: La especificación 004 asume una pantalla "accesible solo a Administradores" (Asunción A-005), pero ninguna especificación previa (001-003) implementó un mecanismo de roles — todo usuario autenticado es tratado como Administrador único del sistema. Introducir roles sería una ampliación de alcance no solicitada por ninguna de las 9 especificaciones leídas; se documenta esta limitación para que el usuario decida si una futura especificación de "usuarios y roles" es necesaria.

**Alternatives considered**:
- Agregar un campo `rol` a `User` en esta feature: rechazado, fuera del alcance textual de la especificación 004 (que solo pide "accesible solo a Administradores", no un sistema de roles), y ninguna spec 001-009 lo define explícitamente.

## 6. Framework de pruebas

**Decision**: Pest, consistente con `specs/001-003`.

**Rationale**: Ya adoptado por el proyecto.

**Alternatives considered**: Ninguna — decisión ya tomada a nivel de proyecto.
