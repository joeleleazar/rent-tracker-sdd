# Implementation Plan: Gestión de Usuarios por Perfiles

**Branch**: `040-gestion-usuarios-perfiles` | **Date**: 2026-08-27 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/040-gestion-usuarios-perfiles/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Introducir un campo de **perfil** (`master` / `administrador`) y un campo de **estado activo/inactivo** en
la cuenta de usuario, y construir una sección de gestión de usuarios (CRUD completo: listar, crear, editar
datos, restablecer contraseña, cambiar perfil, desactivar/reactivar) accesible **exclusivamente** para el
perfil `master`. El perfil `administrador` conserva el acceso operativo completo actual al resto del sistema
pero no ve ni puede invocar ninguna función de esa sección. Se retira el auto-registro público (`/register`)
como vía de alta de cuentas, se migran las cuentas existentes a `master`, se impide que el sistema quede sin
un `master` activo, y se registran en el log de eventos de seguridad las acciones sobre cuentas. La
implementación reutiliza el stack y los patrones ya establecidos: migración Eloquent estándar, enum PHP
respaldado por string para el perfil, un middleware de ruta para el gate `master`, Form Requests en español,
vistas Blade con Bootstrap 5 sobre el layout `app-bootstrap`, y cobertura Pest Feature/Unit.

## Technical Context

**Language/Version**: PHP 8.3, Laravel 13.x, Blade, PostgreSQL 15+ — mismo stack ya usado en todo el
proyecto, sin dependencias nuevas.

**Primary Dependencies**: Ninguna nueva. Se apoya en la autenticación ya provista por Laravel Breeze
(sesiones), `Illuminate\Support\Facades\Hash`, `Illuminate\Auth\Middleware\Authenticate` (`auth`) y el
sistema de logging estándar de Laravel (`Log` / canal dedicado) para el historial de eventos de seguridad.

**Storage**: PostgreSQL. Dos columnas nuevas en `users`: `perfil` (string no nulo, valores `master` /
`administrador`, respaldado por el enum `App\Enums\PerfilUsuario`, con `CHECK` o default a nivel de
migración) y `activo` (boolean no nulo, default `true`). No se crean tablas nuevas: los dos valores de
perfil son un conjunto cerrado que no se administra desde la interfaz (spec.md Key Entities). El historial
de eventos de seguridad se emite al log de aplicación (canal `stack`/dedicado), no a una tabla, salvo que
la fase de investigación concluya lo contrario.

**Testing**: Pest 4 — Feature tests para el `ControladorUsuario` (autorización por perfil en cada acción,
happy path de alta/edición/restablecimiento/cambio de perfil/desactivación, validación de entradas,
unicidad de correo case-insensitive, bloqueo del último master, bloqueo de auto-desactivación /
auto-degradación), Feature test de que un `administrador` recibe 403 en todas las rutas de la sección y no
ve el enlace de navegación, Feature test de que `/register` ya no da de alta cuentas, y Unit tests para el
modelo `User` (casts del enum de perfil, scope `activos()`, helpers `esMaster()` / `estaActivo()`) y para
el servicio que resguarda la invariante del último master.

**Target Platform**: Aplicación web Laravel servida por Herd (sin cambios).

**Project Type**: Aplicación web monolítica existente — columnas nuevas + una sección CRUD nueva sobre el
layout y la navegación ya existentes. Sin cambios de estructura de proyecto.

**Performance Goals**: N/A — el sistema tiene del orden de decenas de usuarios (spec.md Assumptions); el
listado no requiere paginación, búsqueda ni índices adicionales más allá del `unique` de correo ya
existente.

**Constraints**:
- La invariante "siempre existe al menos un `master` activo" debe resguardarse en el servidor y probarse
  para desactivación, eliminación y cambio de perfil (FR-014/FR-015), dentro de una `DB::transaction` con
  bloqueo de fila cuando aplique.
- La comprobación de perfil debe ocurrir en el servidor en **todas** las rutas de la sección (no basta con
  ocultar el enlace); un `administrador` que conoce la URL debe recibir 403 (FR-003/FR-005, SC-002).
- El cambio de estado a inactivo debe cortar el acceso en la petición inmediatamente siguiente del usuario
  afectado, aunque tenga sesión abierta (FR-013, SC-004) — vía middleware que valida `activo` en cada
  petición autenticada.
- Toda vista nueva o modificada (incluido el enlace del sidebar en `app-bootstrap.blade.php`) pasa por la
  revisión con el skill `impeccable` antes de darse por completa (Constitución, Principio VI).

**Scale/Scope**: 2 columnas nuevas en `users`; 1 enum nuevo (`PerfilUsuario`); 1 middleware nuevo de gate
`master` + 1 middleware nuevo de "cuenta activa"; 1 controlador nuevo (`ControladorUsuario`) con ~7
acciones; 2-3 Form Requests nuevos; 1 servicio nuevo para la invariante del último master; ~4 vistas Blade
nuevas (index, create, edit, y modal/vista de confirmación) + edición del sidebar; 1 migración de esquema +
1 migración/comando de datos para poblar `perfil`/`activo` en cuentas existentes; retiro de las rutas
`register` (GET/POST); ~6 archivos de test nuevos.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Stack Tecnológico Moderno (PHP, Laravel y PostgreSQL)**: Cumple — columnas nuevas por migración
  Eloquent estándar con restricción a nivel de BD para el conjunto cerrado de perfiles; enum PHP respaldado
  por string casteado por Eloquent (idiomático en Laravel moderno); sin SQL directo. Las operaciones
  multi-paso (cambio de perfil / estado con verificación del último master) van en `DB::transaction`.
- **II. Nomenclatura y Código Estrictamente en Español**: Cumple — `perfil`, `activo` como columnas;
  `App\Enums\PerfilUsuario` con casos `Master` / `Administrador`; `ControladorUsuario`,
  `SolicitudGuardarUsuario`, `SolicitudRestablecerContrasenaUsuario`, `ServicioInvarianteMaster` (o nombre
  equivalente), rutas `usuarios.*`, vistas en `resources/views/usuarios/`, mensajes de log y comentarios
  PHPDoc en español. **Nota**: el modelo permanece como `App\Models\User` y la tabla como `users` porque
  son artefactos del andamiaje de autenticación de Laravel/Breeze ya existentes en el proyecto; renombrarlos
  queda fuera del alcance de esta funcionalidad (mismo criterio con el que ya conviven `users`,
  `password_reset_tokens` y `sessions` en el proyecto). Todo el código **nuevo** se nombra en español.
- **III. Diseño Moderno e Intuitivo**: Aplica — listado como `table-responsive` + `table-hover`; `badge`
  con color semántico para perfil y para estado activo/inactivo; formularios con `form-control` estándar y
  validación nativa + de servidor; toda acción de alto impacto (desactivar, eliminar, cambiar perfil) con
  `Modal` de confirmación nativo de Bootstrap y botones diferenciados por color y texto ("Sí, desactivar
  usuario" / "No, cancelar") (FR-019).
- **IV. Pruebas Automatizadas Exhaustivas (Modelos y Controladores)**: Aplica — cobertura Feature de
  autorización (403 para `administrador` y para invitados en cada ruta), happy paths, validación de
  `FormRequest`, códigos HTTP (200/302/403/422), persistencia; cobertura Unit del modelo `User` (casts,
  scopes, helpers) y del servicio de invariante del último master. Ninguna funcionalidad se integra con
  tests en rojo.
- **V. Integridad de Datos y Seguridad Transaccional**: Aplica — cambio de perfil y de estado dentro de
  `DB::transaction`; verificación del "último master activo" con `lockForUpdate` sobre las filas `master`
  activas para evitar carreras; contraseñas siempre vía `Hash::make` (cast `hashed` ya presente), nunca en
  texto claro ni en pantalla; validación estricta en servidor y CSRF en todos los formularios.
- **VI. Sistema de Componentes Visuales (Bootstrap 5)**: Cumple — vistas nuevas sobre el layout
  `components/layouts/app-bootstrap.blade.php` con `card` / `table-responsive` / `badge` / `Modal` /
  `input-group`; iconografía `bi-*` consistente (`bi-people` para la sección, `bi-person-plus` para alta,
  `bi-pencil-square` para editar, `bi-key` para restablecer contraseña, `bi-slash-circle` para desactivar,
  `bi-trash` para eliminar); interactividad de escritura con `hx-boost` (htmx) y degradación a envío
  clásico; atributos de accesibilidad (`aria-label`, `aria-hidden` en íconos, `role="alert"` en mensajes).
  El enlace del sidebar se muestra sólo a `master` con un condicional Blade. Revisión con el skill
  `impeccable` (`/impeccable polish`/`audit`) obligatoria antes de cerrar la tarea, y actualización de
  `DESIGN.md` según corresponda.

**Resultado del gate**: PASA — sin excepciones nuevas. La única salvedad es documental (el modelo/tabla de
autenticación conservan su nombre en inglés heredado de Breeze, ya presente en el proyecto), no un
incumplimiento nuevo introducido por esta funcionalidad.

**Re-evaluación post-diseño (Phase 1)**: PASA sin cambios. Los artefactos de diseño (`data-model.md`,
`contracts/gestion-usuarios.md`, `quickstart.md`) no introdujeron entidades, dependencias ni patrones
adicionales respecto a lo evaluado arriba: 2 columnas en `users`, 1 enum, 2 middleware, 1 controlador, 1
servicio, 3 Form Requests, ~4 vistas y el retiro de `register`. Sin entradas en Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/040-gestion-usuarios-perfiles/
├── plan.md                              # This file (/speckit-plan command output)
├── research.md                          # Phase 0 output — decisiones de perfil, gate, invariante master, auditoría, migración de datos, registro
├── data-model.md                        # Phase 1 output — columnas nuevas de User, enum PerfilUsuario, invariantes y transiciones de estado
├── contracts/
│   └── gestion-usuarios.md              # Phase 1 output — rutas, autorización, entradas/salidas y validaciones de la sección de usuarios
├── quickstart.md                        # Phase 1 output — checklist de validación manual/automatizada contra spec.md
└── checklists/
    └── requirements.md                  # Checklist de calidad de la especificación (/speckit-specify)
```

### Source Code (repository root)

Aplicación Laravel monolítica ya existente — sin cambios de estructura. Archivos nuevos o tocados:

```text
app/Enums/
└── PerfilUsuario.php                                     # nuevo — enum backed string: Master, Administrador

database/migrations/
├── ..._agregar_perfil_y_activo_a_users_table.php         # nuevo — columnas perfil (default administrador o master, ver research) y activo (default true) + CHECK de perfil
└── ..._poblar_perfil_master_en_users_existentes.php      # nuevo — data migration: cuentas preexistentes -> perfil master, activo true (FR-016)

app/Models/User.php                                       # + cast 'perfil' => PerfilUsuario, + 'activo' => bool; + $fillable perfil/activo; + scope activos(); + esMaster(); + estaActivo()

app/Http/Middleware/
├── RequerirPerfilMaster.php                              # nuevo — 403 si el usuario autenticado no es master (alias 'perfil.master')
└── AsegurarCuentaActiva.php                              # nuevo — cierra sesión / 403 si la cuenta autenticada está inactiva (alias 'cuenta.activa')

bootstrap/app.php                                         # + registro de los alias de middleware; 'cuenta.activa' añadido al grupo web autenticado

app/Http/Controllers/
└── ControladorUsuario.php                                # nuevo — index, create, store, edit, update, restablecerContrasena, cambiarPerfil, cambiarEstado, destroy

app/Http/Requests/
├── SolicitudGuardarUsuario.php                           # nuevo — alta y edición de datos (name, email lowercase+unique CI, perfil, password en alta)
├── SolicitudRestablecerContrasenaUsuario.php             # nuevo — password confirmada, política mínima
└── SolicitudCambiarPerfilUsuario.php                     # nuevo — perfil destino válido + regla de invariante del último master

app/Services/
└── ServicioAdministracionUsuarios.php                    # nuevo — crea/edita/cambia perfil/cambia estado en transacción, resguarda invariante "≥1 master activo" y "no auto-desactivación/auto-degradación", emite eventos de seguridad al log

app/Http/Controllers/Auth/RegisteredUserController.php    # eliminado (o reducido) junto con las rutas register
routes/auth.php                                           # - rutas GET/POST 'register'
routes/web.php                                            # + grupo Route::middleware(['auth','perfil.master'])->prefix('usuarios') con las rutas usuarios.*
resources/views/auth/register.blade.php                   # eliminado
resources/views/components/layouts/guest-bootstrap.blade.php  # - enlace "Regístrate" si existe

resources/views/usuarios/
├── index.blade.php                                       # nuevo — tabla de usuarios + acciones + modales de confirmación
├── create.blade.php                                      # nuevo — formulario de alta
├── edit.blade.php                                        # nuevo — edición de datos + restablecer contraseña + cambiar perfil + desactivar/reactivar
└── partials/…                                            # (opcional) fila de usuario / modales reutilizables

resources/views/components/layouts/app-bootstrap.blade.php    # + <li> de navegación "Usuarios" visible sólo si auth()->user()->esMaster()

database/factories/UserFactory.php                        # + estados master()/administrador()/inactivo() para los tests

tests/Feature/
├── ControladorUsuarioTest.php                            # nuevo — autorización, CRUD, validación, invariantes
├── AccesoSeccionUsuariosPorPerfilTest.php                # nuevo — administrador -> 403 en todas las rutas; enlace de nav oculto
└── RegistroPublicoDeshabilitadoTest.php                  # nuevo — GET/POST /register ya no existen / no crean cuenta

tests/Unit/
├── UserTest.php                                          # nuevo — casts de perfil, scope activos(), esMaster()/estaActivo()
└── ServicioAdministracionUsuariosTest.php                # nuevo — invariante del último master, auto-desactivación/auto-degradación, emisión de eventos
```

**Structure Decision**: Se mantiene la estructura Laravel estándar del proyecto (controladores en
`app/Http/Controllers`, lógica de negocio en `app/Services`, Form Requests en español en
`app/Http/Requests`, vistas por recurso en `resources/views/usuarios/`, middleware en
`app/Http/Middleware`, enum en `app/Enums`). Las rutas de la sección se agrupan bajo el prefijo `usuarios`
con la pila de middleware `['auth', 'perfil.master']`; el middleware `cuenta.activa` se aplica a todo el
grupo web autenticado para cortar el acceso de cuentas desactivadas en su siguiente petición.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

Sin violaciones. El enum `PerfilUsuario` y los dos middleware nuevos son patrones idiomáticos de Laravel y
no constituyen complejidad injustificada; el `ServicioAdministracionUsuarios` sigue el mismo criterio de
"lógica de negocio desacoplada en un servicio + transacción" ya usado en el resto del proyecto (p. ej.
`ServicioCambioEstadoRecibo`, `ServicioGestionPagosRecibo`).
