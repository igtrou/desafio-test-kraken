<?php

namespace App\Http\Requests;

use App\Domain\MarketData\AssetType;
use App\Domain\Quotations\QuotationStatus;
use App\Http\Requests\Concerns\NormalizesRequestInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Request para exclusao em lote do historico de cotacoes.
 */
class DeleteQuotationBatchRequest extends FormRequest
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
     * Define regras de validacao para filtros e confirmacao de exclusao.
     */
    public function rules(): array
    {
        $configuredProviders = array_keys(config('market-data.providers', []));

        return [
            'confirm' => ['required', 'accepted'],
            'delete_all' => ['nullable', 'boolean'],
            'symbol' => $this->symbolRules('nullable'),
            'type' => ['nullable', Rule::in(AssetType::values())],
            'source' => ['nullable', Rule::in($configuredProviders)],
            'status' => ['nullable', Rule::in(QuotationStatus::values())],
            'include_invalid' => ['nullable', 'boolean'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    /**
     * Adiciona validacao condicional para evitar exclusao total acidental.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validationContext): void {
            $hasRestrictiveFilter = $this->filled('symbol')
                || $this->filled('type')
                || $this->filled('source')
                || $this->filled('status')
                || $this->filled('date_from')
                || $this->filled('date_to')
                || (bool) $this->input('include_invalid', false);

            $deleteAll = (bool) $this->input('delete_all', false);

            if (! $hasRestrictiveFilter && ! $deleteAll) {
                $validationContext->errors()->add(
                    'delete_all',
                    'Set delete_all=true to remove the complete quotation history.'
                );
            }
        });
    }

    /**
     * Normaliza simbolo e flags booleanas antes da validacao.
     */
    protected function prepareForValidation(): void
    {
        $this->normalizeOptionalSymbol();
        $this->normalizeBooleanInputs(['include_invalid', 'delete_all']);
    }
}
