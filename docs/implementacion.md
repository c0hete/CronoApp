# Documentación Técnica de Implementación — Crono

> El *cómo*. El *qué* y *por qué* está en `especificacion.md`.
> Las reglas innegociables están en `../CLAUDE.md` — mandan sobre este documento.
> **Versión:** v2.0 — modelo SaaS instancia-por-cliente.

---

## 1. Decisiones cerradas

| Decisión | Valor |
|----------|-------|
| Modelo de despliegue | Código central, **instancia dedicada por cliente** (servidor + DB propios) |
| Diferenciación entre clientes | Configuración (`.env` + tabla `configuraciones`), nunca código |
| Base de cálculo | Semanal |
| Sueldo del cálculo | Bruto y/o líquido, configurable (`base_calculo`, default bruto) |
| Corte de semana | Configurable, default lunes |
| Retención de fotos | Configurable, default 60 días |
| Autenticación trabajador | No (enchufe `user_id` nullable para ARCO) |
| Autenticación tablet | ID (RUT/pasaporte) + foto-evidencia |
| Eventos | Entrada + salida (atraso solo en entrada) |
| `empresa_id` | Se mantiene en todas las tablas (coherencia + flexibilidad futura) |

---

## 2. Stack y versiones

```
PHP            8.3
Laravel        11.x
MySQL          8.0
Node           20.x
Tailwind CSS   3.x
Alpine.js      3.x
Docker         (despliegue por instancia)
```

**Paquetes Composer:**
- `spatie/laravel-permission` — roles dueño/admin
- `intervention/image` — degradado de fotos

---

## 3. Modelo de despliegue: instancia por cliente

> Esto es lo que hace a Crono un producto y no una app suelta. Es el núcleo del modelo.

### Principio
El repo es la **plantilla del producto**. Levantar un cliente = desplegar una instancia de esta plantilla con su configuración.

### Qué diferencia a una instancia de otra (TODO esto es config, no código)
- `.env`: nombre del cliente, dominio, credenciales de DB, claves de notificación (Telegram bot token, etc.).
- Seed inicial: registro en `empresas` (id=1 por instancia), configuraciones por defecto.
- Branding opcional: logo/nombre mostrado en la UI (vía config, no hardcode).

### Estructura de despliegue (Docker)
```
docker-compose.yml          # app (Laravel+Nginx) + MySQL + (cola/scheduler)
.env.example                # plantilla de variables — se copia y rellena por cliente
deploy/
  └── provision.sh          # script idempotente: levanta una instancia nueva
```

### Docker en LOCAL (desarrollo) — paridad con producción
> El mismo `docker-compose.yml` corre en local y en server. Esto garantiza paridad:
> lo que se prueba en local es exactamente lo que corre en cada cliente (mismo PHP 8.3,
> mismo MySQL 8, mismas extensiones). Elimina los bugs de "en mi máquina funcionaba".

- En local: `docker compose up` con un `.env` de desarrollo (datos de prueba).
- No instalar PHP/MySQL sueltos en la máquina; todo vive en los contenedores.
- En Windows: Docker Desktop o Docker engine sobre WSL2 (suele ir más fluido).
- **Datos y secretos NUNCA van en la imagen.** La imagen lleva solo código + entorno.
  La DB, el `.env`, las fotos y los logos viven en **volúmenes y variables**, fuera del contenedor.
  (Hornear secretos o datos en la imagen es vulnerabilidad y rompe el modelo por-instancia.)

### Construcción de la imagen: build-en-server vs registry
- **Fase 1 (Fugo):** build-en-server. El server clona el repo y hace `docker compose up --build`.
  Cero infraestructura extra; valida rápido.
- **Fase 2+ (varios clientes):** publicar imagen a un registry (GitHub Container Registry, gratis,
  encaja con GitHub Actions) y los servers solo hacen `pull`. Más rápido y consistente, permite
  actualizar todas las instancias con un push. Migrar cuando el flujo esté probado.

### Proceso de alta de un cliente nuevo (repetible)
> **SO del server: Ubuntu (LTS).** Todos los comandos de esta lista corren en el VPS Ubuntu
> (vía SSH), no en la máquina de desarrollo Windows. `provision.sh` es bash de Ubuntu.
> Prerrequisito del server: Docker Engine + plugin `docker compose` instalados en Ubuntu
> (no Docker Desktop — eso es solo para el desarrollo local en Windows).

1. Aprovisionar VPS (Contabo, **Ubuntu LTS**) + DNS del subdominio/dominio del cliente. Instalar Docker Engine en el server.
2. Clonar repo, copiar `.env.example` → `.env`, rellenar config del cliente.
3. `docker compose up -d` + `php artisan migrate --seed`.
4. Crear usuario dueño (comando Artisan dedicado: `php artisan crono:crear-dueno`).
5. Configurar tablet en modo kiosko apuntando a `/marcar`.
6. El dueño ajusta su branding (logo, color, nombre) desde `/panel` en autoservicio.

> **CI/CD (GitHub Actions):** un push a `main` puede desplegar actualizaciones a las instancias registradas. La lista de instancias vive fuera del repo (inventario de despliegue). Esto es Fase 2-3; en Fase 1 basta desplegar Fugo a mano con `provision.sh`.

---

## 4. Estructura del proyecto (dentro del Laravel)

```
app/
├── Models/         Empresa, Trabajador, Contrato, Marcaje, Configuracion
├── Traits/         BelongsToEmpresa (scope de tenant — se mantiene)
├── Services/       CalculoAtrasoService, FotoService
├── Console/Commands/  MonitorDisco, PurgarFotos, CrearDueno
└── Http/
    ├── Controllers/Kiosko/   MarcajeController
    ├── Controllers/Panel/    DashboardController, ReporteController
    └── Controllers/Admin/    ConfiguracionController
```

---

## 5. Esquema de migraciones

> Idéntico al diseño tenant-aware. `empresa_id` se mantiene aunque cada instancia tenga un cliente.

### `empresas`
```php
$table->id();
$table->string('nombre');
$table->string('rut_empresa')->nullable();
$table->boolean('activa')->default(true);
$table->timestamps();
// Seed por instancia: el cliente de esa instancia (id=1)
```

### `trabajadores`
```php
$table->id();
$table->foreignId('empresa_id')->constrained();
$table->foreignId('user_id')->nullable()->constrained(); // enchufe ARCO
$table->string('nombre');
$table->enum('tipo_id', ['rut', 'pasaporte']);
$table->string('numero_id');
$table->string('foto_enrolamiento')->nullable();
$table->boolean('activo')->default(true);
$table->timestamps();
$table->unique(['empresa_id', 'tipo_id', 'numero_id']);
```

### `contratos`
```php
$table->id();
$table->foreignId('empresa_id')->constrained();
$table->foreignId('trabajador_id')->constrained();
$table->decimal('sueldo_bruto', 12, 2)->nullable();    // al menos uno obligatorio (validar)
$table->decimal('sueldo_liquido', 12, 2)->nullable();
$table->decimal('horas_semanales', 5, 2);
$table->time('hora_entrada_pactada');
$table->unsignedSmallInteger('tolerancia_min')->default(0);
$table->date('vigente_desde');
$table->date('vigente_hasta')->nullable();             // contrato vigente = hasta NULL
$table->timestamps();
```
> **Histórico:** nunca editar un contrato vigente para cambiar sueldo/horario. Cerrarlo (`vigente_hasta`) y crear uno nuevo, para no corromper reportes pasados.

### `marcajes`
```php
$table->id();
$table->uuid('uuid')->unique();         // generado en tablet — idempotencia
$table->foreignId('empresa_id')->constrained();
$table->foreignId('trabajador_id')->constrained();
$table->enum('tipo', ['entrada', 'salida']);
$table->timestamp('ts_dispositivo');    // hora de la tablet
$table->timestamp('ts_servidor')->nullable(); // hora al sincronizar
$table->string('foto_evidencia')->nullable();
$table->integer('minutos_atraso')->default(0); // solo entrada
$table->decimal('costo_atraso', 12, 2)->default(0);
$table->boolean('reloj_sospechoso')->default(false);
$table->timestamps();
$table->index(['empresa_id', 'trabajador_id', 'ts_dispositivo']);
```

### `configuraciones`
```php
$table->id();
$table->foreignId('empresa_id')->constrained();
$table->string('clave');
$table->text('valor');
$table->timestamps();
$table->unique(['empresa_id', 'clave']);
// Seed por instancia:
//   base_calculo         = 'bruto'
//   inicio_semana        = 'lunes'
//   retencion_fotos_dias = '60'
//   foto_rotacion        = '0'
//   foto_ancho_px        = '640'
//   foto_calidad         = '70'
//   umbral_disco_alerta  = '90'
//   reloj_tolerancia_min = '5'   (diferencia ts_dispositivo vs ts_servidor para flag)
//   -- branding (white-label, editable por el dueño) --
//   marca_nombre         = ''    (nombre del negocio mostrado en UI; ej. "Fugo Sushi")
//   marca_logo           = ''    (ruta del logo subido; vacío = fallback a texto)
//   marca_color_primario = '#2E75B6' (HEX único; la paleta se deriva en frontend)
```

---

## 6. Lógica de cálculo (`CalculoAtrasoService`)

```
Al registrar marcaje 'entrada':
1. Contrato vigente del trabajador (vigente_hasta IS NULL).
2. Sueldo según base_calculo:
   - base=bruto y bruto existe → bruto.
   - base=liquido y liquido existe → líquido.
   - elegido NULL pero otro existe → usar disponible + registrar cuál.
   - ninguno → minutos_atraso igual, costo=0 + flag "sin sueldo".
3. valor_hora = sueldo / horas_semanales
4. hora_limite = hora_entrada_pactada + tolerancia_min
5. minutos_atraso = max(0, ts_dispositivo.time - hora_limite)
6. costo_atraso = (minutos_atraso / 60) * valor_hora
7. Persistir en el marcaje.

Marcaje 'salida': no calcula atraso. Se guarda para reportes futuros de horas reales.
```

---

## 7. Flujo de marcaje y offline

### Online
```
Tablet → POST /api/marcar { uuid, numero_id, tipo, ts_dispositivo, foto(base64) }
Servidor:
  - uuid ya existe? → 200 sin duplicar (idempotencia).
  - Resolver trabajador por numero_id (+ empresa_id).
  - ts_servidor = now(); si |ts_servidor - ts_dispositivo| > reloj_tolerancia_min → reloj_sospechoso.
  - Procesar foto (degradar + rotar) → guardar fuera de public/.
  - Calcular atraso (si entrada).
  - Responder confirmación.
```

### Offline
```
Tablet sin red:
  - uuid local, guardar en IndexedDB { sincronizado:false }, confirmación local.
Al recuperar red:
  - Empujar pendientes a /api/marcar; marcar sincronizado:true al 200.
  - Idempotencia por uuid evita duplicados en reintentos.
```
Sync **unidireccional**: la tablet solo crea, el servidor es la única fuente de verdad. Sin conflictos.

---

## 8. Manejo de fotos (`FotoService`)

- Al recibir: redimensionar a `foto_ancho_px` (640), calidad `foto_calidad` (70), aplicar `foto_rotacion`.
- Guardar en `storage/app/fotos/{empresa_id}/{año}/{mes}/` (fuera de public).
- Servir solo vía controlador autorizado (dueño/admin). Nunca URL directa.
- Resultado: ~30-50 KB por foto.

---

## 8b. Branding white-label (`BrandingService`)

> Crono es agnóstico: cada instancia se ve como el negocio del cliente, no como "Crono".
> El dueño edita su branding en autoservicio, con límites de seguridad.

### Qué es editable (por el DUEÑO, desde `/panel`)
- **`marca_nombre`**: nombre del negocio mostrado en UI (ej. "Fugo Sushi"). Aparece en tablet y panel.
- **`marca_logo`**: logo subido. Validación: PNG/SVG, máx ~1MB, dimensiones razonables. Guardado fuera de public, servido con autorización (igual que fotos). Fallback a `marca_nombre` en texto si no hay logo.
- **`marca_color_primario`**: UN color HEX. La paleta completa (hover, claros, oscuros, contraste de texto) se **deriva automáticamente** en frontend. El dueño NO elige cada color → imposible romper legibilidad/contraste.

### Cómo se aplica el color (theming)
- El color primario se inyecta como **variables CSS** (`--color-primary` y derivadas calculadas).
- Tailwind consume esas variables. Las variantes (claro/oscuro/hover) se calculan, no se eligen.
- Validar que el HEX entrante sea válido antes de aplicar; fallback al default si no.

### Reglas
- **NADA de marca "Crono" hardcodeado en UI de cliente.** Todo nombre visible sale de `marca_nombre`. "Crono" es el producto (interno/repo), invisible para el cliente final.
- El branding es por instancia (cada cliente el suyo; nunca ve el de otro — el modelo instancia-por-cliente lo garantiza).
- Logo y color se editan en panel del dueño; cambios aplican de inmediato (leídos de config, cacheables).

### Distinción de nombres (importante para no confundir)
| Nombre | Qué es | Dónde se ve |
|--------|--------|-------------|
| **Crono** | El producto/plataforma | Repo, docs, código. NO en UI de cliente. |
| **`marca_nombre`** (ej. Fugo Sushi) | El negocio del cliente | Tablet, panel, lo que ve el trabajador y el dueño. |

---

## 9. Comandos programados (Scheduler)

- **`fotos:purgar`** — borra fotos con antigüedad > `retencion_fotos_dias`. **Solo fotos, nunca marcajes.** Diario.
- **`disco:monitor`** — uso de disco + proyección de llenado. Si supera `umbral_disco_alerta` → notifica. **No borra.** Diario.
- **`crono:crear-dueno`** — alta del usuario dueño en una instancia nueva (aprovisionamiento).

---

## 10. Reportes

- **Semanal:** agrupado según `inicio_semana` (lunes). Minutos de atraso + costo por trabajador y total.
- **Mensual:** agregado al mes.
- Presentación: "Costo de horas no trabajadas". Nunca "descuento".

---

## 11. Seguridad (ISO 27001 / 27701 / Ley 21.719)

- **Ventaja del modelo:** aislamiento físico por cliente (servidor + DB propios) es lo más limpio bajo Ley 21.719. Una brecha en una instancia no toca a otra.
- Fotos fuera de public, acceso autorizado, cifrado en reposo (deseable).
- Audit log de accesos a fotos.
- Roles mínimos (Spatie): dueño, admin. Trabajador no autenticado.
- Validación estricta en `/api/marcar` (entrada pública).
- `reloj_sospechoso` como señal de integridad offline.
- Por instancia: política de privacidad + RAT (Registro de Actividades de Tratamiento).

---

## 12. Orden de implementación

1. Laravel 11 + Spatie + Intervention. Plantilla Docker (`docker-compose`, `.env.example`). **Correr en local** para paridad con producción.
2. Migraciones + modelos + trait `BelongsToEmpresa`. Seeds de empresa + configuraciones (incluye claves de branding).
3. Enrolamiento (CRUD trabajador + contrato; validar RUT/pasaporte y al menos un sueldo).
4. `/api/marcar` + `CalculoAtrasoService` + `FotoService`.
5. Vista kiosko `/marcar` (Alpine: input ID + cámara + feedback). UI agnóstica: nombre/logo desde config.
6. Capa offline (IndexedDB + service worker + cola de sync).
7. Panel dueño: dashboard + reportes semanal/mensual.
8. **Branding** (`BrandingService`): edición de logo/color/nombre en panel dueño + theming por variables CSS.
9. Comandos Scheduler (purga fotos, monitor disco) + `crono:crear-dueno`.
10. Admin: vista de configuraciones.
11. PWA (manifest + instalable) + `provision.sh` para alta de instancia.

> **MVP (Fase 1)** = pasos 1-10 aplicados a la instancia de Fugo Sushi.
> Aprovisionamiento automatizado y panel central = Fase 2-3.
