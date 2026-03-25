FROM php:8.3-fpm-alpine

# Instalar dependencias del sistema
RUN apk add --no-cache \
    build-base \
    curl \
    git \
    wget \
    zip \
    unzip \
    postgresql-dev \
    mysql-client \
    oniguruma-dev \
    libxml2-dev \
    libzip-dev \
    supervisor

# Instalar extensiones PHP necesarias para Laravel
# Nota: json viene incluido en PHP 8.0+, no necesita compilarse
RUN docker-php-ext-install \
    bcmath \
    ctype \
    fileinfo \
    mbstring \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    tokenizer \
    xml \
    zip \
    opcache

# Instalar extensión Redis desde PECL
RUN pecl install redis && docker-php-ext-enable redis

# Instalar Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Crear usuario para Laravel
RUN addgroup -g 1000 laravel && \
    adduser -D -u 1000 -G laravel laravel

# Establecer directorio de trabajo
WORKDIR /app

# Copiar archivos del proyecto
COPY --chown=laravel:laravel . .

# Instalar dependencias PHP
RUN composer install --no-interaction --no-progress --no-dev --optimize-autoloader

# Crear directorios necesarios con permisos correctos
RUN mkdir -p storage/logs bootstrap/cache && \
    chown -R laravel:laravel storage bootstrap

# Crear script de entrada
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copiar configuración de PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

# Setup Supervisor para mantener la aplicación corriendo
COPY docker/php/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Usuario de ejecución
USER laravel

# Exponer puerto si se necesita (reservado para healthcheck)
EXPOSE 9000

# Healthcheck
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD php -r "exit((extension_loaded('pdo') && extension_loaded('mysql')) ? 0 : 1);" || exit 1

# Script de entrada
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
