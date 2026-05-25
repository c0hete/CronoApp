#!/bin/sh
# Crono — entrypoint de producción (Apache).
# Ajusta permisos de storage/cache en cada arranque: el volumen crono-fotos se monta
# vacío como root en runtime, así que hay que chownearlo DESPUÉS del montaje para que
# Apache (www-data) pueda escribir las fotos-evidencia. Idempotente.
set -e

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Arranca Apache en foreground (comando por defecto de la imagen php:apache)
exec apache2-foreground
