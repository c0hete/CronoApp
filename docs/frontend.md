# Frontend y pulido visual — Crono

> Cómo lograr que Crono se vea y se sienta "caro" sin volvernos locos.
> **Principio rector:** belleza por sistema, no por esfuerzo manual. Se definen tokens y
> componentes base UNA vez; todo lo construido encima sale bien por defecto.

---

## 1. Qué es alcanzable (y qué no)

**SÍ, alcanzable por un dev con oficio (sin presupuesto millonario):**
microinteracciones suaves, cero saltos de contenido, modo oscuro impecable, transiciones
orgánicas, 60fps en móvil, accesibilidad por teclado, carga sin spinners. Es el nivel
Linear/Vercel y se logra con criterio + Tailwind + transiciones CSS bien hechas.

**NO, y además NO se necesita:** motores de render a medida (Figma/Wasm), shaders WebGL
(Stripe), scrollytelling cinematográfico (Apple). Resuelven problemas que Crono no tiene.

> Para Crono, "caro" = simplicidad ejecutada con oficio, NO complejidad visual.

---

## 2. Presupuesto de lujo por superficie

| Superficie | Cuidado | Por qué |
|------------|---------|---------|
| **Tablet de marcaje** | Máximo | Cara del producto, uso diario, de aquí nace el "wow" del cliente. |
| **Dashboard del dueño** | Medio-alto | Lo que el dueño mira y por lo que paga. |
| **Admin / config** | Funcional y limpio | Nadie compra por la pantalla de configuración. |

> Meter esfuerzo premium en la pantalla de config es despilfarro. No hacerlo.

---

## 3. Fundación (una sola vez, antes de pulir)

- **Design tokens:** paleta derivada del `marca_color_primario`, escala tipográfica,
  espaciados, radios, sombras. Esto hace que todo se vea coherente "gratis" después.
- **Componentes base:** botón, input, card, badge, con estados claros (hover, focus visible,
  disabled, loading) y transiciones 150-200ms. Definir uno bueno de cada uno y reusar.
- Usar el **skill frontend-design** para esta fundación (evita el look genérico de IA).
- Con stack Blade + Alpine + Tailwind: robar criterios de shadcn/ui (no la librería), no reinventar.

---

## 4. La tablet (máximo mimo)

- Una sola acción dominante: botón enorme, imposible de errar.
- Feedback de marcaje **satisfactorio:** check que aparece con micro-rebote, color de éxito,
  nombre del trabajador confirmado. Esto es lo que hace decir "qué buena la app".
- Estados offline visibles y tranquilizadores ("Guardado. Se sincronizará al volver la conexión").
- Tipografía grande, contraste alto, usable rápido y a distancia.
- Branding del cliente aplicado (logo + color), nunca "Crono".

---

## 5. El dashboard del dueño

- Cuenta la historia de un vistazo: cuánto atraso, cuánto cuesta, quién. El número importante, grande.
- Jerarquía visual clara; estilo Linear (limpio, denso pero legible, sin adornos que distraigan).
- Skeletons en vez de spinners. Transiciones suaves al cambiar semana/mes.

---

## 6. Línea roja (anti-locura)

En la APP, NO:
- WebGL, 3D, shaders.
- Scrollytelling, animaciones de scroll complejas.
- Librerías de animación pesadas para efectos triviales.
- Pulir pantallas antes de que su funcionalidad esté estable.

---

## 7. Orden de ejecución (CUÁNDO se hace cada cosa)

1. **Durante construcción (pasos 1-11 de implementacion.md):** UI limpia pero SIN pulir.
   Funcional primero. No invertir en estética de pantallas que aún pueden cambiar.
2. **Cuando tablet + dashboard estén estables:** UNA pasada de pulido dedicada a esas dos
   superficies, con los tokens ya definidos. Sesión enfocada, no goteo constante.
3. **Landing (cronoapp.cl):** al final, con producto funcionando y screenshots reales.
   Ver sección 8. Una landing antes del producto es vender humo y se rehace.

---

## 8. Landing de cronoapp.cl (frente aparte)

Aquí SÍ es legítimo un toque más premium (la landing vende y justifica precio), con techo:

**SÍ:** hero cuidado (titular del beneficio, no de features) con animación de entrada sutil;
hover effects orgánicos; demo visual del producto (screenshot/video del marcaje); Core Web
Vitals impecables; responsive perfecto.

**NO:** gradientes WebGL estilo Stripe, secuencias de scroll estilo Apple. Un toque, no una
superproducción.

---

## 9. Cómo dirigir a Claude Code en el pulido

- NO decir "haz que se vea bonito" → da resultados genéricos.
- SÍ: primero que defina design tokens + set de componentes base → los apruebas → recién
  entonces que los aplique pantalla por pantalla.
- Acotar siempre el alcance y la línea roja en el prompt (ver `arranque-claude-code.md`,
  sección de pulido).
