# Arranque de Claude Code — Crono

> Guía de traspaso a Claude Code. Prompts listos para copiar.
> Ir en orden. No saltar pasos.
> Entorno real: **Windows + PowerShell**, se trabaja desde `c:\CODIGO`.

---

## Paso 0 — Dejar los archivos en su lugar (✅ ya hecho)

Estructura destino (ya montada):
```
c:\CODIGO\JRAM\apps\crono\
├── .mc            (proyecto=JRAM/apps/crono — marcador mente-colmena)
├── .gitignore
├── .env / .env.example
├── CLAUDE.md
└── docs\
    ├── especificacion.md
    ├── implementacion.md
    ├── registro-jram.md
    └── arranque-claude-code.md   (este archivo)
```

El marcador `.mc` ya existe. Si hiciera falta recrearlo (PowerShell, sin BOM ni newline):
```powershell
[System.IO.File]::WriteAllText("c:\CODIGO\JRAM\apps\crono\.mc", "proyecto=JRAM/apps/crono")
```

> El `CLAUDE.md` va en la RAÍZ (no en docs): Claude Code lo lee automáticamente al abrir la carpeta.

---

## Dos entornos (no confundir)

| | Desarrollo (ahora) | Producción (despliegue, más adelante) |
|---|---|---|
| **Máquina** | Tu PC, `c:\CODIGO\...` | VPS Contabo |
| **SO** | Windows | **Ubuntu LTS** |
| **Docker** | Docker **Desktop** (WSL2) | Docker **Engine** + `docker compose` |
| **Shell** | PowerShell | bash (SSH) |
| **Scripts** | — | `deploy/provision.sh` (bash Ubuntu) |

La **paridad** entre ambos la da Docker: el mismo `docker-compose.yml` corre igual en tu
Windows local y en el server Ubuntu. Por eso se desarrolla SIEMPRE dentro de Docker, no
contra PHP/MySQL sueltos del host. Esta guía cubre el **desarrollo local**; el despliegue
en Ubuntu está en `docs/implementacion.md` sección 3 (Fase 1 = a mano con `provision.sh`).

---

## Paso 0.5 — Prerrequisito: Docker (instalar a mano antes del Paso 1)

El Paso 1 necesita Docker corriendo **en tu Windows** (esto es desarrollo local; el server
Ubuntu se configura recién en la etapa de despliegue). En esta máquina **Docker NO está
instalado todavía** (WSL2 sí). Instalar Docker Desktop para Windows antes de arrancar:

- Descarga oficial: https://www.docker.com/products/docker-desktop/
- Durante la instalación, dejar activado el backend **WSL2** (es el que ya tienes).
- Tras instalar: abrir **Docker Desktop**, aceptar términos, esperar a que el ícono quede
  "running". Puede pedir reiniciar.
- Verificar en PowerShell que quedó operativo:
  ```powershell
  docker --version
  docker info --format '{{.ServerVersion}}'   # debe devolver una versión, no error
  ```

> Hasta que `docker info` no responda con una versión, el Paso 1 fallará por entorno
> (no por código). No arrancar el Paso 1 antes.

---

## Paso 1 — Abrir Claude Code en la carpeta

En PowerShell:
```powershell
cd c:\CODIGO\JRAM\apps\crono
claude
```

---

## Paso 2 — Prompt de orientación (NO construir todavía)

Copiar y pegar:

```
Lee CLAUDE.md y docs/implementacion.md. Resúmeme en 5 líneas qué vamos a construir,
el modelo de arquitectura y las reglas innegociables, para confirmar que estamos
alineados antes de escribir código. No escribas nada todavía.
```

> Checkpoint: si el resumen confunde Crono con Fugo, se salta la regla de no-borrar-registros,
> o no menciona el modelo instancia-por-cliente → corregir AHORA, antes de que escriba código.

---

## Paso 3 — Construir por pasos (uno a la vez)

### Paso 1 de implementación
```
Empecemos por el paso 1 de la sección 12 de implementacion.md: Laravel 11 con plantilla
Docker (docker-compose + .env.example), Spatie e Intervention, corriendo en local.
Sigue las secciones 2 y 3 de implementacion.md. Cuando termines, dime cómo verificar
que levanta bien antes de seguir. No avances al paso 2.
```

Verificar que `docker compose up` levanta de verdad. Luego:

```
Funciona. Haz commit con un mensaje descriptivo y seguimos con el paso 2.
```

### Para cada paso siguiente (2 a 11), mismo patrón:
```
Sigamos con el paso N de la sección 12. Sigue las secciones relevantes de implementacion.md
y respeta las reglas de CLAUDE.md. Cuando termines, dime cómo verificarlo. No avances al paso N+1.
```

Y al verificar:
```
Verificado. Haz commit y seguimos con el paso N+1.
```

---

## Reglas de oro del traspaso

- **Nunca "construye Crono entero".** Paso a paso, cada uno revisable.
- **Commit después de cada paso verificado.** Cada paso = punto de retorno en git.
- **Si se desvía:** párale y apunta a la regla concreta. Ejemplo:
  ```
  Detente. Estás hardcodeando "Crono"/el nombre en la UI. Revisa la sección de Branding
  en CLAUDE.md: la UI es agnóstica, el nombre sale de marca_nombre. Rehazlo.
  ```
- **Sesión larga / nueva sesión:** los docs en la carpeta son el seguro. Una sesión nueva
  relee el CLAUDE.md y retoma con el mismo contexto. Si pierde hilo:
  ```
  Relee CLAUDE.md y docs/implementacion.md. ¿En qué paso de la sección 12 quedamos según el
  último commit? Continúa desde ahí.
  ```

---

## Recordatorio de alcance

El objetivo inmediato es **una instancia funcionando para Fugo Sushi** (Fase 1, pasos 1-11).
Registry, CI/CD multi-instancia y panel central son Fase 2-3: solo valen la pena con un
segundo cliente real. No construir la plataforma antes que el producto.
