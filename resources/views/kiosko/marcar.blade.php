@extends('layouts.kiosko')

@section('content')
<div x-data="kiosko()" x-init="init()" style="width:100%; max-width:520px; text-align:center;">

    {{-- Indicador de conexión + pendientes por sincronizar (esquina superior) --}}
    <div style="position:fixed; top:.8rem; right:1rem; font-size:.85rem; display:flex; gap:.8rem; align-items:center; z-index:10;">
        <span x-show="pendientes > 0" x-cloak
              style="background:#3a4150; color:#ffd27a; padding:.2rem .6rem; border-radius:20px;"
              x-text="pendientes + ' por sincronizar'"></span>
        <span :style="`width:10px;height:10px;border-radius:50%;display:inline-block;background:${online ? '#3ddc84' : '#e0820c'};`"
              :title="online ? 'En línea' : 'Sin conexión — los marcajes se guardan y se envían al volver'"></span>
    </div>

    {{-- ESTADO ESPERA: logo + invitación a tocar. Cámara APAGADA (no graba mirando). --}}
    <div x-show="estado === 'espera'" @click="iniciarMarcacion()"
         style="cursor:pointer; padding:2rem 1rem; min-height:60vh; display:flex;
                flex-direction:column; align-items:center; justify-content:center; gap:1.5rem;">
        @if ($branding->logo())
            <img src="{{ route('kiosko.logo') }}" alt="{{ $branding->nombre() }}" style="max-height:96px;">
        @else
            <div style="font-size:2rem; font-weight:800;">{{ $branding->nombre() }}</div>
        @endif
        <div style="font-size:1.4rem; color:#cdd3dc;">Toca la pantalla para marcar</div>
        <div x-show="!online" x-cloak style="font-size:.95rem; color:#e0820c;">Sin conexión · se guardará y enviará al volver</div>
    </div>

    {{-- ESTADO MARCANDO: cámara + teclado + acciones --}}
    <div x-show="estado === 'marcando'" x-cloak>
        {{-- Cámara, centrada y acotada --}}
        <div style="position:relative; background:#000; border-radius:14px; overflow:hidden;
                    width:220px; height:220px; margin:0 auto 1rem;">
            <video x-ref="video" autoplay playsinline muted
                   style="width:100%; height:100%; object-fit:cover;"></video>
            <canvas x-ref="canvas" style="display:none;"></canvas>
            <div x-show="!camaraOk" x-cloak
                 style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:#9aa3b2; text-align:center; padding:1rem; font-size:.9rem;">
                <span x-text="camaraMsg"></span>
            </div>
        </div>

        <input x-ref="id" x-model="numeroId" inputmode="numeric" readonly
               placeholder="Tu RUT sin puntos ni guión"
               style="width:100%; font-size:1.6rem; text-align:center; padding:.9rem; border:0; border-radius:12px;
                      background:#1c222c; color:#fff; letter-spacing:2px; margin-bottom:.3rem;">
        <p style="color:#9aa3b2; font-size:.85rem; margin:0 0 .8rem;">
            Ej: 12345678<strong>5</strong> &nbsp;·&nbsp; si termina en K, usa la tecla <strong>K</strong>
        </p>

        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:.5rem; margin-bottom:1rem;">
            <template x-for="t in ['1','2','3','4','5','6','7','8','9','K','0','←']" :key="t">
                <button type="button" @click="tecla(t)"
                        style="font-size:1.5rem; padding:1rem 0; border:0; border-radius:12px;
                               background:#262d39; color:#fff; cursor:pointer;"
                        x-text="t"></button>
            </template>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:.7rem; margin-bottom:.8rem;">
            <button type="button" @click="marcar('entrada')" :disabled="enviando"
                    style="font-size:1.5rem; font-weight:700; padding:1.4rem 0; border:0; border-radius:14px;
                           background:var(--color-primary); color:#fff; cursor:pointer;">
                Entrada
            </button>
            <button type="button" @click="marcar('salida')" :disabled="enviando"
                    style="font-size:1.5rem; font-weight:700; padding:1.4rem 0; border:0; border-radius:14px;
                           background:#3a4150; color:#fff; cursor:pointer;">
                Salida
            </button>
        </div>
        <button type="button" @click="volverAEspera()"
                style="background:none; border:0; color:#9aa3b2; font-size:.95rem; cursor:pointer;">Cancelar</button>
    </div>

    {{-- Feedback (overlay) --}}
    <div x-show="resultado" x-cloak @click="cerrarResultado()"
         :style="`position:fixed; inset:0; display:flex; flex-direction:column; align-items:center;
                  justify-content:center; text-align:center; padding:2rem; z-index:50;
                  background:${resultado?.ok ? 'rgba(20,90,45,.97)' : 'rgba(120,30,30,.97)'};`">
        <div style="font-size:4rem;" x-text="resultado?.ok ? '✓' : '✕'"></div>
        <div style="font-size:1.8rem; font-weight:700; margin-top:.5rem;" x-text="resultado?.titulo"></div>
        <div style="font-size:1.1rem; margin-top:.4rem; opacity:.9;" x-text="resultado?.detalle"></div>
        <div style="margin-top:1.5rem; opacity:.7; font-size:.95rem;">Toca para continuar</div>
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
<style>[x-cloak]{display:none!important;}</style>
@endsection
