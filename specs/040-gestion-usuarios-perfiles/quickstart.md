# Quickstart — Validación de "Gestión de Usuarios por Perfiles"

Guía para validar la funcionalidad de extremo a extremo. Los detalles de rutas, entradas y reglas están en
[`contracts/gestion-usuarios.md`](./contracts/gestion-usuarios.md) y [`data-model.md`](./data-model.md); no
se repiten aquí.

## Prerrequisitos

- Entorno local del proyecto funcionando (PHP 8.3, Laravel 13, PostgreSQL, Herd, `npm run dev` para assets).
- Base de datos migrable en local (no producción).

## Puesta a punto

```bash
# 1. Aplicar migraciones (esquema + datos de cuentas existentes)
php artisan migrate

# 2. Verificar que toda cuenta preexistente quedó como master y activa (FR-016)
php artisan tinker --execute="echo App\Models\User::where('perfil','master')->where('activo',true)->count();"
#   -> debe ser >= 1 y = al total de cuentas que existían antes de migrar

# 3. (Opcional) Crear un usuario administrador de prueba para los escenarios manuales
php artisan tinker --execute="App\Models\User::factory()->administrador()->create(['email'=>'admin.prueba@ejemplo.com']);"
```

## Suite automatizada (autoridad de la validación)

```bash
php artisan test --filter="ControladorUsuario|AccesoSeccionUsuariosPorPerfil|RegistroPublicoDeshabilitado|UserTest|ServicioAdministracionUsuarios"
```

Debe pasar en verde. Cobertura esperada (mapeo a `spec.md`):

| Test | Cubre |
|---|---|
| `AccesoSeccionUsuariosPorPerfilTest` | US2, FR-003/FR-005/FR-021, SC-002 — `administrador` e invitado bloqueados en todas las rutas `usuarios.*`; enlace de nav oculto para `administrador` |
| `ControladorUsuarioTest` | US1/US3, FR-002/FR-006/FR-007/FR-008/FR-010/FR-011/FR-012/FR-013/FR-019/FR-020 — happy paths, validación, códigos HTTP, unicidad de correo case-insensitive |
| `ServicioAdministracionUsuariosTest` | FR-014/FR-015, SC-005 — INV-1 (≥1 master activo), INV-2 (no auto-desactivación), INV-3 (no auto-degradación del único master); FR-018/SC-006 — emisión de eventos al canal `seguridad` |
| `UserTest` | FR-001/FR-013 — cast del enum `PerfilUsuario`, scope `activos()`, `esMaster()`, `estaActivo()` |
| `RegistroPublicoDeshabilitadoTest` | FR-017 — `GET`/`POST /register` → `404`, sin altas |

## Escenarios manuales (humo)

1. **Alta por master (US1, SC-001)**: iniciar sesión como `master` → sidebar muestra "Usuarios" → "Agregar
   usuario" → completar nombre, correo nuevo, perfil `Administrador`, contraseña (≥8) → guardar → aparece en
   el listado con `badge` de perfil y estado "Activa" → cerrar sesión → iniciar sesión con esa cuenta →
   entra al sistema. Cronometrar: < 2 min.
2. **Correo duplicado (US1 esc. 3, FR-007)**: repetir el alta con el mismo correo en distinta capitalización
   → error de validación en el campo correo, sin alta.
3. **Administrador sin acceso (US2, SC-002)**: iniciar sesión como `administrador` → el sidebar **no**
   muestra "Usuarios" → navegar manualmente a `/usuarios`, `/usuarios/crear`, `/usuarios/1/editar` → cada
   una responde `403` → todas las secciones del negocio (locaciones, contratos, recibos, lecturas, pagos,
   conceptos, configuración) siguen funcionando igual que antes (SC-007).
4. **Mantenimiento (US3)**: como `master`, sobre un usuario de prueba: editar nombre → restablecer
   contraseña y verificar login con la nueva → desactivar (confirmar en el `Modal`) → esa cuenta ya no
   inicia sesión y, si tenía sesión abierta, en su siguiente clic es expulsada al login (SC-004) →
   reactivar → vuelve a entrar.
5. **Último master (Edge Cases, SC-005)**: con un solo `master` activo, intentar desactivarlo / cambiar su
   perfil a `Administrador` / (si aplica) eliminarlo → cada intento es rechazado con mensaje "Debe existir
   al menos un usuario Master activo…". Crear un segundo `master`, y entonces sí se puede degradar/desactivar
   al primero.
6. **Auto-protección (FR-015)**: como `master`, intentar desactivar la propia cuenta → rechazado.
7. **Registro público retirado (FR-017)**: en sesión de invitado, ir a `/register` → `404`; la pantalla de
   login no ofrece enlace de registro.
8. **Auditoría (FR-018, SC-006)**: tras las acciones anteriores, revisar `storage/logs/seguridad-*.log` →
   hay una entrada por cada alta / cambio de perfil / restablecimiento / desactivación / reactivación, con
   `actor_email` y `usuario_afectado_id`.

## Revisión de interfaz (Constitución, Principio VI)

Antes de dar la tarea por completa, ejecutar la revisión con el skill `impeccable` (`/impeccable audit` o
`/impeccable polish`) sobre las vistas nuevas (`resources/views/usuarios/**`) y el sidebar modificado
(`components/layouts/app-bootstrap.blade.php`), y actualizar `DESIGN.md` según corresponda. El hook
determinístico de `.claude/settings.json` corre igual, pero no sustituye esta revisión.
