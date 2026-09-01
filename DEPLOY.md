# Despliegue en Render (plan gratuito)

Render no tiene un runtime nativo de PHP, así que la app se publica como **servicio
Docker**. Todo lo necesario ya está en el repo:

| Archivo | Para qué sirve |
|---|---|
| `Dockerfile` | Imagen de 2 etapas: compila los assets con Vite (Node) y arma el runtime PHP 8.3 con **FrankenPHP** (un solo proceso, sirve `public/` y escucha en `$PORT`). |
| `.dockerignore` | Excluye `vendor/`, `node_modules/`, `tests/`, `specs/`, `.env*`, etc. del contexto de build. |
| `docker/entrypoint.sh` | Arranque del contenedor: fija el puerto, genera `APP_KEY` si falta, corre `migrate --force`, siembra la cuenta Master inicial y cachea `config`/`views`. |
| `render.yaml` | *Blueprint*: crea **1 PostgreSQL gratis** + **1 servicio web Docker** y cablea las variables de entorno. |
| `database/seeders/UsuarioInicialSeeder.php` | Crea la cuenta de acceso Master la primera vez (el registro público se quitó en specs/040). Idempotente. |

---

## 1. Requisitos

- El repositorio subido a GitHub, GitLab o Bitbucket.
- Una cuenta en <https://render.com> (el plan gratuito no pide tarjeta).

## 2. Publicar con el Blueprint

1. En el panel de Render: **New → Blueprint**.
2. Elegí este repositorio. Render detecta `render.yaml` y muestra los recursos a crear:
   - base de datos `rent-tracker-db` (PostgreSQL, plan `free`)
   - servicio web `rent-tracker` (Docker, plan `free`)
3. **Apply**. El primer build tarda ~5–8 min (compila assets + instala dependencias).

`DB_URL` se conecta solo a la base recién creada; `SESSION_DRIVER`, `CACHE_STORE`,
`LOG_CHANNEL=stderr`, etc. ya vienen fijados en `render.yaml`.

## 3. Pasos posteriores al primer deploy

En **rent-tracker → Environment**, definí estas variables (marcadas `sync: false`,
es decir "se configuran a mano"):

| Variable | Valor |
|---|---|
| `APP_KEY` | Generala con `php artisan key:generate --show` (localmente) y pegá el `base64:…` completo. Si la dejás vacía, el contenedor genera una **efímera** en cada arranque y las sesiones se pierden cada vez que el servicio duerme. |
| `APP_URL` | La URL pública del servicio, p. ej. `https://rent-tracker.onrender.com`. Evita enlaces/redirecciones a `http://localhost` y el *mixed content* de los assets. |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | *(Opcional)* credenciales de la cuenta Master inicial. Si no las ponés se usa `admin@rent-tracker.test` / `demo1234`. |

Después de guardar, **Manual Deploy → Deploy latest commit** (o esperá al
autodeploy) para que `config:cache` tome el `APP_URL` y el `APP_KEY` definitivos.

### Ingresar

Andá a `https://<tu-servicio>.onrender.com/login` y entrá con la cuenta Master
(`ADMIN_EMAIL` / `ADMIN_PASSWORD`, o los valores por defecto de arriba).
**Cambiá la contraseña** desde *Configuración → tu perfil* apenas ingreses.

### (Opcional) Cargar datos de demostración

El `DatabaseSeeder` completo crea una galería con locales, contratos, recibos y
pagos de ejemplo. Ejecutalo **una sola vez** (no es idempotente) desde la
pestaña **Shell** del servicio (disponible en instancias de pago) o corriendo el
comando contra la base externa:

```bash
php artisan db:seed --force
```

## 4. Límites del plan gratuito

- **El servicio web se duerme tras 15 min sin tráfico.** La primera petición
  después de dormir tarda ~30–60 s (arranque en frío + migraciones + caché).
- **La base PostgreSQL gratuita caduca a los 90 días** y se borra. Hacé un
  `pg_dump` periódico si los datos importan, o pasá la base a un plan pago.
- **Sin worker.** `QUEUE_CONNECTION=sync`: las exportaciones a Excel/PDF y los
  correos corren dentro del request. Para volúmenes grandes puede agotar los
  512 MB de RAM o el tiempo de respuesta.
- **Sistema de archivos efímero.** El log `storage/logs/seguridad.log` (specs/040)
  y cualquier archivo subido se pierden al reiniciar. Los logs de la app van a
  `stderr` y se ven en la pestaña **Logs** de Render. Para conservar el historial
  de seguridad o los adjuntos hace falta un almacenamiento externo (S3, disco de
  pago).
- El correo (`MAIL_MAILER`) queda en `log` por defecto: configurá un proveedor
  SMTP en Environment si necesitás enviar correos de verdad.

## 5. Probar la imagen localmente antes de publicar

```bash
# Construir
docker build -t rent-tracker .

# Levantar una base de prueba
docker run -d --name rt-db -e POSTGRES_USER=rent_tracker \
  -e POSTGRES_PASSWORD=secret -e POSTGRES_DB=rent_tracker -p 5432:5432 postgres:16

# Levantar la app apuntando a esa base
docker run --rm -p 8080:8080 \
  -e PORT=8080 \
  -e APP_ENV=production -e APP_DEBUG=false \
  -e DB_CONNECTION=pgsql \
  -e DB_URL="postgres://rent_tracker:secret@host.docker.internal:5432/rent_tracker" \
  -e SESSION_DRIVER=database -e CACHE_STORE=database -e QUEUE_CONNECTION=sync \
  rent-tracker
```

Abrí <http://localhost:8080/login>. (`APP_KEY` se autogenera; para pruebas
repetibles pasá también `-e APP_KEY=base64:...`.)

## 6. Solución de problemas

| Síntoma | Causa / arreglo |
|---|---|
| `500` en todas las rutas y en Logs aparece `No application encryption key` | Falta `APP_KEY` válida. Definila en Environment y redeploy. |
| Los estilos no cargan / assets a `http://localhost` | `APP_URL` no está en `https://…`. Corregila y redeploy. |
| Bucle de redirección a HTTPS | Igual que arriba: `APP_URL` con `https://` y redeploy. |
| `SQLSTATE… could not translate host name` | La base aún no terminó de crearse, o `DB_URL` no quedó cableada. Verificá que `rent-tracker-db` esté *Available* y redeploy. |
| El deploy falla en `route:cache` | No debería: el entrypoint **no** cachea rutas a propósito (`routes/web.php` tiene una ruta con Closure). Si lo agregás, fallará. |
| Primer request lentísimo | Es el arranque en frío del plan gratuito (servicio dormido). Normal. |
