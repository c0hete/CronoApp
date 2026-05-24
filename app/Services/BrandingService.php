<?php

namespace App\Services;

use App\Models\Configuracion;

/**
 * Branding white-label. Crono es agnóstico de marca: la UI muestra el nombre del
 * negocio del cliente (marca_nombre), NUNCA "Crono".
 *
 * Versión mínima del Paso 3 (lectura). El theming por color y la edición desde
 * el panel se completan en el Paso 8.
 */
class BrandingService
{
    /**
     * Nombre del negocio para mostrar en UI. Fallback genérico si no se configuró
     * todavía — jamás "Crono" (eso es el producto, invisible para el cliente).
     */
    public function nombre(): string
    {
        $nombre = Configuracion::valor('marca_nombre', '');

        return $nombre !== '' ? $nombre : 'Asistencia';
    }

    public function colorPrimario(): string
    {
        return Configuracion::valor('marca_color_primario', '#2E75B6');
    }

    public function logo(): ?string
    {
        $logo = Configuracion::valor('marca_logo', '');

        return $logo !== '' ? $logo : null;
    }
}
