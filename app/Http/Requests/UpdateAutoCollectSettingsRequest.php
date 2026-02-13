<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesRequestInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request para atualizacao das configuracoes de auto-collect.
 */
class UpdateAutoCollectSettingsRequest extends FormRequest
{
    use NormalizesRequestInput;

    /**
     * Indica se a requisicao pode ser processada.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define regras de validacao para configuracao de agendamento e simbolos.
     */
    public function rules(): array
    {
        $providers = array_keys(config('market-data.providers', []));
        $symbolRules = $this->symbolRules();

        return [
            'enabled' => ['required', 'boolean'],
            'interval_minutes' => ['required', 'integer', 'between:1,59'],
            'symbols' => ['required', 'array', 'min:1'],
            'symbols.*' => $symbolRules,
            'provider' => ['nullable', Rule::in($providers)],
        ];
    }

    /**
     * Normaliza boolean, simbolos e provider antes da validacao.
     */
    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanInput('enabled');

        $symbols = $this->input('symbols');

        if (is_string($symbols)) {
            $symbols = array_values(array_filter(array_map(
                static fn (string $value): string => trim($value),
                explode(',', $symbols)
            )));
        }

        if (is_array($symbols)) {
            $symbols = array_values(array_filter(array_map(
                fn (mixed $value): string => $this->normalizeSymbolValue($value),
                $symbols
            )));

            $this->merge([
                'symbols' => $symbols,
            ]);
        }

        if ($this->has('provider') && $this->input('provider') === '') {
            $this->merge([
                'provider' => null,
            ]);
        }
    }
}
