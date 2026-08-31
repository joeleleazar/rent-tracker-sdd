# Contract — Sección de Gestión de Usuarios (`usuarios.*`)

Interfaz web (rutas Laravel + vistas Blade) que expone esta funcionalidad. Todas las rutas viven bajo el
grupo `Route::middleware(['auth', 'perfil.master'])->prefix('usuarios')` en `routes/web.php`.

**Regla de autorización transversal** (aplica a TODAS las rutas de esta tabla):
- Invitado no autenticado → redirección a `login` (middleware `auth`).
- Autenticado con perfil `administrador` → `403 Forbidden` con mensaje "No tienes permiso para acceder a la
  gestión de usuarios." (middleware `perfil.master`). No se filtra ninguna información del recurso.
- Autenticado con perfil `master` y cuenta activa → acceso concedido.
- Autenticado pero con cuenta `activo = false` → cierre de sesión + redirección a `login` con aviso
  (middleware `cuenta.activa`, aplicado al grupo web autenticado).

---

## Rutas

| Método | URI | Nombre | Acción | Éxito | Errores |
|---|---|---|---|---|---|
| GET | `/usuarios` | `usuarios.index` | `ControladorUsuario@index` | `200` + vista `usuarios.index` con el listado | `403` / redirect login |
| GET | `/usuarios/crear` | `usuarios.create` | `ControladorUsuario@create` | `200` + vista `usuarios.create` | `403` |
| POST | `/usuarios` | `usuarios.store` | `ControladorUsuario@store` | `302` → `usuarios.index` + flash éxito; usuario creado (`activo=true`, `email_verified_at=now()`) | `422` validación; `403` |
| GET | `/usuarios/{usuario}/editar` | `usuarios.edit` | `ControladorUsuario@edit` | `200` + vista `usuarios.edit` | `403`; `404` si no existe |
| PUT | `/usuarios/{usuario}` | `usuarios.update` | `ControladorUsuario@update` | `302` → `usuarios.edit`/`index` + flash éxito; `name`/`email` actualizados | `422`; `403`; `404` |
| PUT | `/usuarios/{usuario}/contrasena` | `usuarios.contrasena.update` | `ControladorUsuario@restablecerContrasena` | `302` + flash "Contraseña restablecida." | `422`; `403`; `404` |
| PUT | `/usuarios/{usuario}/perfil` | `usuarios.perfil.update` | `ControladorUsuario@cambiarPerfil` | `302` + flash "Perfil actualizado."; `perfil` cambiado | `422` (incl. INV-1/INV-3); `403`; `404` |
| PUT | `/usuarios/{usuario}/estado` | `usuarios.estado.update` | `ControladorUsuario@cambiarEstado` | `302` + flash "Usuario desactivado." / "Usuario reactivado."; `activo` cambiado | `422` (incl. INV-1/INV-2); `403`; `404` |
| DELETE | `/usuarios/{usuario}` | `usuarios.destroy` | `ControladorUsuario@destroy` | `302` → `usuarios.index` + flash éxito; registro eliminado | `422` (INV-1); `403`; `404` |

`{usuario}` = binding implícito por `id` sobre `App\Models\User`.

> **Nota sobre `usuarios.destroy`**: el mecanismo por defecto para retirar el acceso es **desactivar**
> (`usuarios.estado.update`). La eliminación definitiva se expone sólo si el equipo la considera necesaria
> durante `/speckit-tasks`; si se incluye, hereda literalmente la salvaguarda INV-1 y su propio `Modal` de
> confirmación. Puede omitirse de v1 sin afectar ningún FR (FR-013 cubre el retiro de acceso vía
> desactivación).

---

## Entradas por acción

### `store` — `SolicitudGuardarUsuario` (modo alta)

| Campo | Regla |
|---|---|
| `name` | `required string max:255` |
| `email` | `required string trim lowercase email max:255 unique:users,email` |
| `perfil` | `required` ∈ `{master, administrador}` |
| `password` | `required confirmed Password::min(8)` |
| `password_confirmation` | (implícito por `confirmed`) |

### `update` — `SolicitudGuardarUsuario` (modo edición)

| Campo | Regla |
|---|---|
| `name` | `required string max:255` |
| `email` | `required string trim lowercase email max:255 unique:users,email` **ignorando** `{usuario}->id` |

No acepta `perfil` ni `password` (esas van por sus rutas dedicadas).

### `restablecerContrasena` — `SolicitudRestablecerContrasenaUsuario`

| Campo | Regla |
|---|---|
| `password` | `required confirmed Password::min(8)` |

### `cambiarPerfil` — `SolicitudCambiarPerfilUsuario`

| Campo | Regla |
|---|---|
| `perfil` | `required` ∈ `{master, administrador}`; además el servicio valida INV-1 (no dejar 0 masters activos) e INV-3 (no auto-degradación del único master) → `422` con mensaje en español si falla |

### `cambiarEstado`

| Campo | Regla |
|---|---|
| `activo` | `required boolean`; el servicio valida INV-1 (al desactivar un master) e INV-2 (no auto-desactivación) → `422` si falla |

---

## Salidas / vistas

- **`usuarios.index`**: `table-responsive` + `table-hover` con columnas Nombre, Correo, Perfil (`badge`
  semántico), Estado (`badge` `bg-success` "Activa" / `bg-secondary` "Inactiva"), Acciones (editar,
  restablecer contraseña, cambiar perfil, desactivar/reactivar, [eliminar]). Botón "Agregar usuario"
  (`bi-person-plus`) arriba. Cada acción de alto impacto abre un `Modal` de confirmación nativo de Bootstrap
  con botones diferenciados ("Sí, desactivar usuario" / "No, cancelar"). Mensajes flash con `role="alert"`.
- **`usuarios.create`** / **`usuarios.edit`**: `card` con formulario; `input-group` donde aplique; errores
  de validación por campo; `hx-boost` para envío asíncrono con degradación a POST clásico.
- **Navegación** (`components/layouts/app-bootstrap.blade.php`): nuevo `<li>` "Usuarios" (`bi-people`)
  renderizado **sólo** dentro de `@can('gestionar-usuarios')` / `@if(auth()->user()?->esMaster())`.

---

## Contrato de lo que se RETIRA

| Antes | Después |
|---|---|
| `GET /register` (`register`) → vista `auth.register` | Ruta eliminada → `404` |
| `POST /register` (`register.store` implícito) → crea `User` y auto-login | Ruta eliminada → `404`; ninguna cuenta se crea |
| Enlace "Regístrate" en `guest-bootstrap` (si existe) | Eliminado |
| `App\Http\Controllers\Auth\RegisteredUserController` | Eliminado |
| `resources/views/auth/register.blade.php` | Eliminado |

Test asociado: `RegistroPublicoDeshabilitadoTest` — `get('/register')` y `post('/register', [...])` →
`404`; `assertDatabaseCount('users', $n)` sin cambios.

---

## Matriz de autorización (para tests Feature)

| Ruta | Invitado | `administrador` activo | `master` activo | `master` inactivo |
|---|---|---|---|---|
| cualquiera de `usuarios.*` | redirect `login` | `403` | `200`/`302` según acción | redirect `login` (cerrado por `cuenta.activa`) |

| Escenario de invariante | Resultado esperado |
|---|---|
| Desactivar al único `master` activo | `422` + mensaje; sin cambios en BD |
| Cambiar perfil del único `master` activo a `administrador` | `422` + mensaje; sin cambios |
| `master` se desactiva a sí mismo | `422` + mensaje |
| Eliminar al único `master` activo | `422` + mensaje |
| Desactivar un `master` habiendo otro `master` activo | `302` + éxito |
| Alta con `email` que ya existe con otra capitalización (`Foo@Bar.com` vs `foo@bar.com`) | `422` en `email` |
| Alta con `password` de 7 caracteres | `422` en `password` |
