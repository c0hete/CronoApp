# CLAUDE.md — Crono

> Contexto y reglas para Claude Code.
> **Producto:** Crono — SaaS de control de gestión de asistencia para PYMEs por turnos.
> **Modelo:** código central, una instancia dedicada por cliente, configurable.
> **Cliente piloto:** Fugo Sushi (restaurant). Es el tenant #1, NO es el producto.
> Detalle técnico completo en `docs/implementacion.md`.

---

## Qué es Crono (en una línea)

SaaS de control de gestión de asistencia para PYMEs con trabajadores por turno (locales, talleres, bodegas, gastronomía). Marca entrada/salida en tablet, calcula atrasos y los traduce a costo de horas no trabajadas. **NO es un registro oficial de jornada.**

---

## Modelo de despliegue (clave del producto)

- **Una base de código central** (este repo) → se despliega como **instancia dedicada por cliente**.
- Cada cliente corre en **su propio servidor + su propia base de datos**. Aislamiento físico total.
- El producto es idéntico entre instancias; lo que cambia es la **configuración** (`.env` + tabla `configuraciones`).
- **NUNCA hardcodear nada específico de un cliente** (ni "Fugo Sushi", ni sus sueldos, ni su horario). Todo lo específico de cliente vive en configuración o seeds, jamás en el código.
- Se mantiene `empresa_id` en las tablas aunque cada instancia tenga un cliente: permite (a) meter varios clientes chicos en una instancia si se quiere, y (b) coherencia de esquema entre instancias.

---

## Reglas innegociables

### Legal / encuadre
- **NUNCA** uses "registro de jornada", "libro de asistencia", "descuento por atraso" ni "certificado por la Dirección del Trabajo" en código, comentarios, UI, variables, tablas ni docs. Activan regímenes legales que Crono evita por diseño.
- El cálculo de dinero es **"costo de horas no trabajadas"** / **"indicador de gestión"**, jamás "descuento".
- Las fotos son **evidencia visual de presencia**, NO reconocimiento facial ni biometría. No implementar ni nombrar reconocimiento facial.

### Datos
- **Los registros de marcaje NUNCA se borran automáticamente.** Solo las fotos se purgan por retención. El registro permanece aunque pierda la imagen.
- Ante disco lleno: **avisar, no borrar.** Monitoreo proactivo (proyección de llenado), nunca reactivo-destructivo.
- Fotos **fuera del directorio público**, servidas solo con autorización. Sin URLs adivinables.

### Arquitectura
- El **trabajador NO es usuario autenticado** (no va en `users`). Es entidad propia (`trabajadores`) con `user_id` NULLABLE como enchufe para login futuro (derechos ARCO, Ley 21.719). Roles Spatie solo para dueño y administrador.
- Todo valor diferenciador entre clientes = **configuración**, no código.

### Branding (white-label)
- Crono es **agnóstico de marca**. **NUNCA hardcodear "Crono" en la UI de cliente** (tablet ni panel). El nombre visible sale de `marca_nombre` (ej. "Fugo Sushi"). "Crono" es el producto, invisible para el cliente final.
- Color: el dueño elige UN color primario; la paleta se **deriva automáticamente** (variables CSS). No exponer selección de cada color → no se puede romper contraste/legibilidad.
- Logo: subida validada (PNG/SVG, tamaño máx), guardado fuera de public, fallback a texto.
- El branding lo edita el **dueño** en autoservicio desde `/panel`, con los límites de seguridad anteriores.

### Docker / entorno
- Mismo `docker-compose.yml` en local y producción (paridad). Desarrollar en Docker local, no con PHP/MySQL sueltos.
- **Local = Windows, producción = Ubuntu.** Docker es lo que cierra esa brecha: lo que corre en el contenedor es idéntico en ambos. Por eso es innegociable desarrollar dentro de Docker y no contra PHP/MySQL del host Windows (rompería la paridad con el server Ubuntu).
- Scripts de despliegue (`deploy/provision.sh`) son **bash para Ubuntu**, no PowerShell. Se ejecutan en el server, no en tu Windows.
- **Datos y secretos NUNCA en la imagen.** Solo código + entorno. DB, `.env`, fotos y logos van en volúmenes/variables.

---

## Stack

- **Backend:** Laravel 11, PHP 8.3
- **Frontend:** PWA (Blade + Alpine.js + Tailwind). Vistas separadas por ruta/rol.
- **DB:** MySQL 8.0 (una por instancia)
- **Infra:** VPS Contabo + Nginx, una instancia por cliente. **SO del server: Ubuntu** (LTS).
- **Despliegue:** Docker + GitHub Actions (plantilla replicable por cliente). Producción corre en **Ubuntu**; el desarrollo local es Windows. La paridad real la da Docker (mismo `docker-compose.yml`), no el SO del host.
- **Offline:** IndexedDB en tablet, sync unidireccional con idempotencia por UUID
- **Imágenes:** Intervention Image

---

## Vistas (mismo dominio por instancia, separadas por ruta)

| Ruta | Vista | Acceso |
|------|-------|--------|
| `/marcar` | Kiosko de marcaje (tablet) | Sin login. Solo ID + cámara. Cero datos del dueño. |
| `/login` → `/panel` | Panel del dueño | Autenticado (Spatie). Dashboard, reportes, notificaciones. |
| `/admin` | Config técnica | Rol admin. Retención, monitoreo, purgas. Delegable al dueño. |

Separación por ruta + ausencia de sesión: las notificaciones del dueño NUNCA aparecen en la tablet.

---

## Cálculo (fórmula central)

```
valor_hora = sueldo / horas_semanales        # base SEMANAL
minutos_atraso = max(0, hora_marcaje - (hora_pactada + tolerancia_min))
costo = (minutos_atraso / 60) * valor_hora
```

- **Base:** semanal.
- **Sueldo:** configurable bruto/líquido (`base_calculo`, default bruto). Se puede ingresar uno o ambos; si solo hay uno, se usa ese y se indica cuál.
- **Corte de semana:** configurable, default lunes.
- **Presentación:** siempre "costo de horas no trabajadas".

---

## Lo que NO construir todavía

- Multi-tenancy compartido (varios clientes en una base) → solo si el negocio lo pide; la costura `empresa_id` ya lo permite.
- Login del trabajador / portal ARCO → Fase 2, dejar solo el enchufe.
- Reconocimiento facial → fuera de alcance.
- WhatsApp → solo si el cliente lo pide.
- Notificaciones diarias → descartado. Solo semanal/mensual.
- Panel de administración central de instancias (gestión de clientes) → Fase 3, cuando haya varios.

---

## Fases

- **Fase 1 (MVP, con Fugo de piloto):** enrolamiento + marcaje entrada/salida con foto + cálculo + dashboard semanal/mensual. Plantilla de despliegue Docker.
- **Fase 2:** notificaciones (push PWA → Telegram), reportes por mail, login trabajador (ARCO).
- **Fase 3:** panel central de aprovisionamiento, onboarding de clientes nuevos, evaluar Resolución 38 si algún cliente busca uso legal.
