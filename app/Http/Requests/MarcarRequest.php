<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Payload de marcaje desde la tablet:
 *   { uuid, numero_id, tipo, ts_dispositivo, foto? }
 * El kiosko no tiene login: la ruta es pública (sin auth), por eso validar fuerte acá.
 */
class MarcarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // kiosko sin sesión; la seguridad es validación + idempotencia.
    }

    public function rules(): array
    {
        return [
            'uuid'           => ['required', 'uuid'],
            'numero_id'      => ['required', 'string', 'max:30'],
            'tipo'           => ['required', Rule::in(['entrada', 'salida'])],
            'ts_dispositivo' => ['required', 'date'],
            'foto'           => ['nullable', 'string'], // base64 / data-uri
        ];
    }
}
