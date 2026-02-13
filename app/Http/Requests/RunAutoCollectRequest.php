<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesRequestInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request para execucao manual do auto-collect no dashboard.
 */
class RunAutoCollectRequest extends FormRequest
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
     * Define regras de validacao para parametros de execucao do auto-collect.
     */
    public function rules(): array
    {
        $providers = array_keys(config('market-data.providers', []));
        $symbolRules = $this->symbolRules();

        return [
            'symbols' => ['nullable', 'array'],
            'symbols.*' => $symbolRules,
            'provider' => ['nullable', Rule::in($providers)],
            'dry_run' => ['nullable', 'boolean'],
            'force_provider' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Normaliza booleans, lista de simbolos e provider antes da validacao.
     */
    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanInput('dry_run');
        $this->normalizeBooleanInput('force_provider');

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
