# syntax=docker/dockerfile:1
#
# specs — Despliegue en Render (plan gratuito).
# Render no tiene runtime nativo de PHP, así que se publica como servicio Docker.
# Imagen final: FrankenPHP (un solo proceso, sirve Laravel desde /app/public y
# escucha en el puerto que Render inyecta vía $PORT).

# ---------------------------------------------------------------------------
# Etapa 1 — Compilar los assets del frontend con Vite
# ---------------------------------------------------------------------------
FROM node:20-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources/ resources/
COPY public/ public/

# Genera public/build (CSS/JS con hash + fuentes Instrument Sans autohospedadas).
RUN npm run build


# ---------------------------------------------------------------------------
# Etapa 2 — Runtime PHP 8.3 + FrankenPHP
# ---------------------------------------------------------------------------
FROM dunglas/frankenphp:1-php8.3-alpine AS runtime

# Extensiones que necesitan Laravel + PostgreSQL + dompdf + PhpSpreadsheet.
RUN install-php-extensions \
        pdo_pgsql \
        gd \
        zip \
        intl \
        bcmath \
        exif \
        opcache \
        pcntl

# Composer (binario de la imagen oficial).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# --- Dependencias PHP (capa cacheable por composer.json / composer.lock) ---
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

# --- Código de la aplicación ---
COPY . .

# Assets ya compilados en la etapa 1.
COPY --from=assets /app/public/build ./public/build

# Autoload optimizado + descubrimiento de paquetes.
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && php artisan package:discover --ansi

# Configuración de PHP orientada a producción + OPcache.
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=0'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
    } > "$PHP_INI_DIR/conf.d/zz-opcache.ini"

# Permisos de escritura para storage y la caché del framework.
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# El puerto real lo fija Render con $PORT; el entrypoint lo traduce a SERVER_NAME.
EXPOSE 8080

ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
