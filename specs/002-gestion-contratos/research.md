# Research: Gestión de Contratos de Locación

**Feature**: `002-gestion-contratos` | **Date**: 2026-08-19

Este documento resuelve las decisiones técnicas necesarias antes del diseño (Fase 1), a partir de las restricciones explícitas del usuario ("Laravel + PHP en su última versión, monolito para shared hosting, PostgreSQL en su última versión, archivos en carpeta del proyecto con solo la ruta en base de datos") y de la Constitución del proyecto.

## 1. Versiones de PHP, Laravel y PostgreSQL

**Decision**: PHP 8.3+ (mínimo garantizado por la mayoría de shared hosting con cPanel/Plesk actuales), Laravel 11.x LTS o superior disponible al momento de `composer create-project`, PostgreSQL 16+ (o la versión más reciente que el proveedor de shared hosting exponga como base de datos gestionada).

**Rationale**: El usuario pide "la última versión" de PHP y Laravel, pero el despliegue real está condicionado por lo que el proveedor de shared hosting soporte. En shared hosting es común que el panel de control limite la versión de PHP disponible (normalmente rezagada 1-2 versiones menores respecto a la última release). Se fija PHP 8.3+ como piso porque es el mínimo ampliamente disponible en shared hosting moderno y es compatible con Laravel 11/12. Debe verificarse la versión exacta soportada por el proveedor de hosting elegido antes de desplegar, y usar la más alta disponible.

**Alternatives considered**:
- Fijar una versión exacta (ej. "PHP 8.4 exacto"): rechazado porque no se puede garantizar disponibilidad en un shared hosting no elegido aún.
- Usar MySQL en vez de PostgreSQL por mayor disponibilidad en shared hosting: rechazado porque el usuario y la Constitución (Principio I) exigen explícitamente PostgreSQL.

## 2. Restricción de "monolito para shared hosting"

**Decision**: Aplicación Laravel monolítica única (Blade + componentes ligeros con Alpine.js, sin build de Node.js requerido en producción), sesiones y colas con drivers compatibles con shared hosting (`database` para sesiones/colas/caché en vez de Redis), sin contenedores ni orquestación en producción, tareas programadas vía el scheduler de Laravel activado con una única entrada de cron (`* * * * * php artisan schedule:run`), tal como lo permite cPanel.

**Rationale**: Shared hosting típicamente no ofrece: acceso root, Redis/Memcached persistente, procesos en segundo plano de larga duración (queue workers `supervisor`), ni Docker. Usar drivers basados en la propia base de datos PostgreSQL para sesiones, caché y colas evita dependencias de infraestructura adicional. Los assets de frontend (CSS/JS) se compilan con Vite en tiempo de build/despliegue y se suben ya compilados; Node.js no necesita ejecutarse en el servidor de producción.

**Alternatives considered**:
- Colas con Redis: rechazado, no garantizado en shared hosting.
- Laravel Octane/Swoole para rendimiento: rechazado, requiere un proceso persistente no disponible en shared hosting típico.
- Frontend SPA separado (Vue/React con API): rechazado, aumenta la complejidad operativa (build separado, CORS, dos despliegues) sin necesidad para este dominio; la Constitución ya orienta a "Blade Templates o componentes desacoplados".

## 3. Almacenamiento de archivos (PDF/fotos del contrato)

**Decision**: Los archivos se guardan en el sistema de archivos local del servidor usando el disco `local` por defecto de Laravel 11+, cuya raíz ya apunta a `storage/app/private/` (no público), bajo `storage/app/private/contratos/{contrato_id}/`, sin usar el enlace simbólico público de Laravel (`storage:link`, que solo expone `storage/app/public/`). El acceso a cada archivo se sirve mediante una ruta de descarga/visualización autenticada que verifica sesión de Administrador antes de transmitir el contenido. En la base de datos (`documentos_contrato.ruta_archivo`) solo se almacena la ruta relativa del archivo (relativa a la raíz del disco `local`), nunca el contenido binario.

**Nota de implementación (confirmada en Fase de implementación)**: Laravel 11+ ya define por defecto `'local' => ['root' => storage_path('app/private'), 'serve' => true]` en `config/filesystems.php`, es decir, el framework adopta nativamente el patrón "privado por defecto" que se buscaba aquí. No fue necesario definir un disco custom adicional; basta con usar `Storage::disk('local')` apuntando al subdirectorio `contratos/`.

**Rationale**: El usuario pide explícitamente "imagenes y archivos deben cargarse en una carpeta del proyecto, en base de datos solo se almacena la ruta"; la especificación 002 (Asunción A-001) exige además que los archivos no sean públicos directamente. Evitar el symlink público (`public/storage`) también evita un problema práctico común en shared hosting: algunos proveedores no permiten crear symlinks desde SSH limitado o el panel de control.

**Alternatives considered**:
- Guardar en `public/` directamente accesible por URL: rechazado, viola la asunción de confidencialidad de los documentos del contrato.
- Almacenamiento en la nube (S3, etc.): rechazado explícitamente por el usuario ("carpeta del proyecto").
- Guardar el binario en una columna `bytea` de PostgreSQL: rechazado explícitamente por el usuario ("en base de datos solo se almacena la ruta").

## 4. Validación de no solapamiento de fechas de contrato (FR-003)

**Decision**: Validación a nivel de servicio de aplicación dentro de `DB::transaction`, con bloqueo pesimista (`lockForUpdate()`) sobre los contratos existentes de la misma locación antes de comprobar solapamiento (`fecha_inicio <= :nueva_fecha_fin AND fecha_fin >= :nueva_fecha_inicio`, excluyendo contratos en estado "rescindido"/"cancelado"), complementada con una restricción `CHECK`/índice a nivel de base de datos donde sea viable sin extensiones no garantizadas.

**Rationale**: PostgreSQL ofrece restricciones `EXCLUDE` con rangos (`daterange` + operador `&&`) para garantizar esto de forma nativa, pero requiere la extensión `btree_gist`, cuya instalación no está garantizada en una base de datos PostgreSQL gestionada de shared hosting (el usuario podría no tener permisos de superusuario para `CREATE EXTENSION`). Por eso la validación primaria se implementa en la capa de aplicación (Service dentro de una transacción con bloqueo de filas), que no depende de extensiones del servidor, tal como exige el Principio V de la Constitución (transacciones atómicas). Si el entorno de despliegue confirma soporte para `btree_gist`, se puede añadir la restricción `EXCLUDE` como capa adicional de defensa en una migración posterior.

**Alternatives considered**:
- Restricción `EXCLUDE` con `btree_gist` como único mecanismo: rechazado como único mecanismo por el riesgo de no disponibilidad de la extensión en shared hosting; se documenta como mejora opcional, no como dependencia dura.
- Validación solo en el Form Request sin transacción/bloqueo: rechazado, no previene condiciones de carrera entre dos solicitudes concurrentes.

## 5. Framework de pruebas

**Decision**: Pest (sobre PHPUnit, incluido por defecto en instalaciones nuevas de Laravel 11/12), con `RefreshDatabase` contra una base de datos PostgreSQL de pruebas.

**Rationale**: La Constitución permite "PHPUnit / Pest"; Pest es el default actual de `laravel/laravel` y ofrece sintaxis más legible sin sacrificar compatibilidad con las aserciones y utilidades de PHPUnit/Laravel (incluye feature tests HTTP y unit tests de modelo, requeridos por el Principio IV).

**Alternatives considered**:
- PHPUnit puro: viable y permitido por la Constitución, pero se prefiere Pest por ser el estándar actual generado por `laravel new`.

## 6. Entidad `Inquilino` referenciada por `Contrato.inquilino_id`

**Decision**: Se crea una entidad mínima `Inquilino` (id, nombre, timestamps) como soporte necesario para satisfacer `Contrato.inquilino_id` (FR-002 de la especificación 002), ya que ninguna especificación previa define su gestión completa.

**Rationale**: La especificación 002 exige el campo `inquilino_id` pero no existe una especificación dedicada a la gestión de inquilinos; la especificación 003 (Representantes de Contrato) introduce después un catálogo más completo (`Representante`: apellidos, nombres, DNI, fecha de nacimiento) que cubre un rol similar pero fue especificada como una entidad separada y explícitamente vinculada a `Contrato` mediante una tabla pivote, no como reemplazo de `inquilino_id`. Para no bloquear la planificación de 002 de forma aislada, se modela `Inquilino` como una entidad mínima de referencia (similar a un campo de texto estructurado). Se documenta como nota de integración: al planificar la especificación 003, deberá reconciliarse si `inquilino_id` se mantiene, se deprecia en favor de los `representantes` (con `es_principal`), o coexisten ambos conceptos.

**Alternatives considered**:
- Usar un campo de texto libre `inquilino_nombre` en vez de una tabla: rechazado, la especificación 002 define explícitamente `inquilino_id` como referencia a una entidad `Inquilino`, y usar texto libre impediría relaciones futuras (historial de contratos por inquilino).
