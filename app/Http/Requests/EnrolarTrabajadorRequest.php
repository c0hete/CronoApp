<?php

namespace App\Http\Requests;

use App\Rules\RutChileno;
use App\Support\Rut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Enrolamiento: crea Trabajador + su primer Contrato vigente.
 * Reglas de negocio (sección 5):
 *  - tipo_id rut/pasaporte; si es rut, validar dígito verificador.
 *  - numero_id único por empresa (+ tipo).
 *  - al menos uno de sueldo_bruto / sueldo_liquido.
 */
class EnrolarTrabajadorRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Protegido por middleware de rol en la ruta; acá basta estar autenticado.
        return $this->user() !== null;
    }

    /**
     * Normaliza el numero_id ANTES de validar/guardar:
     *  - RUT → canónico (sin puntos/guion, K mayúscula): "25.768.863-1" → "257688631".
     *  - Pasaporte → solo trim + mayúsculas (sin estándar de formato).
     * Así el unique y la búsqueda del kiosko comparan contra el mismo valor.
     */
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

        return [
            'nombre'    => ['required', 'string', 'max:255'],
            'tipo_id'   => ['required', Rule::in(['rut', 'pasaporte'])],
            'numero_id' => [
                'required', 'string', 'max:30',
                // único por (empresa, tipo, numero) — coincide con el índice de la tabla
                Rule::unique('trabajadores', 'numero_id')
                    ->where(fn ($q) => $q->where('empresa_id', $empresaId)
                                          ->where('tipo_id', $this->input('tipo_id'))),
                // dígito verificador solo si es RUT
                ...($this->input('tipo_id') === 'rut' ? [new RutChileno()] : []),
            ],

            // --- contrato inicial ---
            'sueldo_bruto'         => ['nullable', 'numeric', 'min:0'],
            'sueldo_liquido'       => ['nullable', 'numeric', 'min:0'],
            'horas_semanales'      => ['required', 'numeric', 'min:1', 'max:168'],
            'hora_entrada_pactada' => ['required', 'date_format:H:i'],
            'tolerancia_min'       => ['required', 'integer', 'min:0', 'max:120'],
            'vigente_desde'        => ['required', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            // Regla de negocio: al menos un sueldo (bruto o líquido).
            if (blank($this->input('sueldo_bruto')) && blank($this->input('sueldo_liquido'))) {
                $v->errors()->add('sueldo_bruto', 'Debe ingresar al menos un sueldo (bruto o líquido).');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'numero_id'            => 'número de identificación',
            'hora_entrada_pactada' => 'hora de entrada',
            'horas_semanales'      => 'horas semanales',
        ];
    }
}
