@extends('layouts.kiosko')

@section('content')
<div x-data="kiosko()" x-init="init()" style="width:100%; max-width:520px;">

    {{-- Chip superior: estado de conexión + pendientes por sincronizar --}}
    <div class="topchip">
        <span x-show="pendientes > 0" x-cloak class="pending"
              x-text="pendientes + ' por sincronizar'"></span>
        <span class="dot" :class="online ? 'on' : 'off'"
              :title="online ? 'En línea' : 'Sin conexión — los marcajes se guardan y se envían al volver'"></span>
    </div>

    {{-- ESTADO ESPERA: marca + reloj + invitación. Cámara APAGADA. --}}
    <div x-show="estado === 'espera'" @click="iniciarMarcacion()" class="espera">
        @if ($branding->logo())
            <div class="espera__logo"><img src="{{ route('kiosko.logo') }}" alt="{{ $branding->nombre() }}"></div>
        @else
            <div class="espera__nombre">{{ $branding->nombre() }}</div>
        @endif

        <div>
            <div class="espera__reloj" x-text="reloj"></div>
            <div class="espera__fecha" x-text="fechaTexto"></div>
        </div>

        <div class="espera__cta">
            <span class="pulse"></span>
            Toca la pantalla para marcar
        </div>

        <div x-show="!online" x-cloak class="espera__offline">
            Sin conexión · se guardará y enviará al volver
        </div>
    </div>

    {{-- ESTADO MARCANDO: cámara circular + teclado + acciones --}}
    <div x-show="estado === 'marcando'" x-cloak class="marcando">
        <div class="camara">
            <video x-ref="video" autoplay playsinline muted></video>
            <canvas x-ref="canvas" style="display:none;"></canvas>
            <div x-show="!camaraOk" x-cloak class="camara__msg">
                <span x-text="camaraMsg"></span>
            </div>
        </div>

        <input x-ref="id" x-model="numeroId" inputmode="numeric" readonly
               placeholder="Tu RUT sin puntos ni guión" class="input-id">
        <p class="hint">
            Ej: 12345678<strong>5</strong> · si termina en K, usa la tecla <strong>K</strong>
        </p>

        <div class="keypad">
            <template x-for="t in ['1','2','3','4','5','6','7','8','9','K','0','←']" :key="t">
                <button type="button" @click="tecla(t)" class="key"
                        :class="{ 'back': t === '←' }" x-text="t"></button>
            </template>
        </div>

        <div class="acciones">
            <button type="button" @click="marcar('entrada')" :disabled="enviando" class="act act--primario">
                <span>→</span> Entrada
            </button>
            <button type="button" @click="marcar('salida')" :disabled="enviando" class="act act--secundario">
                <span>←</span> Salida
            </button>
        </div>
        <button type="button" @click="volverAEspera()" class="cancelar">Cancelar</button>
    </div>

    {{-- Feedback overlay (el "wow" del marcaje) --}}
    <div x-show="resultado" x-cloak @click="cerrarResultado()" class="overlay"
         :class="resultado?.ok ? 'overlay--ok' : 'overlay--err'">
        <div class="check" :class="resultado?.ok ? 'check--ok' : 'check--err'"
             x-text="resultado?.ok ? '✓' : '✕'"></div>
        <div class="resultado__titulo" x-text="resultado?.titulo"></div>
        <div class="resultado__detalle" x-text="resultado?.detalle"></div>
        <div class="resultado__continuar">Toca para continuar</div>
    </div>
</div>

<script src="{{ asset('js/crono-offline.js') }}"></script>
<script>
function kiosko() {
    return {
        estado: 'espera',          // 'espera' | 'marcando'
        numeroId: '',
        camaraOk: false,
        camaraMsg: 'Solicitando cámara…',
        enviando: false,
        resultado: null,
        stream: null,
        online: navigator.onLine,
        pendientes: 0,

        // Reloj en vivo (solo presentación; se actualiza cada segundo).
        reloj: '',
        fechaTexto: '',

        async init() {
            // estado de conexión + cola pendiente
            this.online = navigator.onLine;
            this.pendientes = await window.KioskoCola.contarPendientes();
            // al volver la red, sincronizar la cola
            window.addEventListener('online',  async () => { this.online = true;  await this.sincronizar(); });
            window.addEventListener('offline', () => { this.online = false; });
            // intento inicial por si quedaron pendientes de una sesión anterior
            if (this.online) await this.sincronizar();
            // reintento periódico (red intermitente)
            setInterval(() => { if (navigator.onLine) this.sincronizar(); }, 30000);

            // reloj en vivo (presentación)
            this.tickReloj(); setInterval(() => this.tickReloj(), 1000);
        },

        tickReloj() {
            const d = new Date();
            const hh = String(d.getHours()).padStart(2, '0');
            const mm = String(d.getMinutes()).padStart(2, '0');
            this.reloj = `${hh}:${mm}`;
            // "miércoles 27 de mayo" — en español
            try {
                this.fechaTexto = d.toLocaleDateString('es-CL', {
                    weekday: 'long', day: 'numeric', month: 'long',
                });
            } catch (_) {
                this.fechaTexto = '';
            }
        },

        async sincronizar() {
            this.pendientes = await window.KioskoCola.sincronizar();
        },

        async iniciarMarcacion() {
            this.estado = 'marcando';
            this.numeroId = '';
            await this.encenderCamara();
        },

        async encenderCamara() {
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 1280 } }, audio: false
                });
                this.$refs.video.srcObject = this.stream;
                this.camaraOk = true;
            } catch (e) {
                this.camaraOk = false;
                this.camaraMsg = 'Sin acceso a la cámara. Se puede marcar igual.';
            }
        },

        apagarCamara() {
            if (this.stream) {
                this.stream.getTracks().forEach(t => t.stop()); // la cámara DEJA de mirar
                this.stream = null;
            }
            this.camaraOk = false;
        },

        volverAEspera() {
            this.apagarCamara();
            this.numeroId = '';
            this.estado = 'espera';
        },

        tecla(t) {
            // Pequeño feedback háptico en tablets/móviles que lo soportan (no-op si no).
            if (navigator.vibrate) navigator.vibrate(10);
            if (t === '←') { this.numeroId = this.numeroId.slice(0, -1); return; }
            if (this.numeroId.length < 15) this.numeroId += t;
        },

        capturarFoto() {
            if (!this.camaraOk) return null;
            const v = this.$refs.video, c = this.$refs.canvas;
            if (!v.videoWidth) return null;
            c.width = v.videoWidth; c.height = v.videoHeight;
            c.getContext('2d').drawImage(v, 0, 0, c.width, c.height);
            return c.toDataURL('image/jpeg', 0.85);
        },

        uuid() {
            return (crypto.randomUUID && crypto.randomUUID()) ||
                'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
                    const r = Math.random() * 16 | 0;
                    return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
                });
        },

        async marcar(tipo) {
            if (!this.numeroId) {
                this.resultado = { ok: false, titulo: 'Falta tu identificación', detalle: 'Ingrésala antes de marcar.' };
                return;
            }
            this.enviando = true;

            const marcaje = {
                uuid: this.uuid(),
                numero_id: this.numeroId,
                tipo: tipo,
                ts_dispositivo: new Date().toISOString(),
                foto: this.capturarFoto(),
            };
            const etiqueta = tipo === 'entrada' ? 'Entrada' : 'Salida';

            try {
                const r = await window.KioskoCola.enviar(marcaje);
                if (r.ok) {
                    this.resultado = { ok: true, titulo: '¡Listo, ' + (r.data.trabajador || '') + '!', detalle: etiqueta + ' registrada.' };
                } else if (r.status === 422) {
                    // el servidor rechazó (RUT inválido/inactivo): NO encolar, avisar
                    this.resultado = { ok: false, titulo: 'No se pudo marcar', detalle: r.data.mensaje || 'Revisa tu identificación.' };
                } else {
                    // 5xx u otro: encolar para reintentar
                    await window.KioskoCola.encolar(marcaje);
                    this.pendientes = await window.KioskoCola.contarPendientes();
                    this.resultado = { ok: true, titulo: 'Marcaje guardado', detalle: etiqueta + ' registrada · se sincronizará pronto.' };
                }
            } catch (e) {
                // sin red: encolar + confirmación local (no bloquear al trabajador)
                await window.KioskoCola.encolar(marcaje);
                this.pendientes = await window.KioskoCola.contarPendientes();
                this.resultado = { ok: true, titulo: 'Marcaje guardado sin conexión', detalle: etiqueta + ' registrada · se enviará al volver la conexión.' };
            } finally {
                this.enviando = false;
            }
        },

        cerrarResultado() {
            const fueExito = this.resultado?.ok;
            this.resultado = null;
            // tras un marcaje exitoso, apaga cámara y vuelve a espera (no queda grabando)
            if (fueExito) this.volverAEspera();
        },
    };
}
</script>
@endsection
