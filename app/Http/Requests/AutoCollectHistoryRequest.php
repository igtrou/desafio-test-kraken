<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para listagem de historico de execucoes do auto-collect.
 */
class AutoCollectHistoryRequest extends FormRequest
{
    /**
     * Indica se a requisicao pode ser processada.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define regras de validacao para limite de historico retornado.
     */
    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
