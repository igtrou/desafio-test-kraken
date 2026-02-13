<?php

namespace App\Http\Requests;

use App\Domain\MarketData\AssetType;
use App\Http\Requests\Concerns\NormalizesRequestInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request para consulta/coleta pontual de cotacao por simbolo.
 */
class QuotationRequest extends FormRequest
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
     * Define regras de validacao para simbolo, provider e tipo de ativo.
     */
    public function rules(): array
    {
        $configuredProviders = array_keys(config('market-data.providers', []));

        return [
            'symbol' => $this->symbolRules('required'),
            'provider' => ['nullable', Rule::in($configuredProviders)],
            'type' => ['nullable', Rule::in(AssetType::values())],
        ];
    }

    /**
     * Normaliza o simbolo vindo de rota ou payload antes da validacao.
     */
    protected function prepareForValidation(): void
    {
        $submittedSymbol = (string) ($this->route('symbol') ?? $this->input('symbol', ''));

        $this->normalizeRequiredSymbol('symbol', $submittedSymbol);
    }
}
