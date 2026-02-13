<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para envio do link de recuperacao de senha.
 */
class SendPasswordResetLinkRequest extends FormRequest
{
    /**
     * Indica se a requisicao pode ser processada.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define regras de validacao para solicitacao de reset.
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
