#!/bin/sh
# Arranque del contenedor en Render. Prepara la aplicación y luego cede el
# control al proceso indicado en CMD (FrankenPHP).
set -e

# --- Puerto ---------------------------------------------------------------
# Render inyecta $PORT (10000 por defecto). El Caddyfile de FrankenPHP escucha
# en {$SERVER_NAME}; sin nombre de host, Caddy sirve HTTP plano en ese puerto
# (Render termina el TLS por delante).
export SERVER_NAME=":${PORT:-8080}"

# --- Clave de aplicación ------------------------------------------------
# Si no se definió APP_KEY en el panel de Render, se genera una efímera para
# no bloquear el arranque. Para que las sesiones sobrevivan a los reinicios
# (el plan gratuito duerme el servicio), definí una APP_KEY fija en Render
# (ver DEPLOY.md).
if [ -z "${APP_KEY:-}" ]; then
    APP_KEY="$(php artisan key:generate --show)"
    export APP_KEY
    echo "entrypoint: APP_KEY efímera generada — definí una fija en Render para persistir sesiones."
fi

# --- Preparación de la aplicación --------------------------------------
php artisan storage:link 2>/dev/null || true

# Esquema al día. --force omite la confirmación interactiva en producción.
php artisan migrate --force --no-interaction

# Datos de PRUEBA (destructivo sobre el dominio). Se ejecuta solo mientras
# SEED_DEMO=true esté en el entorno de Render; quitá la variable después del
# primer arranque para no re-generar los datos en cada deploy.
if [ "${SEED_DEMO:-}" = "true" ]; then
    echo "entrypoint: SEED_DEMO=true — cargando DatosPruebaSeeder."
    php artisan db:seed --class="Database\\Seeders\\DatosPruebaSeeder" --force --no-interaction
fi

# Cuenta de acceso inicial (perfil Master). Idempotente: no duplica ni pisa una
# cuenta existente. Sin esto no habría forma de iniciar sesión (specs/040 quitó
# el registro público).
php artisan db:seed --class="Database\\Seeders\\UsuarioInicialSeeder" --force --no-interaction

# Cachés de producción. NO se cachean rutas: routes/web.php usa una ruta con
# Closure ('/') que no es serializable.
php artisan config:cache
php artisan view:cache
php artisan event:cache 2>/dev/null || true

exec "$@"
