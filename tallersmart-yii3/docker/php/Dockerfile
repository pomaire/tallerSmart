FROM php:8.2-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libicu-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar usuario y permisos
RUN usermod -u 1000 www-data

WORKDIR /var/www/html

# Copiar archivos de la aplicación
COPY . /var/www/html/

# Configurar Git para aceptar el directorio como seguro
RUN git config --global --add safe.directory /var/www/html

# Establecer APP_ENV para la construcción
ENV APP_ENV=dev

# Instalar dependencias con Composer
RUN composer update --optimize-autoloader --no-interaction --no-scripts

# Ejecutar migraciones (si existen)
RUN if [ -d "migrations" ] && [ "$(ls -A migrations/*.php 2>/dev/null)" ]; then \
        php yii migrate --interactive=0 || true; \
    fi

# Configurar permisos y directorios necesarios
RUN mkdir -p /var/www/html/runtime/cache \
    && mkdir -p /var/www/html/runtime/logs \
    && mkdir -p /var/www/html/public/assets \
    && mkdir -p /var/www/html/web/assets \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/runtime \
    && chmod -R 775 /var/www/html/public \
    && chmod -R 775 /var/www/html/public/assets \
    && chmod -R 775 /var/www/html/web/assets

EXPOSE 9000

CMD ["php-fpm"]
