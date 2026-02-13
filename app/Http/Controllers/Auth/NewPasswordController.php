<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ResetPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;

/**
 * Recebe e aplica redefinicao de senha via token.
 */
class NewPasswordController extends Controller
{
    /**
     * Processa a alteracao de senha com dados validados.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(
        ResetPasswordRequest $request,
        ResetPasswordAction $resetPassword
    ): JsonResponse
    {
        $status = $resetPassword($request->validated());

        return response()->json(['status' => $status]);
    }
}
