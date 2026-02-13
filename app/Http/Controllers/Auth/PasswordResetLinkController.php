<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SendPasswordResetLinkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendPasswordResetLinkRequest;
use Illuminate\Http\JsonResponse;

/**
 * Inicia o fluxo de recuperacao enviando link de reset por e-mail.
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Processa solicitacao de envio de link de redefinicao.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(
        SendPasswordResetLinkRequest $request,
        SendPasswordResetLinkAction $sendPasswordResetLink
    ): JsonResponse
    {
        $status = $sendPasswordResetLink($request->validated());

        return response()->json(['status' => $status]);
    }
}
