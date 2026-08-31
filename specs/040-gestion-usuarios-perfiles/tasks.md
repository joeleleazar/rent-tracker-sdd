---

description: "Task list for 040-gestion-usuarios-perfiles"
---

# Tasks: Gestión de Usuarios por Perfiles

**Input**: Design documents from `/specs/040-gestion-usuarios-perfiles/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md

**Tests**: incluidas — Principio IV de la constitución las exige para modelos y controladores.

**Organization**: 3 historias de usuario (US1 alta por master — P1; US2 el administrador sin acceso a la
sección — P1; US3 mantenimiento de cuentas — P2), más una fase Foundational que crea el esquema, el enum,
los middleware y el esqueleto de rutas/controlador que las tres comparten.

**Nota de entorno**: usar el binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`) para
`artisan` / `pest`. Correr los tests con `php artisan test`.

**Decisión pendiente asumida** (ver contracts/gestion-usuarios.md): el mecanismo por defecto para retirar
el acceso es **desactivar**; la eliminación definitiva (`usuarios.destroy`) se incluye como tarea
explícitamente **opcional** (T035/T036) y puede omitirse de v1 sin incumplir ningún FR.

## Phase 1: Setup

- [X] T001 Confirmar la línea base: correr `php artisan test` completo (binario Herd) y verificar que todo está en verde antes de tocar ningún archivo.
- [X] T002 [P] Crear el enum `app/Enums/PerfilUsuario.php`: enum respaldado por string con casos `Master = 'master'` y `Administrador = 'administrador'`, más `etiqueta(): string` ("Master" / "Administrador") y `colorBadge(): string` (clase `bg-*` semántica) (data-model.md §Enum; research.md Decisión 1).

---

## Phase 2: Foundational (Blocking Prerequisites)

**Propósito**: esquema, modelo, autorización y esqueleto de la sección que las tres historias necesitan.

**⚠️ CRITICAL**: ninguna historia puede empezar hasta terminar esta fase.

- [X] T003 Migración de esquema `..._agregar_perfil_y_activo_a_users_table.php`: columna `perfil` (string, NOT NULL, default `'administrador'`, `CHECK (perfil in ('master','administrador'))`) y `activo` (boolean, NOT NULL, default `true`) en `users`; `down()` revierte ambas (data-model.md §users; research.md Decisión 6).
- [X] T004 Migración de datos `..._poblar_perfil_master_en_users_existentes.php`: `UPDATE users SET perfil = 'master', activo = true` para todas las filas existentes al correr la migración (FR-016; research.md Decisión 6). `down()` no-op documentado.
- [X] T005 Extender `app/Models/User.php`: añadir `perfil` y `activo` a `$fillable`; en `casts()` añadir `'perfil' => \App\Enums\PerfilUsuario::class` y `'activo' => 'boolean'`; añadir `scopeActivos($query)`, `esMaster(): bool` y `estaActivo(): bool` (data-model.md §Modelo User).
- [X] T006 [P] Añadir estados a `database/factories/UserFactory.php`: `master()`, `administrador()` e `inactivo()` (`activo => false`) para los tests (plan.md §Source Code).
- [X] T007 [P] Crear `app/Http/Middleware/RequerirPerfilMaster.php`: si `auth()->user()` no es `master`, `abort(403, 'No tienes permiso para acceder a la gestión de usuarios.')`; registrar el alias `perfil.master` en `bootstrap/app.php` (research.md Decisión 2; contracts/gestion-usuarios.md §Regla de autorización).
- [X] T008 Crear `app/Http/Middleware/AsegurarCuentaActiva.php`: en cada petición autenticada, si `auth()->user()->activo === false` → `Auth::logout()`, invalidar y regenerar sesión, redirigir a `login` con `status`/`error` "Tu cuenta fue desactivada. Contacta a un administrador."; registrar el alias `cuenta.activa` en `bootstrap/app.php` y añadirlo al grupo de rutas web autenticadas (junto a `auth`) (research.md Decisión 3; FR-013).
- [X] T009 [P] Definir el Gate `gestionar-usuarios` en `app/Providers/AppServiceProvider.php`: `Gate::define('gestionar-usuarios', fn (\App\Models\User $u) => $u->esMaster())`, para usar `@can('gestionar-usuarios')` en las vistas (research.md Decisión 2).
- [X] T010 [P] Añadir el canal de log `seguridad` en `config/logging.php`: driver `daily`, ruta `storage/logs/seguridad.log`, para el historial de eventos de seguridad (research.md Decisión 5; FR-018).
- [X] T011 Crear el esqueleto `app/Http/Controllers/ControladorUsuario.php` con los métodos `index`, `create`, `store`, `edit`, `update`, `restablecerContrasena`, `cambiarPerfil`, `cambiarEstado` (y `destroy` opcional) y registrar el grupo de rutas en `routes/web.php`: `Route::middleware(['auth','perfil.master'])->prefix('usuarios')->controller(ControladorUsuario::class)` con los nombres `usuarios.index|create|store|edit|update|contrasena.update|perfil.update|estado.update` según contracts/gestion-usuarios.md §Rutas. En esta fase los métodos pueden devolver un stub mínimo; las historias los completan.
- [X] T012 Crear el esqueleto `app/Services/ServicioAdministracionUsuarios.php` con las firmas `crear(array $datos, User $actor): User`, `editarDatos(User $usuario, array $datos, User $actor): void`, `restablecerContrasena(User $usuario, string $contrasena, User $actor): void`, `cambiarPerfil(User $usuario, PerfilUsuario $perfil, User $actor): void`, `cambiarEstado(User $usuario, bool $activo, User $actor): void` (y `eliminar()` opcional); implementación en las historias (plan.md §Source Code; data-model.md §Invariantes).

**Checkpoint**: esquema migrado, `User` con perfil/estado, middleware y gate activos, rutas `usuarios.*`
protegidas por `perfil.master`. US2 ya es testeable para autorización.

---

## Phase 3: User Story 1 - El Master da de alta a un nuevo usuario (Priority: P1) 🎯 MVP

**Goal**: un `master` ve la sección "Usuarios", lista las cuentas y crea una cuenta nueva (nombre, correo,
perfil, contraseña inicial) que queda operativa de inmediato.

**Independent Test**: iniciar sesión como `master`, crear un usuario con perfil `Administrador`, cerrar
sesión e iniciar sesión con la cuenta nueva (quickstart.md Escenario 1).

### Tests for User Story 1 ⚠️

- [X] T013 [P] [US1] Feature test `tests/Feature/ControladorUsuarioTest.php` (grupo alta): un `master` ve `usuarios.index` con las cuentas existentes (200); `usuarios.store` con datos válidos crea el usuario (`activo=true`, `email_verified_at` no nulo, `perfil` persistido) y redirige con flash de éxito (302); rechaza correo ya existente con distinta capitalización/espacios (422 en `email`); rechaza campos obligatorios vacíos y correo mal formado (422); rechaza contraseña de 7 caracteres (422); la cuenta creada puede iniciar sesión (contracts/gestion-usuarios.md §store; FR-002/FR-006/FR-007/FR-008).

### Implementation for User Story 1

- [X] T014 [P] [US1] Crear `app/Http/Requests/SolicitudGuardarUsuario.php`: `authorize()` vía Gate `gestionar-usuarios`; reglas modo alta (`name`, `email` con `lowercase`+`trim`+`unique:users,email`, `perfil` `Enum(PerfilUsuario)`, `password` `confirmed` + `Password::min(8)`) y modo edición (sin `perfil`/`password`, `unique` ignorando el id de la ruta); mensajes en español (data-model.md §Reglas de validación; contracts §Entradas).
- [X] T015 [US1] Implementar `ServicioAdministracionUsuarios::crear()`: dentro de `DB::transaction`, normalizar el correo (minúsculas, `trim`), `Hash::make` de la contraseña, `email_verified_at = now()`, persistir con el `perfil` recibido, y emitir `Log::channel('seguridad')->info('usuario.creado', [...])` con `actor_id`/`actor_email`/`usuario_afectado_id` (research.md Decisión 5/8; data-model.md §Evento de seguridad).
- [X] T016 [US1] Implementar `ControladorUsuario@index` (lista `User::orderBy('name')` con perfil y estado), `@create` (devuelve la vista del formulario) y `@store` (valida con `SolicitudGuardarUsuario`, delega en `ServicioAdministracionUsuarios::crear()`, redirige a `usuarios.index` con flash) (contracts/gestion-usuarios.md §Rutas).
- [X] T017 [P] [US1] Crear la vista `resources/views/usuarios/index.blade.php` sobre `components/layouts/app-bootstrap`: `table-responsive` + `table-hover` con Nombre, Correo, Perfil (`badge` semántico vía `PerfilUsuario::colorBadge()`), Estado (`badge` `bg-success` "Activa" / `bg-secondary` "Inactiva") y columna Acciones; botón "Agregar usuario" (`bi-person-plus`) arriba; mensajes flash con `role="alert"` (contracts §Salidas/vistas; Principio III/VI).
- [X] T018 [P] [US1] Crear la vista `resources/views/usuarios/create.blade.php`: `card` con formulario (`name`, `email`, selector `perfil`, `password` + `password_confirmation`), errores de validación por campo, `hx-boost` con degradación a POST clásico, atributos de accesibilidad (Principio VI).
- [X] T019 [US1] Añadir el `<li>` de navegación "Usuarios" (`bi-people`) en `resources/views/components/layouts/app-bootstrap.blade.php`, dentro de `@can('gestionar-usuarios')`, con el patrón `active` de `request()->routeIs('usuarios.*')` (contracts §Navegación; FR-021).
- [X] T020 [US1] Ejecutar los Escenarios 1 y 2 de `quickstart.md` (alta cronometrada < 2 min, login de la cuenta nueva, rechazo de correo duplicado) y corregir cualquier hallazgo.

**Checkpoint**: un `master` puede listar y crear usuarios; el enlace "Usuarios" aparece sólo para `master`.

---

## Phase 4: User Story 2 - El Administrador opera el sistema sin acceso a la gestión de usuarios (Priority: P1)

**Goal**: el perfil `administrador` conserva todo el acceso operativo actual pero no ve ni puede invocar
ninguna función de la sección de usuarios; además se retira el auto-registro público como vía de alta.

**Independent Test**: iniciar sesión como `administrador`, comprobar que el sidebar no muestra "Usuarios",
que `/usuarios*` responde 403, y que las secciones del negocio siguen funcionando (quickstart.md Escenario
3); y que `/register` ya no existe (Escenario 7).

### Tests for User Story 2 ⚠️

- [X] T021 [P] [US2] Feature test `tests/Feature/AccesoSeccionUsuariosPorPerfilTest.php`: para cada ruta de `usuarios.*` (index, create, store, edit, update, contrasena.update, perfil.update, estado.update) un `administrador` autenticado recibe 403 y un invitado es redirigido a `login`; un `master` con `activo=false` es expulsado a `login` (middleware `cuenta.activa`); la vista renderizada para un `administrador` no contiene el enlace "Usuarios"; un `administrador` sí accede con 200 a rutas operativas de muestra (`locaciones.index`, `recibos.registroMasivo.index`) (contracts/gestion-usuarios.md §Matriz de autorización; FR-003/FR-005/FR-021, SC-002/SC-007).
- [X] T022 [P] [US2] Feature test `tests/Feature/RegistroPublicoDeshabilitadoTest.php`: `GET /register` y `POST /register` con payload válido devuelven 404 y `assertDatabaseCount('users', ...)` no cambia (FR-017).

### Implementation for User Story 2

- [X] T023 [US2] Retirar el auto-registro en `routes/auth.php`: eliminar las rutas `GET`/`POST` `register` y su `use` de `RegisteredUserController` (research.md Decisión 7; FR-017).
- [X] T024 [P] [US2] Eliminar `app/Http/Controllers/Auth/RegisteredUserController.php` y `resources/views/auth/register.blade.php`.
- [X] T025 [P] [US2] Quitar cualquier enlace/CTA "Regístrate" de `resources/views/components/layouts/guest-bootstrap.blade.php` y de la vista de login (`resources/views/auth/login.blade.php`) si lo tuvieran.
- [X] T026 [US2] Ejecutar los Escenarios 3 y 7 de `quickstart.md` (administrador sin enlace ni acceso a `/usuarios*`, negocio intacto, `/register` → 404) y corregir hallazgos.

**Checkpoint**: US1 y US2 funcionan de forma independiente; la única vía de alta es `usuarios.store`.

---

## Phase 5: User Story 3 - Mantenimiento de cuentas existentes (Priority: P2)

**Goal**: el `master` edita datos, restablece contraseñas, cambia perfiles y desactiva/reactiva cuentas,
con las salvaguardas del último master y de la auto-protección, y con confirmación en acciones de alto
impacto.

**Independent Test**: como `master`, editar un usuario, restablecer su contraseña y verificar login,
desactivarlo (ya no inicia sesión y su sesión abierta es expulsada), reactivarlo (quickstart.md Escenario
4); probar los bloqueos del último master (Escenario 5) y de auto-desactivación (Escenario 6).

### Tests for User Story 3 ⚠️

- [X] T027 [P] [US3] Unit test `tests/Unit/UserTest.php`: `perfil` se castea a `PerfilUsuario`; `scopeActivos()` filtra `activo=true`; `esMaster()` y `estaActivo()` devuelven lo esperado (data-model.md §Modelo User).
- [X] T028 [P] [US3] Unit test `tests/Unit/ServicioAdministracionUsuariosTest.php`: INV-1 (desactivar / eliminar / degradar al único `master` activo lanza excepción de validación y no persiste), INV-2 (un usuario no puede desactivarse a sí mismo), INV-3 (el único `master` no puede auto-degradarse; con dos masters sí); cada operación exitosa emite el evento correspondiente al canal `seguridad` con `actor` y `usuario_afectado` (data-model.md §Invariantes; FR-014/FR-015/FR-018, SC-005/SC-006).
- [X] T029 [P] [US3] Feature test `tests/Feature/ControladorUsuarioTest.php` (grupo mantenimiento): `usuarios.update` cambia `name`/`email` (302 + éxito); `usuarios.contrasena.update` aplica contraseña nueva (login con ella funciona); `usuarios.perfil.update` cambia el perfil; `usuarios.estado.update` desactiva y reactiva; una cuenta recién desactivada es expulsada a `login` en su siguiente petición; los intentos que violan INV-1/INV-2/INV-3 devuelven 422 con mensaje (contracts/gestion-usuarios.md §Matriz de autorización; FR-010–FR-015).

### Implementation for User Story 3

- [X] T030 [P] [US3] Crear `app/Http/Requests/SolicitudRestablecerContrasenaUsuario.php` (`password` `required confirmed` + `Password::min(8)`, `authorize()` vía Gate) y `app/Http/Requests/SolicitudCambiarPerfilUsuario.php` (`perfil` `required Enum(PerfilUsuario)`, `authorize()` vía Gate) (data-model.md §Reglas; contracts §Entradas).
- [X] T031 [US3] Implementar en `ServicioAdministracionUsuarios`: `editarDatos()` (normaliza correo, actualiza, emite `usuario.datos_actualizados`); `restablecerContrasena()` (`Hash::make`, emite `usuario.contrasena_restablecida`); `cambiarPerfil()` y `cambiarEstado()` dentro de `DB::transaction` con `User::where('perfil','master')->where('activo',true)->lockForUpdate()->count()` para resguardar INV-1, más INV-2/INV-3 (comparando `$usuario->is($actor)`); cada una emite su evento al canal `seguridad` (data-model.md §Invariantes; research.md Decisión 4/5).
- [X] T032 [US3] Implementar `ControladorUsuario@edit`, `@update`, `@restablecerContrasena`, `@cambiarPerfil` y `@cambiarEstado`: validan con sus Form Requests, delegan en el servicio, traducen las excepciones de invariante a `back()->withErrors([...])` (422) y el éxito a `redirect()` con flash (contracts/gestion-usuarios.md §Rutas).
- [X] T033 [P] [US3] Crear `resources/views/usuarios/edit.blade.php`: `card` con el formulario de datos (`name`/`email`), un bloque "Restablecer contraseña", un bloque "Cambiar perfil" y un bloque "Estado de la cuenta" (Desactivar / Reactivar); cada acción de alto impacto se confirma con un `Modal` nativo de Bootstrap con botones diferenciados por color y texto ("Sí, desactivar usuario" / "No, cancelar") (FR-019; Principio III/VI).
- [X] T034 [US3] Añadir a `resources/views/usuarios/index.blade.php` las acciones por fila (editar `bi-pencil-square`, restablecer contraseña `bi-key`, cambiar perfil, desactivar `bi-slash-circle` / reactivar `bi-arrow-counterclockwise`) y sus `Modal` de confirmación; ocultar la acción "desactivar" sobre la propia fila del `master` en sesión (refuerzo visual de INV-2).
- [X] T035 [US3] (OPCIONAL) Implementar `ServicioAdministracionUsuarios::eliminar()` (dentro de `DB::transaction`, resguarda INV-1, emite `usuario.eliminado`) y `ControladorUsuario@destroy` + ruta `DELETE /usuarios/{usuario}` (`usuarios.destroy`) (contracts/gestion-usuarios.md §Nota sobre `usuarios.destroy`).
- [X] T036 [US3] (OPCIONAL) Añadir la acción "Eliminar" (`bi-trash`) con su `Modal` de confirmación en `usuarios/index.blade.php` y su cobertura en `ControladorUsuarioTest` y `ServicioAdministracionUsuariosTest` (INV-1 al eliminar el último master).
- [X] T037 [US3] Ejecutar los Escenarios 4, 5, 6 y 8 de `quickstart.md` (mantenimiento completo, bloqueo del último master, auto-protección, entradas en `storage/logs/seguridad-*.log`) y corregir hallazgos.

**Checkpoint**: las tres historias funcionan de forma independiente.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T038 Correr `php artisan test` completo (binario Herd) y dejar toda la suite en verde.
- [X] T039 Revisión de interfaz con el skill `impeccable` (`/impeccable audit` o `/impeccable polish`) sobre `resources/views/usuarios/**` y el sidebar modificado `components/layouts/app-bootstrap.blade.php` (Constitución, Principio VI); aplicar los hallazgos.
- [X] T040 [P] Actualizar `DESIGN.md` con la sección de la vista de gestión de usuarios (según `/impeccable document`), y `README.md` con la nota de que el alta de cuentas es exclusiva del `master` y las cuentas nuevas nacen como `administrador`.
- [X] T041 [P] Auditoría de nomenclatura en español sobre los archivos nuevos (columnas, enum, controlador, requests, servicio, middleware, rutas, vistas, mensajes de log) (Constitución, Principio II; Flujo de Trabajo punto 4).
- [X] T042 Ejecutar la suite filtrada de `quickstart.md` (`php artisan test --filter="ControladorUsuario|AccesoSeccionUsuariosPorPerfil|RegistroPublicoDeshabilitado|UserTest|ServicioAdministracionUsuarios"`) como verificación final del mapeo FR/SC.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: depende de Setup. **Bloquea todas las historias.**
- **US1 (Phase 3)**: depende de Foundational.
- **US2 (Phase 4)**: depende de Foundational. Sus tests de nav (T021) esperan el enlace de T019 (US1); si US2 se hace antes que US1, ajustar ese aserto o ejecutarlo tras T019.
- **US3 (Phase 5)**: depende de Foundational. Independiente de US1/US2 salvo que reutiliza `usuarios/index.blade.php` (creada en T017).
- **Polish (Phase 6)**: depende de las historias que se decidan entregar.

### User Story Dependencies

- **US1 (P1)**: sólo Foundational.
- **US2 (P1)**: sólo Foundational. Integra con US1 a nivel de la vista (enlace de nav) pero es testeable de forma independiente para autorización y para el retiro de `/register`.
- **US3 (P2)**: sólo Foundational. Comparte `usuarios/index.blade.php` con US1.

### Within Each User Story

- Los tests se escriben primero y deben fallar antes de implementar.
- Migraciones/encabezado (Foundational) → Form Requests → Servicio → Controlador → Vistas → validación manual (quickstart).

### Parallel Opportunities

- Setup: T002 en paralelo con T001 no aplica (T001 es previo); T002 es [P] respecto al resto.
- Foundational: T006, T007, T009, T010 en paralelo entre sí; T003→T004→T005 son secuenciales (esquema→datos→modelo); T011 y T012 tras T005/T007.
- US1: T014, T017, T018 en paralelo; T013 (test) antes de T015/T016.
- US2: T021, T022 en paralelo; T024, T025 en paralelo tras T023.
- US3: T027, T028, T029, T030 en paralelo; T033 en paralelo con T031.
- Con equipo: tras Foundational, un desarrollador por historia (US1/US2/US3) en paralelo.

---

## Parallel Example: User Story 1

```bash
# Primero el test de la historia (debe fallar):
Task: "T013 Feature test tests/Feature/ControladorUsuarioTest.php (grupo alta)"

# Luego, en paralelo:
Task: "T014 SolicitudGuardarUsuario en app/Http/Requests/SolicitudGuardarUsuario.php"
Task: "T017 Vista resources/views/usuarios/index.blade.php"
Task: "T018 Vista resources/views/usuarios/create.blade.php"
```

---

## Implementation Strategy

### MVP First (US1 + US2)

1. Fase 1: Setup.
2. Fase 2: Foundational (crítica — bloquea todo).
3. Fase 3: US1 (alta por master) → validar el Escenario 1.
4. Fase 4: US2 (administrador sin acceso + retiro de `/register`) → validar los Escenarios 3 y 7.
5. **PARAR y VALIDAR**: en este punto la regla de negocio pedida ("el master agrega usuarios, el
   administrador no accede al CRUD") está entregada y probada. Desplegable como MVP.

### Incremental Delivery

1. Setup + Foundational → base lista.
2. US1 → alta y listado por master → demo.
3. US2 → cierre de las demás vías de alta → demo.
4. US3 → ciclo de vida completo de la cuenta (editar / contraseña / perfil / desactivar) → demo.

### Parallel Team Strategy

Tras Foundational: Dev A → US1, Dev B → US2 (coordinando el enlace de nav con T019), Dev C → US3.
