# Phase 0 — Research: Gestión de Usuarios por Perfiles

Decisiones tomadas para resolver los puntos abiertos del Technical Context. La clarificación funcional
principal (alcance del Administrador) ya quedó resuelta en `spec.md` (sección Clarifications, 2026-08-27):
**todo el CRUD de usuarios es exclusivo del `master`**.

---

## Decisión 1 — Representación del perfil: enum PHP respaldado por string

**Decisión**: Añadir una columna `perfil` de tipo `string` a `users` y castearla en el modelo con un enum
PHP respaldado por string `App\Enums\PerfilUsuario { case Master = 'master'; case Administrador =
'administrador'; }`. Restricción `CHECK (perfil in ('master','administrador'))` a nivel de migración.

**Rationale**:
- El conjunto de perfiles es cerrado y no se administra desde la interfaz (spec.md Key Entities), por lo que
  una tabla `perfiles` + relación sería complejidad sin beneficio.
- Los enums respaldados casteados por Eloquent son el patrón idiomático en Laravel moderno (13.x) para
  campos de dominio cerrado, dan seguridad de tipos en controladores/servicios/tests y evitan literales
  mágicos dispersos. El proyecto aún no tiene `app/Enums/`, pero introducirlo aquí es coherente con el
  Principio I ("convenciones idiomáticas ... del framework en su versión estable más reciente").
- `CHECK` en BD mantiene la integridad aunque algún camino evada el modelo (Principio I y V).

**Alternativas consideradas**:
- *Columna string libre + constantes de clase*: es lo que hoy usa `recibos.estado`, pero para un campo de
  seguridad preferimos el tipado fuerte del enum y su exhaustividad en `match`.
- *Tabla `perfiles` + FK*: descartada; no hay perfiles dinámicos ni permisos granulares en el alcance.
- *Paquete de roles/perm-isos (spatie/laravel-permission)*: descartado; introduce dependencia y modelo
  mental desproporcionados para dos perfiles y una única capacidad diferenciadora.

---

## Decisión 2 — Gate `master`: middleware de ruta dedicado

**Decisión**: Crear `App\Http\Middleware\RequerirPerfilMaster` (alias `perfil.master`) que devuelve `403`
(abort con mensaje en español) si `auth()->user()` no es `master`. Agrupar **todas** las rutas de la
sección `usuarios.*` bajo `Route::middleware(['auth', 'perfil.master'])`. Adicionalmente registrar un Gate
`Gate::define('gestionar-usuarios', fn (User $u) => $u->esMaster())` para usarlo en las vistas
(`@can('gestionar-usuarios')`) y mantener una única fuente de verdad.

**Rationale**:
- El requisito exige bloqueo en servidor en cada ruta, no sólo ocultar el enlace (FR-003/FR-005, SC-002).
  Un middleware de grupo garantiza que ninguna acción nueva quede sin proteger por olvido.
- Un Gate nombrado permite que la vista (`@can`) y el middleware compartan la misma condición y facilita el
  test.

**Alternativas consideradas**:
- *Sólo `@can` en las vistas*: insuficiente — no protege el acceso directo por URL.
- *Policy sobre el modelo `User`*: válida, pero una Policy por-modelo encaja mejor cuando hay acciones
  mixtas por rol sobre instancias; aquí toda la sección es "sólo master", y un middleware de grupo lo
  expresa con menos ceremonia. Se puede añadir una `UserPolicy` más adelante sin fricción.

---

## Decisión 3 — Cuentas inactivas: middleware que corta el acceso en la siguiente petición

**Decisión**: Crear `App\Http\Middleware\AsegurarCuentaActiva` (alias `cuenta.activa`) que, en cada petición
autenticada, si `auth()->user()->activo === false`, cierra la sesión (`Auth::logout`, invalida y regenera
token) y redirige a `login` con un mensaje ("Tu cuenta fue desactivada. Contacta a un administrador."). Se
añade al grupo de rutas web autenticadas (junto a `auth`).

**Rationale**:
- FR-013 / SC-004 exigen que una cuenta recién desactivada pierda acceso en su **siguiente acción**, aunque
  tenga sesión viva. Comprobar `activo` en cada request es la forma directa y probada.
- Cerrar la sesión (en vez de sólo abortar) evita un bucle de 403 y deja al usuario en una pantalla con
  explicación.

**Alternativas consideradas**:
- *Invalidar todas las sesiones del usuario al desactivarlo* (borrando filas de la tabla `sessions`):
  posible pero más frágil (depende del driver de sesión = database) y no cubre "cambio de perfil aplica en
  el siguiente login" de forma uniforme. El middleware es driver-agnóstico.
- *No hacer nada hasta el próximo login*: incumple SC-004.

**Nota sobre cambio de perfil**: los permisos derivan del valor `perfil` leído en cada request desde la BD
(no de un claim cacheado en sesión), por lo que un cambio de perfil aplica en la siguiente petición sin
trabajo extra. La spec sólo exige "a más tardar en el siguiente inicio de sesión" (Edge Cases), así que
esto la satisface con holgura.

---

## Decisión 4 — Invariante "siempre ≥1 master activo": servicio + transacción con bloqueo

**Decisión**: Centralizar en `App\Services\ServicioAdministracionUsuarios` las operaciones que pueden
romper la invariante (desactivar, eliminar, cambiar perfil de un `master` a `administrador`). Cada una
corre en `DB::transaction`; antes de aplicar el cambio hace
`User::query()->where('perfil','master')->where('activo',true)->lockForUpdate()->count()` y aborta con
`ValidationException` / `422` y mensaje en español si el resultado dejaría el conteo en 0. También rechaza
que un usuario se desactive a sí mismo o se quite a sí mismo el perfil master siendo el único (FR-015).

**Rationale**:
- Es una regla de negocio con riesgo de carrera (dos masters degradándose en paralelo); `lockForUpdate`
  dentro de la transacción la serializa (Principio V).
- Reutiliza el patrón ya presente en el proyecto: lógica de negocio en un `Servicio*` + `DB::transaction`
  (p. ej. `ServicioCambioEstadoRecibo`, `ServicioGestionPagosRecibo`).

**Alternativas consideradas**:
- *Sólo validación en el Form Request*: no protege contra carreras ni contra la eliminación (que no siempre
  pasa por un Request con esa regla).
- *Trigger en PostgreSQL*: cumpliría la integridad pero saca la regla del dominio de la aplicación y
  complica los mensajes de error orientados al usuario; se descarta salvo necesidad futura.

---

## Decisión 5 — Historial de eventos de seguridad: log de aplicación con canal dedicado

**Decisión**: Registrar altas, cambios de perfil, restablecimientos de contraseña, desactivaciones y
reactivaciones mediante `Log::channel('seguridad')->info(...)` con contexto estructurado (`actor_id`,
`actor_email`, `usuario_afectado_id`, `accion`, `timestamp`). Definir el canal `seguridad` en
`config/logging.php` (archivo diario `storage/logs/seguridad-*.log`). No se crea tabla de auditoría.

**Rationale**:
- La spec pide "registrar en el historial de eventos de seguridad ... indicando quién y sobre qué cuenta"
  (FR-018, SC-006) pero no pide una pantalla de auditoría ni consultas sobre ese historial. Un canal de log
  dedicado cumple el requisito con mínimo alcance y es la práctica estándar del stack.
- Mantiene la migración de esquema acotada a `users`.

**Alternativas consideradas**:
- *Tabla `eventos_seguridad_usuarios`*: aporta consultabilidad pero nadie la consume en el alcance actual;
  se puede introducir después si aparece el requisito de una vista de auditoría. Se deja anotado como
  posible evolución en `data-model.md`.
- *Paquete de activity log (spatie/laravel-activitylog)*: dependencia nueva desproporcionada.

---

## Decisión 6 — Migración de datos de cuentas existentes

**Decisión**: Dos migraciones separadas. (a) Esquema: `perfil` string **default `'administrador'`** +
`activo` boolean default `true` + `CHECK`. (b) Datos: en la misma corrida, `UPDATE users SET perfil =
'master'` para **todas** las cuentas existentes al momento de la migración (son las cuentas fundadoras del
sistema, spec.md Assumptions + FR-016), garantizando ≥1 master. Documentar en el `README`/seeders que a
partir de aquí las cuentas nuevas nacen como `administrador` salvo que el master elija otro perfil.

**Rationale**:
- FR-016 exige que ninguna cuenta quede sin perfil y que al menos una sea `master`. Marcar todas las
  preexistentes como `master` es lo más seguro para no bloquear a quien hoy administra el sistema.
- `default 'administrador'` para filas futuras evita que un alta que omita el campo cree un master por
  accidente (mínimo privilegio); el formulario de alta siempre envía `perfil` explícito de todos modos.

**Alternativas consideradas**:
- *Default `master`*: descartado por mínimo privilegio.
- *Seeder en vez de migración de datos*: los seeders no corren en producción por defecto; la
  transformación de datos existentes debe ir en una migración.
- *Comando artisan interactivo para elegir el/los master*: sobre-ingeniería para un sistema con pocas
  cuentas fundadoras; si hiciera falta, el master resultante puede degradar a los demás desde la UI.

---

## Decisión 7 — Retiro del auto-registro público

**Decisión**: Eliminar las rutas `register` (GET y POST) de `routes/auth.php`, borrar
`app/Http/Controllers/Auth/RegisteredUserController.php` y `resources/views/auth/register.blade.php`, y
quitar cualquier enlace "Regístrate" del layout `guest-bootstrap`. La creación de cuentas pasa a ser
exclusivamente `usuarios.store` (protegida por `perfil.master`).

**Rationale**:
- FR-017: la única vía de alta es un master dentro de la sección de usuarios. Dejar `/register` vivo sería
  una puerta trasera que crea cuentas (hoy sin perfil controlado).
- Reduce superficie de ataque y código muerto.

**Alternativas consideradas**:
- *Dejar la ruta pero protegerla con `perfil.master`*: redundante con `usuarios.create`/`store` y
  confuso (dos formularios de alta). Se descarta.
- *Mantener `RegisteredUserController` para reutilizar su lógica*: la lógica de alta se reescribe en
  `ControladorUsuario`/`ServicioAdministracionUsuarios` con perfil y contraseña fijada por el master, así
  que no hay reutilización real.

---

## Decisión 8 — Contraseña inicial y restablecimiento

**Decisión**: En el alta, el master ingresa una contraseña inicial (campo `password` + `password_confirmation`),
validada con `Password::min(8)` (política mínima de 8, spec.md Assumptions / FR-008). El restablecimiento es
una acción aparte (`restablecerContrasena`) donde el master fija una contraseña nueva directamente; no se
envía correo. `email_verified_at` se setea a `now()` en el alta para no bloquear el primer login (FR: la
verificación de correo no es requisito, spec.md Assumptions).

**Rationale**: coincide con los supuestos ya acordados en la spec y con la infraestructura existente
(`Rules\Password`, cast `hashed`). Un flujo de invitación por correo queda fuera del alcance de v1.

**Alternativas consideradas**:
- *Generar contraseña aleatoria y mostrarla una vez*: mejor higiene, pero añade UI y manejo de "cópiala
  ahora"; se puede evolucionar luego. v1 usa contraseña elegida por el master.
- *Enviar enlace de establecer contraseña*: descartado explícitamente en los Assumptions de la spec.

---

## Puntos sin incógnitas restantes

Technical Context no contiene marcadores `NEEDS CLARIFICATION`. Todas las decisiones anteriores usan el
stack ya presente (PHP 8.3 / Laravel 13 / PostgreSQL / Pest 4 / Bootstrap 5 + htmx) sin dependencias
nuevas.
