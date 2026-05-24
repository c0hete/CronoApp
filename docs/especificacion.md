# Crono — Especificación de Producto
### Documento de Especificación — v2.0

> **Producto:** Crono — SaaS de control de gestión de asistencia para PYMEs por turnos.
> **Modelo:** código central, una instancia dedicada por cliente, configurable.
> **Cliente piloto:** Fugo Sushi (restaurant).
> **Naturaleza:** Herramienta de control de gestión interno. **NO es registro oficial de jornada.**

---

## 1. Propósito y alcance

### 1.1 Qué resuelve
Las PYMEs con trabajadores por turno (gastronomía, locales, talleres, bodegas) no tienen visibilidad sobre atrasos ni sobre lo que esos atrasos representan en costo. Crono da ese control: marca asistencia, calcula atrasos y los traduce a costo de horas no trabajadas para apoyar decisiones de gestión.

### 1.2 Qué entrega
- Enrolamiento de trabajadores (identidad + configuración de contrato).
- Marcaje en tablet (entrada/salida) con foto de evidencia.
- Cálculo de atraso por marcaje y su costo asociado.
- Reportes semanales y mensuales.
- Panel y notificaciones para el dueño (PWA en su teléfono).

### 1.3 Qué NO es
- **No es** registro oficial de jornada de la Dirección del Trabajo.
- **No** calcula descuentos a aplicar al trabajador.
- **No** hace reconocimiento facial (solo foto-evidencia de presencia).

### 1.4 Mercado objetivo
PYME general por turnos. Fugo Sushi (gastronomía) es el piloto; el producto sirve a cualquier rubro con trabajadores por horario.

---

## 2. Modelo de negocio y despliegue

### 2.1 Instancia por cliente
- Una base de código central (el producto Crono).
- Cada cliente corre en su propia instancia: servidor + base de datos propios.
- Aislamiento físico total entre clientes.
- La diferenciación entre clientes es **configuración**, nunca código.

### 2.2 Por qué este modelo
- **Aislamiento de datos:** lo más limpio bajo Ley 21.719; una brecha en un cliente no afecta a otros.
- **Juega a las fortalezas del equipo:** despliegue tipo Contabo + Docker + GitHub Actions ya conocido.
- **Validación antes de escalar:** Fugo valida el producto real antes de invertir en plataforma multi-cliente.
- **Costura `empresa_id` mantenida:** permite consolidar clientes chicos en una instancia si el negocio lo pide, sin rehacer esquema.

---

## 3. Naturaleza y encuadre legal

### 3.1 Mapa legal

**Leyes que CUMPLIMOS activamente**

| Ley | Cómo |
|-----|------|
| **Ley 21.719** (Protección de Datos) | Compliance-ready: finalidad declarada, retención justificada, control de acceso, cifrado, ARCO vía login futuro, audit log. Aislamiento físico por instancia. Vigencia: 1 dic 2026. |
| **Ley 21.663** (Ciberseguridad) | Referencia de buenas prácticas: respaldos, gestión de incidentes, resiliencia. |

**Leyes que ADAPTAMOS por encuadre de finalidad**

| Ley | Posición |
|-----|----------|
| **Resolución 38 Exenta** (Dirección del Trabajo) | Fuera de su alcance: Crono es control de gestión, NO registro de jornada. No requiere certificación DT. |
| **Código del Trabajo** (descuentos) | El sistema calcula "costo de horas no trabajadas", NUNCA "descuento". El descuento es decisión del empleador, fuera del sistema. |

**Riesgos que VIGILAMOS**

| Disparador | Consecuencia |
|-----------|--------------|
| Un cliente usa Crono como registro de jornada real | Cruza a Resolución 38 → requiere certificación DT para esa instancia. |
| Agregar reconocimiento facial | Sube a dato sensible Ley 21.719 → exige DPIA. |
| Login del trabajador | No es riesgo: es el mecanismo de cumplimiento ARCO. |
| Pasar a multi-tenant compartido | Sube exposición Ley 21.719 (encargado de datos de varias empresas en una base). El modelo instancia-por-cliente lo evita. |

### 3.2 Lenguaje (qué decir / qué no decir)

**Se DICE:** "control de gestión de asistencia", "costo de horas no trabajadas", "apoyo a la toma de decisiones", "registro interno con fines de gestión".

**NO se dice:** "registro oficial de jornada", "libro de asistencia", "descuento por atraso", "certificado por la Dirección del Trabajo", "reconocimiento facial".

### 3.3 Documentación de cumplimiento (por instancia)
Aviso de privacidad, consentimiento informado del trabajador, Registro de Actividades de Tratamiento (RAT), política de retención y purga, declaración de finalidad ("NO es registro de jornada").

---

## 4. Arquitectura

### 4.1 Stack
- Backend: Laravel 11, PHP 8.3.
- Frontend: PWA (Blade + Alpine.js + Tailwind).
- DB: MySQL 8.0 (una por instancia).
- Infra: VPS Contabo + Nginx, instancia por cliente. Docker + GitHub Actions.

### 4.2 Vistas (por ruta/rol)
| Ruta | Vista | Acceso |
|------|-------|--------|
| `/marcar` | Kiosko (tablet) | Sin login. ID + cámara. |
| `/login` → `/panel` | Panel del dueño | Autenticado. Dashboard, reportes. |
| `/admin` | Config técnica | Rol admin, delegable al dueño. |

### 4.3 Roles
- **Trabajador:** entidad de datos, no autenticado. `user_id` nullable como enchufe ARCO.
- **Dueño:** usuario Spatie. Dashboard, reportes, config de negocio.
- **Administrador:** usuario Spatie. Config técnica.

---

## 5. Modelo de datos
Ver `implementacion.md` sección 5 para el esquema completo. Tablas: `empresas`, `trabajadores`, `contratos`, `marcajes`, `configuraciones`. Todas con `empresa_id`. Contratos con histórico (`vigente_hasta`). Marcajes con UUID de idempotencia y doble timestamp (dispositivo/servidor).

---

## 6. Cálculo
```
valor_hora = sueldo / horas_semanales        # base semanal
minutos_atraso = max(0, hora_marcaje - (hora_pactada + tolerancia))
costo = (minutos_atraso / 60) * valor_hora
```
Sueldo bruto/líquido configurable (default bruto), maneja el caso de un solo sueldo ingresado. Corte de semana configurable (default lunes). Presentación: "costo de horas no trabajadas".

---

## 7. Offline
Cola en IndexedDB, sync unidireccional, idempotencia por UUID. Defensa de hora: doble timestamp + flag `reloj_sospechoso`. Detalle en `implementacion.md` sección 7.

---

## 8. Almacenamiento y retención
- **Fotos:** evidencia desechable. Retención configurable (default 60 días), purga automática y selectiva. Fuera de public, acceso autorizado.
- **Registros:** dato de valor. NUNCA se borran automáticamente.
- **Disco:** monitoreo proactivo (proyección de llenado), avisa pero no borra.

---

## 9. Notificaciones
Push PWA (principal) → Telegram (secundario) → email (reportes). WhatsApp solo si se pide. Sin notificaciones diarias.

---

## 10. Marco del proyecto de título (seguridad)
**Ley 21.719 + ISO 27001 + ISO 27701**, con ISO 25010 para calidad. Contexto: APDP + ANCI + Ley 21.663. El modelo instancia-por-cliente refuerza el caso de aislamiento de datos.

---

## 11. Fases
- **Fase 1 (MVP):** producto completo desplegado en la instancia de Fugo Sushi. Plantilla Docker.
- **Fase 2:** notificaciones, login trabajador (ARCO), CI/CD de actualizaciones a instancias.
- **Fase 3:** panel central de aprovisionamiento/onboarding, evaluar Resolución 38 por cliente que lo requiera.

---

## 12. Decisiones cerradas
- [x] Producto: **Crono** (marca y código de proyecto). Dominio: **cronoapp.cl** (disponible, por inscribir).
- [x] Mercado: PYME general por turnos.
- [x] Modelo: instancia dedicada por cliente, configurable.
- [x] Cálculo: base semanal; sueldo bruto/líquido configurable; corte default lunes.
- [x] Retención fotos: configurable, default 60 días.
- [x] Trabajador: entidad sin login (enchufe ARCO).

---

*Documento vivo. Última actualización: v2.0.*
