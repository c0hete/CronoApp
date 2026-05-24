#!/bin/sh
# Crono — entrypoint del contenedor app.
# Asegura que php-fpm (www-data) pueda escribir en storage/ y bootstrap/cache/.
# Idempotente; corre en cada arranque. Mismo comportamiento local y en Ubuntu.
set -e

if [ -d /var/www/html/storage ]; then
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
    chmod -R ug+rwX /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
fi

# Ejecuta el comando del contenedor (php-fpm, o schedule:work en el scheduler)
exec "$@"
