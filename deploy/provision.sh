#!/usr/bin/env bash
# =====================================================================
# Crono — aprovisionamiento de una instancia nueva (un cliente).
#
# Modelo instancia-por-cliente: cada cliente corre su propia instancia (server + DB
# propios). Este script levanta una desde cero, en el server (Ubuntu, Docker).
#
# Destila el workflow real del primer deploy (ver docs/deploy-hub.md). Idempotente
# donde se puede; pensado para correr EN el server, dentro del directorio del repo
# ya clonado (~/apps/<cliente>/).
#
# Uso:
#   ./deploy/provision.sh --nombre "Fugo Sushi" --email dueno@cliente.cl
#
# Requisitos en el server: Docker + plugin compose, y este repo ya clonado.
# NO crea el proxy host ni el cert (eso es específico del NPM del hub — ver deploy-hub.md).
# =====================================================================
set -euo pipefail

NOMBRE=""
EMAIL=""
COMPOSE_FILE="docker-compose.prod.yml"

while [ $# -gt 0 ]; do
  case "$1" in
    --nombre) NOMBRE="$2"; shift 2 ;;
    --email)  EMAIL="$2";  shift 2 ;;
    --compose) COMPOSE_FILE="$2"; shift 2 ;;
    *) echo "Opción desconocida: $1"; exit 1 ;;
  esac
done

dc() { docker compose -f "$COMPOSE_FILE" "$@"; }

echo "==> 1/7  Verificando prerequisitos"
command -v docker >/dev/null || { echo "Docker no está instalado"; exit 1; }
[ -f "$COMPOSE_FILE" ] || { echo "No se encuentra $COMPOSE_FILE (¿estás en el dir del repo?)"; exit 1; }

echo "==> 2/7  Preparando .env"
if [ ! -f .env ]; then
  cp .env.production.example .env
  # Generar secretos en el server (nunca hardcodear)
  APP_KEY="base64:$(openssl rand -base64 32)"
  DB_PASS="$(openssl rand -base64 24 | tr -d '/+=' | head -c 28)"
  sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" .env
  sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env
  chmod 600 .env
  echo "    .env creado con secretos generados."
else
  echo "    .env ya existe, se respeta (no se regeneran secretos)."
fi

echo "==> 3/7  Build + up del stack"
dc up -d --build

echo "==> 4/7  Esperando a que MySQL esté healthy"
for i in $(seq 1 30); do
  dc ps --format '{{.Service}}:{{.Status}}' | grep -q 'mysql.*healthy' && break
  sleep 2
done

echo "==> 5/7  Migraciones + seed (roles + empresa + configuración)"
dc exec -T app php artisan migrate --force
dc exec -T app php artisan db:seed --force

echo "==> 6/7  Caches de producción (config + route; NO view)"
dc exec -T app php artisan config:cache
dc exec -T app php artisan route:cache

echo "==> 7/7  Usuario dueño"
if [ -n "$NOMBRE" ] && [ -n "$EMAIL" ]; then
  PASS="$(openssl rand -base64 12 | tr -d '/+=' | head -c 14)"
  dc exec -T app php artisan crono:crear-dueno --name="$NOMBRE" --email="$EMAIL" --password="$PASS"
  echo ""
  echo "    >>> Dueño: $EMAIL  ·  contraseña temporal: $PASS  (CAMBIAR al primer login)"
else
  echo "    (sin --nombre/--email: crear el dueño manualmente con 'php artisan crono:crear-dueno')"
fi

echo ""
echo "✓ Instancia aprovisionada. Falta (manual, según el server):"
echo "   - DNS del dominio del cliente → IP del server (grey/DNS-only si es Cloudflare)."
echo "   - Proxy host + cert TLS en el reverse-proxy (ver docs/deploy-hub.md)."
echo "   - Setear branding (nombre/color/logo) del cliente en /panel/personalizacion."
