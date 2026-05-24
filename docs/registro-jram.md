# Registro de Crono en JRAM (convención mente-colmena)

> Cómo dejar el producto Crono registrado en tu estructura, siguiendo la convención
> que ya usas en `JRAM/apps/` (igual que iacode, bitacora).

---

## Ubicación

Crono es **producto propio**, no trabajo de cliente. Por lo tanto vive en **JRAM**
(tu espacio de productos e ideas), NO en CLIENTES. Fugo Sushi es el cliente piloto
del producto, no el producto en sí.

Ruta destino:
```
C:/CODIGO/JRAM/apps/crono/
```

---

## Pasos de registro

### 1. Crear la carpeta y mover los documentos
```bash
mkdir -p C:/CODIGO/JRAM/apps/crono/docs
# copiar CLAUDE.md a la raíz de crono/
# copiar especificacion.md e implementacion.md a crono/docs/
```

### 2. Crear el marcador .mc (convención: sin BOM ni newline final)
```bash
printf 'proyecto=JRAM/apps/crono' > C:/CODIGO/JRAM/apps/crono/.mc
```
Verificar que queden 24 bytes (igual estilo que `proyecto=JRAM/apps/iacode` = 25):
```bash
wc -c < C:/CODIGO/JRAM/apps/crono/.mc
```

### 3. NO crear el INDEX.md a mano
El hook Stop de mente-colmena siembra el `.mente-colmena/INDEX.md` automáticamente
al destilar la primera conversación anclada a este `.mc`, y entonces aparece en el
`MAPA.md` global. (Mismo comportamiento que el resto de tus celdas.)

### 4. Limpiar el experimento previo en CLIENTES (opcional)
La carpeta `CLIENTES/fugo-sushi/` se creó cuando esto era trabajo de cliente.
Ahora que es producto, puedes:
- Eliminar `CLIENTES/fugo-sushi/` y su `.mc`, o
- Dejarla vacía como referencia de que Fugo es cliente piloto de Crono.
Si la eliminas, quita también la línea de memoria `clientes-fugo-sushi.md` y su
índice en MEMORY.md para mantener la mente-colmena limpia.

### 5. Registrar en memoria (celda nueva)
Crear `C:\Users\JoseA\.claude\projects\c--CODIGO\memory\jram-apps-crono.md`:
```
---
name: jram-apps-crono
description: Crono es un producto SaaS propio de control de gestión de asistencia para PYMEs por turnos; Fugo Sushi es su cliente piloto.
metadata:
  type: project
---

Crono (JRAM/apps/crono): SaaS de control de gestión de asistencia para PYMEs por
turnos (gastronomía, talleres, bodegas). Modelo: código central, instancia dedicada
por cliente, configurable. NO es registro oficial de jornada (control de gestión).

- Cliente piloto: Fugo Sushi (restaurant).
- Stack: Laravel 11 + PHP 8.3 + MySQL 8 + PWA (Alpine/Tailwind) + Docker.
- Dominio: cronoapp.cl (disponible, por inscribir).
- Marco título seguridad: Ley 21.719 + ISO 27001/27701 + ISO 25010.
- Documentos: CLAUDE.md + docs/especificacion.md + docs/implementacion.md.
```
Y agregar su línea índice en `MEMORY.md`.

---

## Después del registro: arrancar Claude Code

Abrir Claude Code en `C:/CODIGO/JRAM/apps/crono/` (detecta el CLAUDE.md solo).
Arrancar por el **paso 1** del orden de implementación (no pedir todo de una vez):

> "Instala Laravel 11 con la plantilla Docker (docker-compose + .env.example),
> Spatie e Intervention. Sigue docs/implementacion.md sección 3 y 12 paso 1."

Revisar, y avanzar paso a paso (2, 3, ...). Soltarle los 10 pasos juntos es donde
un agente se desvía.
