<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

/**
 * Request para cadastro de novos usuarios na aplicacao web.
 */
class RegisterUserRequest extends FormRequest
{
    /**
     * Indica se a requisicao pode ser processada.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define regras de validacao para o fluxo de cadastro.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }
}
