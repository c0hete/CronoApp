# Seguridad y CI/CD — Crono

Este documento describe el pipeline de integración y despliegue continuo de Crono,
los controles de seguridad que aplica, y cómo interpretar y actuar sobre sus resultados.

> **Para qué existe.** Crono maneja datos de personas (marcajes, fotos-evidencia) en una
> instancia dedicada por cliente. El pipeline integra controles de seguridad en el ciclo
> de desarrollo (SDLC) para que ninguna versión llegue a producción sin pasar por lint,
> tests y análisis de dependencias.

---

## El pipeline en una imagen

```
push / pull_request a main
        │
        ├──────────────┐
        ▼              ▼
   ┌─────────┐    ┌──────────┐
   │ quality │    │ security │      ← gates: si alguno falla, no se mergea
   │ Pint +  │    │  SCA:    │        (bloqueo real vía branch protection)
   │ PHPUnit │    │ composer │
   └─────────┘    │ + pnpm   │
        │         │  audit   │
        │         └──────────┘
        └──────┬───────┘
               ▼
          ┌─────────┐
          │ deploy  │   ← SOLO en main, SOLO si quality+security pasaron
          │ SSH →   │     (hoy: disparo manual; ver "Activar deploy automático")
          │ Docker  │
          └─────────┘
```

Definición: [`.github/workflows/ci.yml`](.github/workflows/ci.yml).

---

## Qué hace cada job

### 1. `quality` — calidad de código
| Paso | Herramienta | Qué verifica |
|------|-------------|--------------|
| Lint | **Laravel Pint** (`pint --test`) | Estilo consistente (PSR-12 + preset Laravel). No modifica; solo reporta. |
| Tests | **PHPUnit** (`php artisan test`) | Los 69 tests (Feature + Unit). Corren sobre **SQLite en memoria** (`phpunit.xml`), sin DB externa. |

> **Por qué CI corre los tests mejor que tu máquina.** En Windows local, 9 tests fallan
> porque falta la extensión **GD de PHP** (la usa Intervention Image para procesar las
> fotos). El CI declara `extensions: ... gd ...` en `setup-php`, y producción la trae en
> el Dockerfile. Resultado: los 69 pasan en CI y en Docker. Es el caso de libro de
> "funciona en mi máquina" resuelto por un entorno reproducible.

### 2. `security` — SCA (Software Composition Analysis)
Detecta dependencias con vulnerabilidades conocidas (CVEs). Es el control de
seguridad de cadena de suministro más directo para un proyecto con dependencias.

| Paso | Herramienta | Alcance |
|------|-------------|---------|
| PHP | **`composer audit --no-dev`** | Dependencias de producción (las dev no se despliegan). |
| JS | **`pnpm audit --audit-level=high`** | Dependencias de frontend, severidad alta o superior. |

**Por qué pnpm y no npm.** pnpm usa una estructura estricta de `node_modules` (sin
*hoisting* plano), lo que elimina las *phantom dependencies* (importar un paquete no
declarado) y verifica integridad por content-addressing. Además da paridad con la
infraestructura del hub, que ya usa pnpm. La instalación corre con `--frozen-lockfile`
(build reproducible) y `--ignore-scripts` (no ejecuta `postinstall` de terceros: defensa
anti supply-chain, coherente con el `.npmrc` del repo).

### 3. `deploy` — publicar versión nueva
**No re-aprovisiona.** El aprovisionamiento inicial de una instancia lo hace una sola vez
[`deploy/provision.sh`](deploy/provision.sh). El job de deploy **publica la versión nueva
sobre la instancia que ya existe**, vía SSH y Docker:

```
git pull --ff-only
docker compose -f docker-compose.prod.yml up -d --build   # rebuild: el container corre el código COPY'd
docker compose ... exec -T app php artisan migrate --force # NUNCA migrate:fresh (hay marcajes reales)
docker compose ... exec -T app php artisan config:cache
docker compose ... exec -T app php artisan route:cache     # NO view:cache
```

---

## Cómo interpretar los resultados

| Resultado | Significa | Qué hacer |
|-----------|-----------|-----------|
| ✅ Todo verde | Lint, tests y audits limpios | Mergear / dejar que el deploy publique. |
| ❌ `quality` rojo en Pint | Estilo fuera de convención | `./vendor/bin/pint` local → commit. |
| ❌ `quality` rojo en tests | Un test falla de verdad | Arreglar el código o el test antes de mergear. |
| ❌ `security` rojo | Apareció un **CVE nuevo** no documentado | Ver "Plan de remediación". No ignorar a ciegas. |

---

## Overrides de audit (vulnerabilidades aceptadas con justificación)

Igual que con los hallazgos de OpenVAS en la infraestructura, una vulnerabilidad puede
quedar **aceptada temporalmente** si está documentada, justificada y con plan de
remediación. El `composer audit` del pipeline ignora explícitamente los CVEs de abajo
(`--ignore=<CVE>`), de modo que el job **no se bloquea por ellos, pero SÍ por cualquier
CVE nuevo**.

| CVE | Paquete | Severidad | Estado | Justificación del override |
|-----|---------|-----------|--------|----------------------------|
| CVE-2026-48736 | `symfony/http-foundation` | Media | Override | SSRF bypass en `NoPrivateNetworkHttpClient`. Crono **no** usa ese cliente HTTP para llamadas a redes no confiables. Fix disponible (≥8.0.13), requiere bump Symfony 7.4→8.0 (ver plan). |
| CVE-2026-46644 | `symfony/polyfill-intl-idn` | Baja | Override | Equivalencia insegura de labels IDN punycode. Crono no compara dominios IDN provistos por el usuario. Fix en ≥1.38.1. |
| CVE-2026-48784 | `symfony/routing` | Media | Override | Normalización de dot-segments en `UrlGenerator`. Crono usa rutas estáticas (sin generación de URLs con segmentos `../` controlados por el usuario). Fix en ≥8.0.13. |

> **Disciplina.** Un override no es "ignorar para siempre": es "aceptar el riesgo,
> documentado, hasta remediar". Todos estos tienen fix disponible y entran en el plan
> de abajo. La fecha de reporte de los tres es 2026-05-26 — fueron detectados por este
> pipeline en su primer uso (evidencia de que el control funciona).

---

## Plan de remediación (Symfony 7.4 → 8.0)

Las tres CVEs se resuelven con el mismo movimiento, pero implican un **major bump** de
Symfony (7.4 → 8.0), arrastrado por `laravel/framework ^13.8`. Por eso se trata como un
**PR separado y revisado**, no se mezcla con la introducción del pipeline (separar
"detectar" de "remediar").

1. **Rama dedicada:** `chore/bump-symfony-8`.
2. **Actualizar:** `composer update "symfony/*" --with-all-dependencies`.
   - `symfony/http-foundation` 7.4.x → 8.0.13
   - `symfony/polyfill-intl-idn` 1.37 → 1.38.1
   - `symfony/routing` 7.4.x → 8.0.13
3. **Validar:** `php artisan test` dentro de Docker (los 69 deben seguir verdes).
4. **Revisar breaking changes** del major de Symfony 8 que toquen routing/http-foundation.
5. **Verificar `composer audit --no-dev`** sin advisories.
6. **Quitar los tres `--ignore`** de `.github/workflows/ci.yml` y esta sección de overrides.
7. Mergear vía el mismo pipeline (que ahora exige audit limpio).

---

## Gestión de secretos en el pipeline

Coherente con la convención del paraguas (`CLAUDE.md` raíz): **los secretos viven
server-side, nunca en el repo ni en el cliente.**

- Las credenciales de deploy son **GitHub Secrets**, referenciadas como `${{ secrets.X }}`.
  El YAML no contiene ninguna IP, usuario ni llave en claro.
- La llave privada SSH nunca toca el repositorio; se carga una sola vez como secret.
- En el servidor, el `.env` se genera con `openssl rand` (`provision.sh`) y queda en
  `chmod 600`. Nunca se versiona.

### Secrets requeridos por el job `deploy`

| Secret | Ejemplo / qué es | Dónde sale |
|--------|------------------|------------|
| `DEPLOY_HOST` | IP o host del server | infra del hub |
| `DEPLOY_PORT` | Puerto SSH (no estándar) | infra del hub |
| `DEPLOY_USER` | Usuario SSH | infra del hub |
| `DEPLOY_SSH_KEY` | Contenido **completo** de la llave privada (`id_ed25519_*`) | tu máquina admin |
| `DEPLOY_PATH` | Ruta del repo clonado en el server (ej. `~/apps/crono`) | infra del hub |

> Los valores concretos están en la documentación interna de infraestructura, fuera de
> este repositorio. **No se pegan en el chat ni en archivos versionados.**

---

## Activar el deploy automático

Hoy el job `deploy` corre **solo por disparo manual** (`workflow_dispatch`), como red de
seguridad mientras se validan los Secrets. Para activar deploy automático en cada push a
`main` exitoso, en [`.github/workflows/ci.yml`](.github/workflows/ci.yml) cambiar:

```yaml
# de:
    if: github.event_name == 'workflow_dispatch'
# a:
    if: github.ref == 'refs/heads/main'
```

---

## Bloqueo de merge (branch protection)

El YAML define los checks; el **bloqueo** se configura en GitHub (no se puede en el YAML):

**Settings → Branches → Add branch protection rule** sobre `main`:
- ✅ *Require a pull request before merging*
- ✅ *Require status checks to pass before merging* → marcar `Lint + Tests` y `SCA (audit de dependencias)`
- ✅ *Require branches to be up to date before merging*

---

## Próximas iteraciones (roadmap de seguridad)

Lo que este pipeline **todavía no** cubre, en orden de prioridad:

1. **SAST** — análisis estático: PHPStan (nivel mid) o Psalm como job adicional.
2. **Lint de JS** — hoy el frontend es Alpine vía CDN + Blade (superficie mínima), por eso
   no hay ESLint: meterlo ahora sería ruido sin valor. Se suma cuando crezca el JS propio.
3. **Dependabot** — actualizaciones automáticas de dependencias a nivel de cuenta.
4. **Trivy** — escaneo de las imágenes Docker (no solo las dependencias de la app).
5. **Gestión de secretos centralizada** — el setup actual (GitHub Secrets + `age` +
   Vaultwarden) cubre el caso; HashiCorp Vault sería el siguiente nivel.
