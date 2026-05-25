<?php

namespace App\Http\Requests;

use App\Rules\RutChileno;
use App\Support\Rut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edición de los datos de identidad de un trabajador (no toca contratos — el
 * histórico de contratos se maneja aparte: cerrar y crear, nunca editar el vigente).
 * Normaliza el RUT igual que el enrolamiento; el unique ignora al propio registro.
 */
class EditarTrabajadorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $numero = (string) $this->input('numero_id', '');

        $this->merge([
            'numero_id' => $this->input('tipo_id') === 'rut'
                ? Rut::normalizar($numero)
                : strtoupper(trim($numero)),
        ]);
    }

    public function rules(): array
    {
        $empresaId = (int) config('crono.empresa_id', 1);
        $trabajadorId = $this->route('trabajador')->id;

        return [
            'nombre'    => ['required', 'string', 'max:255'],
            'tipo_id'   => ['required', Rule::in(['rut', 'pasaporte'])],
            'numero_id' => [
                'required', 'string', 'max:30',
                Rule::unique('trabajadores', 'numero_id')
                    ->ignore($trabajadorId)
                    ->where(fn ($q) => $q->where('empresa_id', $empresaId)
                                          ->where('tipo_id', $this->input('tipo_id'))),
                ...($this->input('tipo_id') === 'rut' ? [new RutChileno()] : []),
            ],
            'activo'    => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['numero_id' => 'número de identificación'];
    }
}
