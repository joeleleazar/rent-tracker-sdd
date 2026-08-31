# Phase 1 — Data Model: Gestión de Usuarios por Perfiles

Fuente: entidades y reglas de `spec.md` (Key Entities, FR-001–FR-021) + decisiones de `research.md`.

---

## Entidad: Usuario (`users`) — columnas nuevas

Se extiende la tabla `users` existente (andamiaje de autenticación Breeze). No se renombra la tabla ni el
modelo `App\Models\User` (ver Constitution Check en `plan.md`).

| Columna | Tipo | Nulo | Default | Notas |
|---|---|---|---|---|
| `perfil` | `string` (varchar) | NO | `'administrador'` | Casteado a `App\Enums\PerfilUsuario`. Restricción `CHECK (perfil in ('master','administrador'))`. Filas preexistentes se fijan a `'master'` por la migración de datos (FR-016). |
| `activo` | `boolean` | NO | `true` | `false` = cuenta desactivada: no puede iniciar sesión ni acceder a rutas protegidas (FR-013). Conserva toda la información y el historial. |

Columnas existentes relevantes (sin cambios): `id`, `name`, `email` (`unique`), `email_verified_at`,
`password` (cast `hashed`), `remember_token`, `timestamps`.

### Índices

- `users.email` ya es `unique`. La unicidad case-insensitive se aplica normalizando el correo a minúsculas
  y sin espacios antes de persistir/validar (regla `lowercase` + `trim` en el Form Request), reutilizando
  el `unique` existente. No se añade índice funcional nuevo (volumen bajo, spec.md Assumptions).
- No se indexa `perfil` ni `activo` (decenas de filas).

---

## Enum: `App\Enums\PerfilUsuario`

Enum PHP respaldado por string.

| Caso | Valor | Significado |
|---|---|---|
| `Master` | `'master'` | Acceso operativo completo + acceso **exclusivo** a todo el CRUD de usuarios. |
| `Administrador` | `'administrador'` | Acceso operativo completo. **Sin** acceso a la sección de gestión de usuarios (ni listar). |

Helpers sugeridos: `etiqueta(): string` (texto para la UI, p. ej. "Master" / "Administrador"),
`colorBadge(): string` (clase `bg-*` semántica para el `badge` de la vista).

---

## Modelo `User` — añadidos

- `$fillable`: `+ 'perfil'`, `+ 'activo'` (o gestión explícita desde el servicio; nunca `perfil`/`activo`
  desde un formulario público — sólo desde `usuarios.*`).
- `casts()`: `+ 'perfil' => PerfilUsuario::class`, `+ 'activo' => 'boolean'`.
- Scope `activos($query)` → `where('activo', true)`.
- `esMaster(): bool` → `$this->perfil === PerfilUsuario::Master`.
- `estaActivo(): bool` → `$this->activo === true`.

---

## Reglas de validación (derivadas de FR)

| Campo | Reglas | FR |
|---|---|---|
| `name` | `required`, `string`, `max:255` | FR-002 |
| `email` | `required`, `string`, `lowercase`, `email`, `max:255`, `trim`, `unique:users,email` (ignorando el propio id en edición) | FR-002, FR-007 |
| `perfil` | `required`, `Enum(PerfilUsuario)` | FR-001, FR-002, FR-012 |
| `password` (alta) | `required`, `confirmed`, `Password::min(8)` | FR-002, FR-008 |
| `password` (restablecer) | `required`, `confirmed`, `Password::min(8)` | FR-008, FR-011 |
| `activo` (cambio de estado) | `boolean` | FR-013 |

Toda la validación es de servidor; la validación nativa del navegador (`required`, `type=email`,
`minlength`) es refuerzo, no sustituto (Principio VI).

---

## Invariantes (resguardadas en `ServicioAdministracionUsuarios`, en `DB::transaction` + `lockForUpdate`)

1. **INV-1 — Siempre ≥1 master activo**: ninguna operación (desactivar, eliminar, cambiar perfil
   `master`→`administrador`) puede dejar `count(users where perfil='master' and activo=true) == 0`. Si lo
   haría → `422` + mensaje "Debe existir al menos un usuario Master activo en el sistema." (FR-014).
2. **INV-2 — No auto-desactivación**: un usuario no puede desactivar su propia cuenta (FR-015).
3. **INV-3 — No auto-degradación del único master**: un usuario `master` no puede quitarse a sí mismo el
   perfil `master` si es el único `master` activo (FR-015). (Degradarse siendo uno de varios sí se permite,
   sujeto a INV-1.)
4. **INV-4 — Correo único case-insensitive**: no pueden coexistir dos cuentas cuyo `email` normalizado
   (minúsculas, sin espacios) coincida (FR-007).
5. **INV-5 — Perfil siempre válido**: `perfil ∈ {master, administrador}` (CHECK en BD + Enum).

---

## Transiciones de estado de la cuenta

```text
        alta (por master)
             │
             ▼
        ┌─────────┐   desactivar (master, si INV-1/INV-2 ok)   ┌──────────┐
        │ ACTIVA  │ ─────────────────────────────────────────▶ │ INACTIVA │
        │(activo= │ ◀───────────────────────────────────────── │(activo=  │
        │  true)  │                 reactivar (master)          │  false)  │
        └─────────┘                                             └──────────┘
             │                                                       │
             │ eliminar (master, si INV-1 ok)   eliminar (master, si INV-1 ok)
             ▼                                                       ▼
         (registro borrado — sólo si no rompe INV-1; ver research.md Decisión 6:
          el mecanismo por defecto es desactivar, la eliminación es opcional y
          queda sujeta a las mismas salvaguardas)
```

Cambio de `perfil`: transición ortogonal al estado activo/inactivo; `master ⇄ administrador` permitido por
un `master`, sujeto a INV-1 e INV-3. Efecto de permisos: en la siguiente petición del usuario afectado
(los permisos se leen del `perfil` en BD por request, no de la sesión).

---

## Evento de seguridad de usuarios (no persistido en tabla)

Emitido al canal de log `seguridad` (research.md Decisión 5). Forma del contexto:

| Campo | Ejemplo | Origen |
|---|---|---|
| `accion` | `usuario.creado` \| `usuario.perfil_cambiado` \| `usuario.contrasena_restablecida` \| `usuario.desactivado` \| `usuario.reactivado` \| `usuario.eliminado` | acción ejecutada |
| `actor_id` / `actor_email` | `1` / `admin@ejemplo.com` | `auth()->user()` (siempre un master) |
| `usuario_afectado_id` | `7` | cuenta objetivo |
| `detalle` | `{ "perfil_anterior": "administrador", "perfil_nuevo": "master" }` | opcional, según acción |
| `timestamp` | `2026-08-27T14:03:00-05:00` | `now()` |

**Evolución posible (fuera de alcance v1)**: si más adelante se pide una pantalla de auditoría consultable,
promover esto a una tabla `eventos_seguridad_usuarios` con las mismas columnas.
