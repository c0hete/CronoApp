<div align="center">

# Crono

**SaaS de control de gestión de asistencia para PYMEs por turnos.**
Marca entrada/salida en una tablet, calcula atrasos y los traduce a *costo de horas no trabajadas* — un indicador de gestión para el dueño del negocio.

[![CI](https://github.com/c0hete/CronoApp/actions/workflows/ci.yml/badge.svg)](https://github.com/c0hete/CronoApp/actions/workflows/ci.yml)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-2496ED?logo=docker&logoColor=white)
![Tests](https://img.shields.io/badge/tests-69%20passing-3ddc84)

</div>

---

## Qué es

Crono es un producto **multi-instancia** (white-label): una sola base de código que se despliega como **instancia dedicada por cliente** (servidor + base de datos propios, aislamiento físico total). Cada instancia se ve como el negocio del cliente — su nombre, su logo, su color — nunca como "Crono".

No es un registro oficial de jornada ni una herramienta de descuentos: es un **indicador de gestión interno**, diseñado así a propósito para mantenerse fuera de los regímenes legales que eso activaría (Resolución 38 de la Dirección del Trabajo chilena).

| | |
|---|---|
| **Dominio** | Control de asistencia / gestión para PYMEs por turnos (gastronomía, retail, talleres) |
| **Cliente piloto** | Un restaurante (instancia en producción) |
| **Modelo** | Instancia-por-cliente, configurable, white-label |
| **Estado** | MVP completo (11/11 hitos), en producción con HTTPS |

---

## Cómo funciona

```
┌─────────────────┐        ┌──────────────────┐        ┌─────────────────────┐
│  Tablet kiosko   │  HTTPS │   Nginx Proxy    │  HTTP  │   App (Apache+PHP)   │
│  /marcar         │───────▶│   Manager (TLS)  │───────▶│   Laravel 13         │
│  ID + foto       │        └──────────────────┘        │   + MySQL 8 (aislado)│
│  (PWA, offline)  │                                     └─────────────────────┘
└─────────────────┘
        │ sin red: IndexedDB → cola → sync al reconectar (idempotencia por UUID)
        ▼
   Panel del dueño (autenticado):  marcaciones · reportes · personalización · config
```

- **Kiosko de marcaje** (`/marcar`): sin login. El trabajador ingresa su RUT y marca entrada/salida con una foto de evidencia. La cámara solo se enciende durante el marcaje. **Funciona sin conexión** (PWA + IndexedDB): guarda local y sincroniza al volver la red.
- **Panel del dueño**: enrolamiento de trabajadores y contratos, listado de marcaciones con evidencia, **reportes semanales/mensuales** del costo de horas no trabajadas por trabajador, y **personalización** (nombre, color, logo) en autoservicio.
- **Cálculo**: `costo = (minutos_de_atraso / 60) × (sueldo / horas_semanales)`. Base semanal, sueldo bruto/líquido configurable, tolerancia por contrato.

---

## Stack

- **Backend:** Laravel 13 · PHP 8.3
- **Base de datos:** MySQL 8 (una por instancia, datos en volumen persistente)
- **Frontend:** Blade + Alpine.js + Tailwind — sin paso de build, ligero para tablets de gama media
- **PWA:** manifest dinámico (con el branding del cliente) + service worker + cola offline en IndexedDB
- **Auth/Roles:** `spatie/laravel-permission` (dueño / admin)
- **Imágenes:** `intervention/image` (degradado de foto-evidencia)
- **Infra:** Docker (Apache + MySQL + scheduler) detrás de Nginx Proxy Manager con TLS de Let's Encrypt

---

## Decisiones de diseño destacables

- **Multi-instancia por aislamiento, no multi-tenant compartido.** Cada cliente en su propia DB → una brecha en uno no toca a otros (lo más limpio bajo la Ley 21.719 de protección de datos). El esquema mantiene `empresa_id` como costura, por si algún día se consolida.
- **Encuadre legal por diseño.** El lenguaje del producto evita "descuento", "registro de jornada" y "reconocimiento facial" — la foto es *evidencia visual de presencia*, no biometría. Esto mantiene a Crono fuera de regímenes legales que no quiere activar.
- **Los registros nunca se borran solos.** La purga por retención borra *solo la foto*; el marcaje permanece. Ante disco lleno, el sistema **avisa, no borra**.
- **Cálculo crítico aislado y testeado.** Un error en el costo es silencioso (no rompe nada visible, solo da cifras mal). Por eso el `CalculoAtrasoService` es una unidad pura con la batería de tests más grande del proyecto (atraso cero, borde de tolerancia, sin sueldo, fallback bruto/líquido, etc.).
- **RUT canónico.** Se guarda normalizado (sin puntos ni guión) y se muestra formateado — así el teclado del kiosko (solo números + K, sin guión) siempre encuentra al trabajador.

---

## Calidad y seguridad (DevSecOps)

Pipeline de CI/CD en GitHub Actions con cuatro gates:

| Job | Qué hace |
|---|---|
| **Lint + Tests** | Laravel Pint (estilo) + 69 tests PHPUnit (sobre SQLite en memoria) |
| **SCA** | `composer audit` + `pnpm audit` — análisis de dependencias con CVEs |
| **Secret scan** | `gitleaks` sobre código e historial — bloquea credenciales filtradas |
| **Deploy** | `git pull` → rebuild Docker → `migrate` vía SSH, solo si los gates pasan |

- **Gestión de secretos server-side**: credenciales y llave SSH de deploy en GitHub Secrets, cero en el repositorio.
- **Foto-evidencia fuera del directorio público**, servida solo con autorización (sin URLs adivinables).
- Política de remediación de vulnerabilidades documentada en [`SECURITY.md`](SECURITY.md).

---

## Cobertura de tests (69)

```
Unit     · CalculoAtrasoService (casos límite del cálculo) · Rut (normalización/validación) · BrandingService (paleta/contraste)
Feature  · Enrolamiento y edición de trabajadores (RUT, unicidad, sueldo)
         · API de marcaje (idempotencia, doble timestamp, reloj sospechoso, foto)
         · Kiosko (acceso público, aislamiento de datos del dueño)
         · Reportes (agregación semanal/mensual, solo entradas)
         · Branding white-label · Admin de configuración · PWA (manifest) · Scheduler (purga/monitor)
```

---

## Desarrollo local

Requiere Docker. La app corre en contenedores (paridad dev/producción):

```bash
git clone git@github.com:c0hete/CronoApp.git && cd CronoApp
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
# Panel:  http://localhost:8080
# Kiosko: http://localhost:8080/marcar
```

Tests:

```bash
docker compose exec app php artisan test
```

---

## Aprovisionar una instancia nueva

El modelo instancia-por-cliente se levanta con un script idempotente ([`deploy/provision.sh`](deploy/provision.sh)):

```bash
./deploy/provision.sh --nombre "Nombre del Negocio" --email dueno@cliente.cl
# genera .env + secretos, build, migrate, seed, y crea el usuario dueño
```

---

<div align="center">
<sub>Proyecto de producto propio · Laravel 13 · Docker · CI/CD · en producción</sub>
</div>
