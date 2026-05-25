@extends('layouts.kiosko')

@section('content')
<div x-data="kiosko()" x-init="initCamara()" style="width:100%; max-width:520px;">

    {{-- Cámara (evidencia visual de presencia, NO biometría) — preview acotado y centrado --}}
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

    {{-- Identificación: RUT sin puntos ni guión (con K si tu RUT termina en K) --}}
    <input x-ref="id" x-model="numeroId" inputmode="numeric" readonly
           placeholder="Tu RUT sin puntos ni guión"
           style="width:100%; font-size:1.6rem; text-align:center; padding:.9rem; border:0; border-radius:12px;
                  background:#1c222c; color:#fff; letter-spacing:2px; margin-bottom:.3rem;">
    <p style="text-align:center; color:#9aa3b2; font-size:.85rem; margin:0 0 .8rem;">
        Ej: 12345678<strong>5</strong> &nbsp;·&nbsp; si termina en K, usa la tecla <strong>K</strong>
    </p>

    {{-- Teclado numérico (uso a distancia, dedos) --}}
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:.5rem; margin-bottom:1rem;">
        <template x-for="t in ['1','2','3','4','5','6','7','8','9','K','0','←']" :key="t">
            <button type="button" @click="tecla(t)"
                    style="font-size:1.5rem; padding:1rem 0; border:0; border-radius:12px;
                           background:#262d39; color:#fff; cursor:pointer;"
                    x-text="t"></button>
        </template>
    </div>

    {{-- Acciones: entrada / salida (botones dominantes, imposibles de errar) --}}
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:.7rem;">
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

    {{-- Feedback (overlay a pantalla completa) --}}
    <div x-show="resultado" x-cloak @click="resultado=null"
         :style="`position:fixed; inset:0; display:flex; flex-direction:column; align-items:center;
                  justify-content:center; text-align:center; padding:2rem; z-index:50;
                  background:${resultado?.ok ? 'rgba(20,90,45,.97)' : 'rgba(120,30,30,.97)'};`">
        <div style="font-size:4rem;" x-text="resultado?.ok ? '✓' : '✕'"></div>
        <div style="font-size:1.8rem; font-weight:700; margin-top:.5rem;" x-text="resultado?.titulo"></div>
        <div style="font-size:1.1rem; margin-top:.4rem; opacity:.9;" x-text="resultado?.detalle"></div>
        <div style="margin-top:1.5rem; opacity:.7; font-size:.95rem;">Toca para continuar</div>
    </div>
</div>

<script>
function kiosko() {
    return {
        numeroId: '',
        camaraOk: false,
        camaraMsg: 'Solicitando cámara…',
        enviando: false,
        resultado: null,
        stream: null,

        async initCamara() {
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
            return c.toDataURL('image/jpeg', 0.85); // el servidor la degrada a 640/q70
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
            try {
                const resp = await fetch('{{ route('api.marcar') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({
                        uuid: this.uuid(),
                        numero_id: this.numeroId,
                        tipo: tipo,
                        ts_dispositivo: new Date().toISOString(),
                        foto: this.capturarFoto(),
                    }),
                });
                const data = await resp.json();
                if (resp.ok && data.ok) {
                    this.resultado = {
                        ok: true,
                        titulo: '¡Listo, ' + (data.trabajador || '') + '!',
                        detalle: (tipo === 'entrada' ? 'Entrada' : 'Salida') + ' registrada.',
                    };
                    this.numeroId = '';
                } else {
                    this.resultado = { ok: false, titulo: 'No se pudo marcar', detalle: data.mensaje || 'Revisa tu identificación.' };
                }
            } catch (e) {
                // Sin conexión: en el Paso 6 esto se encola offline (IndexedDB). Por ahora, avisar.
                this.resultado = { ok: false, titulo: 'Sin conexión', detalle: 'Inténtalo de nuevo en un momento.' };
            } finally {
                this.enviando = false;
            }
        },
    };
}
</script>
<style>[x-cloak]{display:none!important;}</style>
@endsection
