<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

/**
 * Request para redefinicao de senha via token.
 */
class ResetPasswordRequest extends FormRequest
{
    /**
     * Indica se a requisicao pode ser processada.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define regras de validacao para confirmacao de nova senha.
     */
    public function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }
}
