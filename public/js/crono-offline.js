/**
 * Crono — cola de marcajes offline (IndexedDB + sync).
 *
 * Diseño (sección 7): la tablet solo CREA marcajes. Sin red, se guardan en IndexedDB
 * con sincronizado:false y se confirma localmente. Al volver la red, se empujan a
 * /api/marcar; la idempotencia por uuid (backend) evita duplicados en reintentos.
 * Sync UNIDIRECCIONAL: el servidor es la única fuente de verdad.
 *
 * Sin dependencias: IndexedDB nativo. Expuesto como window.KioskoCola.
 */
(function () {
    const DB_NAME = 'crono';
    const STORE = 'pendientes';
    const URL_MARCAR = '/api/marcar';

    function abrirDB() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(DB_NAME, 1);
            req.onupgradeneeded = (e) => {
                const db = e.target.result;
                if (!db.objectStoreNames.contains(STORE)) {
                    db.createObjectStore(STORE, { keyPath: 'uuid' });
                }
            };
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    }

    async function tx(modo, fn) {
        const db = await abrirDB();
        return new Promise((resolve, reject) => {
            const t = db.transaction(STORE, modo);
            const store = t.objectStore(STORE);
            const r = fn(store);
            t.oncomplete = () => resolve(r && r.result !== undefined ? r.result : undefined);
            t.onerror = () => reject(t.error);
        });
    }

    async function encolar(marcaje) {
        await tx('readwrite', (s) => s.put({ ...marcaje, sincronizado: false, intentos: 0 }));
    }

    async function pendientes() {
        const db = await abrirDB();
        return new Promise((resolve, reject) => {
            const r = db.transaction(STORE).objectStore(STORE).getAll();
            r.onsuccess = () => resolve(r.result.filter((m) => !m.sincronizado));
            r.onerror = () => reject(r.error);
        });
    }

    async function contarPendientes() {
        return (await pendientes()).length;
    }

    async function quitar(uuid) {
        await tx('readwrite', (s) => s.delete(uuid));
    }

    function csrf() {
        const el = document.querySelector('meta[name=csrf-token]');
        return el ? el.content : '';
    }

    /**
     * Envía un marcaje al servidor. Devuelve {ok, data, status} o lanza si es error de red.
     */
    async function enviar(marcaje) {
        const resp = await fetch(URL_MARCAR, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({
                uuid: marcaje.uuid,
                numero_id: marcaje.numero_id,
                tipo: marcaje.tipo,
                ts_dispositivo: marcaje.ts_dispositivo,
                foto: marcaje.foto || null,
            }),
        });
        const data = await resp.json().catch(() => ({}));
        return { ok: resp.ok && data.ok, status: resp.status, data };
    }

    /**
     * Intenta sincronizar la cola. Cada pendiente:
     *  - 2xx ok → se quita de la cola.
     *  - 422 (trabajador inválido) → se descarta (no reintentar eternamente algo inválido).
     *  - error de red / 5xx → se deja para el próximo intento.
     * Devuelve cuántos quedaron pendientes.
     */
    async function sincronizar() {
        const cola = await pendientes();
        for (const m of cola) {
            try {
                const r = await enviar(m);
                if (r.ok || r.status === 422) {
                    // ok o rechazo definitivo del servidor → no tiene sentido reintentar
                    await quitar(m.uuid);
                }
            } catch (e) {
                // sin red: dejar en cola para el próximo intento
                break;
            }
        }
        return contarPendientes();
    }

    window.KioskoCola = { encolar, pendientes, contarPendientes, quitar, enviar, sincronizar };
})();
