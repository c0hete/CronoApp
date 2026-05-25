<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Edición de branding por el dueño (Paso 8). Límites de seguridad:
 *  - color: UN HEX válido (la paleta se deriva; el dueño no elige cada tono).
 *  - logo: PNG o SVG, máx ~1MB. Se guarda fuera de public.
 */
class BrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'marca_nombre'         => ['required', 'string', 'max:60'],
            'marca_color_primario' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            // PNG o SVG, hasta 1024 KB. mimetypes cubre svg (image/svg+xml).
            'logo'                 => ['nullable', 'file', 'max:1024', 'mimetypes:image/png,image/svg+xml'],
        ];
    }

    public function messages(): array
    {
        return [
            'marca_color_primario.regex' => 'El color debe ser un HEX válido (ej: #2E75B6).',
            'logo.max'                   => 'El logo no puede superar 1 MB.',
            'logo.mimetypes'             => 'El logo debe ser PNG o SVG.',
        ];
    }
}
