# Crono — imagen PHP 8.3-FPM (paridad local Windows ↔ producción Ubuntu)
# Solo código + entorno. Datos, .env, fotos y logos viven en volúmenes/variables.
FROM php:8.3-fpm

# Dependencias del sistema para las extensiones PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libpng-dev libjpeg-dev libwebp-dev libfreetype6-dev \
    libonig-dev default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PHP que necesitan Laravel 13 + Crono (MySQL, GD para Intervention, etc.)
RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install pdo_mysql mbstring zip gd bcmath opcache

# Composer (desde imagen oficial)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Entrypoint: ajusta permisos de storage/cache (Laravel necesita escribir ahí)
# en cada arranque. Funciona igual en local y en Ubuntu, sobre código montado.
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
