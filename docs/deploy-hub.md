# Plan de deploy — Crono en el hub (cronoapp.alvaradomazzei.cl)

> Plan para desplegar la PRIMERA instancia de Crono (piloto Fugo Sushi) en el server
> `hub` (184.174.33.249), detrás del Nginx Proxy Manager existente. Adaptado del patrón
> `JRAM/infraestructura/hub/documentacion/DEPLOY_LARAVEL_HUB.md`, con las diferencias
> propias de Crono marcadas. **NO ejecutado aún — este documento es el plan a revisar.**

---

## En qué Crono DIFIERE del patrón estándar del hub

El hub desplegó iacode/bitacora (Laravel + Vite + Inertia/Filament + PostgreSQL). Crono
es más simple en unos puntos y distinto en otros:

| Aspecto | Patrón hub | Crono | Implicancia |
|---|---|---|---|
| **Base de datos** | PostgreSQL 15 | **MySQL 8** | Stack propio aislado. Coexiste sin choque (ver abajo). |
| **Assets / Vite** | Vite + pnpm + sidecar de build | **Sin Vite**: Alpine por CDN + Blade | **No se usa el sidecar `hub-build-laravel:8.4`.** Deploy más simple. |
| **Carpeta** | `~/sites/` (viejas) / `~/apps/` (nuevas) | `~/apps/crono/` | Convención nueva (hermana de bitacora). |
| **Extensiones runtime** | pdo_pgsql | **pdo_mysql + gd** | El Dockerfile de Crono ya las trae (no el sidecar). |

> Como Crono no compila assets (Alpine vía CDN), **saltamos todo el flujo de sidecar/pnpm/
> wayfinder** que generó las 7 iteraciones fallidas de iacode. El deploy de Crono es
> sustancialmente más corto.

---

## Sobre el aislamiento de la DB (verificado en el server, 2026-05-24)

Dos garantías confirmadas mirando el server real:

1. **No hay choque de puertos.** Hoy corren 4 Postgres simultáneos (`bitacora_db`,
   `iacode_db`, `portfolio_db`, `superset_db`), todos en `5432` interno, sin chocar:
   ninguno publica al host (`docker ps` muestra `5432/tcp`, NO `0.0.0.0:5432->`). En el
   host solo escuchan 80/81/443 (NPM) y 22022 (SSH). El MySQL de Crono vivirá igual:
   `3306` interno a su red, **sin publicar al host**. Coexiste con los 4 Postgres.
   - ⚠️ Quitar el mapeo `ports: "3307:3306"` del `docker-compose.yml` para producción
     (ese mapeo es solo de desarrollo local en Windows).

2. **Los datos NO son volátiles.** El contenedor mysql es desechable; los datos viven en
   un **volumen Docker nombrado** (`crono-db`) que sobrevive a down/up/rebuild/reboot.
   Solo se borran con `down -v` explícito. (Por eso `portfolio_db` lleva semanas sin
   perder datos.) Ya está así en el compose de Crono.

---

## Pre-flight

- [x] DNS: `cronoapp.alvaradomazzei.cl` A → `184.174.33.249`, **grey/DNS-only**, TTL auto. (creado por José)
- [x] Server con espacio: 63 GiB libres, 7 GiB RAM libre (verificado).
- [x] Conexión SSH por llave verificada (`id_ed25519_hub`, puerto 22022, user master).
- [ ] Confirmar con `dig +short cronoapp.alvaradomazzei.cl` → debe dar `184.174.33.249`, NO IP de Cloudflare.
- [ ] Repo accesible desde el server (deploy key SSH dedicada, como bitacora — el repo es privado).
- [ ] Credenciales nuevas generadas (DB pass, APP_KEY) — NUNCA en el chat ni versionadas.
- [ ] NPM admin password a mano (para crear el proxy host vía API).

---

## Cambios de CÓDIGO en el repo (antes de desplegar)

Estos van committeados ANTES del deploy. Son los 3 fixes de "Laravel detrás de NPM"
(la app corre detrás del proxy que termina el TLS):

**a) `bootstrap/app.php`** — confiar en el proxy:
```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->trustProxies(at: '*');
    // ... (alias de Spatie ya presentes)
})
```

**b) `.env` de producción** — `APP_URL` y `ASSET_URL` en https (lección 7/29 del hub:
con TrustProxies + estas dos vars, NO hace falta `URL::forceScheme` — quitarlo si se agrega).

**c) Dockerfile de producción** (`docker/php/Dockerfile.prod` o ajuste del actual):
- Base `php:8.4-apache` (alineado con el hub; Crono pide ^8.3, 8.4 es compatible).
  - DECISIÓN: ¿mantener php-fpm+nginx (como dev) o pasar a apache (como el resto del hub)?
    El hub usa apache+NPM. Para coherencia y reusar el patrón, **apache**. Revisar.
- Extensiones: `pdo_mysql mbstring zip gd bcmath opcache intl` (Crono usa GD para fotos).
- `opcache.validate_timestamps=1 revalidate_freq=2` (lección 24 del hub).
- COPY del código (no bind-mount); permisos storage/cache a www-data.

> El `docker-compose.yml` de producción difiere del de dev: sin `ports` en mysql,
> network `nginx_network` (external), `APP_ENV=production`. Se versiona aparte
> (`docker-compose.prod.yml`) o se ajusta en el server.

---

## Workflow de deploy (resumen; detalle en DEPLOY_LARAVEL_HUB.md del hub)

1. **Clonar** en `~/apps/crono/` (deploy key para repo privado).
2. **`.env` producción** server-side: `APP_ENV=production`, `APP_DEBUG=false`,
   `APP_URL`/`ASSET_URL=https://cronoapp.alvaradomazzei.cl`, DB MySQL interna,
   `CRONO_EMPRESA_ID=1`, mail si aplica. Generar `APP_KEY` con `openssl rand` + `sed`
   (lección 20: `key:generate` falla silencioso con .env chmod 600 / container www-data).
3. **Build + up**: `docker compose -f docker-compose.prod.yml up -d --build`
   (ANTES de migrar — el container corre código COPY'd, no el del host).
4. **Migrar + seed**: `migrate --force` + `db:seed --force` (empresa id=1 + config +
   roles). **NUNCA `migrate:fresh` una vez que Fugo tenga marcajes reales.**
5. **Crear dueño**: `php artisan crono:crear-dueno` (el comando del Paso 3).
6. **Caches**: `config:cache` + `route:cache`. (Crono no usa Filament, así que `view:cache`
   no tiene el problema de bitacora — pero por ahora omitir para simplicidad.)
7. **Verificar contenedor** antes de tocar NPM:
   `docker run --rm --network nginx_network curlimages/curl -sI http://crono_app/` → 200/302.
8. **NPM proxy host** vía API REST → `crono_app:80`, websocket upgrade on.
9. **Cert LE** vía API (CF en grey confirmado) → asociar, force SSL + HTTP/2 + HSTS.
10. **Verificar e2e** desde fuera: `curl -sI https://cronoapp.alvaradomazzei.cl/` → 200.

---

## Específico de Crono — no olvidar

- **HTTPS habilita la cámara.** `getUserMedia` (kiosko `/marcar`) exige contexto seguro.
  En `http://localhost` dev funciona; en el server SOLO con el HTTPS del cert LE. Por eso
  el deploy con cert es lo que destraba probar el marcaje real con tablet.
- **Backup de la DB.** El `backup_bunker.sh` del hub NO respalda DBs de apps (pendiente
  conocido). Con datos reales de Fugo, sumar `mysqldump` de `crono_db` al backup. Anotar
  como pendiente post-deploy (no bloquea el primer deploy de prueba).
- **Disco de fotos.** Las fotos-evidencia van a un volumen, fuera de public. Vigilar
  crecimiento (el scheduler de purga es el Paso 9, aún no construido — al inicio las fotos
  se acumulan hasta que exista `fotos:purgar`).
- **Branding del cliente.** Tras el deploy, setear `marca_nombre`/`marca_color_primario`
  de Fugo vía seeder o panel (Paso 8 aún no construido; por ahora vía `Configuracion::poner`).
- **Scheduler.** El `docker-compose` de dev tiene un servicio `scheduler` (schedule:work).
  En prod aún no hay tareas reales (purga/monitor = Paso 9). Mantenerlo no molesta.

---

## Orden sugerido respecto a la implementación

Crono está en el Paso 5/11. Se puede desplegar AHORA (lo construido ya marca de verdad) para:
- Destrabar la cámara (HTTPS) y probar el flujo real con una tablet.
- Validar el deploy temprano, no al final (menos sorpresas).

Lo que falta (offline P6, panel/reportes P7, branding P8, scheduler P9, admin P10, PWA P11)
se irá redeployando con el mismo workflow (`git pull` → `up -d --build` → `migrate --force`).
