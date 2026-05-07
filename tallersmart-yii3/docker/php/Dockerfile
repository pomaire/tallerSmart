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

# Instalar dependencias con Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Ejecutar migraciones (si existen)
RUN if [ -d "migrations" ] && [ "$(ls -A migrations/*.php 2>/dev/null)" ]; then \
        php yii migrate --interactive=0 || true; \
    fi

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/runtime \
    && chmod -R 755 /var/www/html/web

EXPOSE 9000

CMD ["php-fpm"]
