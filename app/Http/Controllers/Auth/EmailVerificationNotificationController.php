<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SendEmailVerificationNotificationAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Reenvia notificacao de verificacao de e-mail para o usuario autenticado.
 */
class EmailVerificationNotificationController extends Controller
{
    /**
     * Dispara novo envio de verificacao quando aplicavel.
     */
    public function store(
        Request $request,
        SendEmailVerificationNotificationAction $sendEmailVerificationNotification
    ): JsonResponse|RedirectResponse
    {
        $sent = $sendEmailVerificationNotification($request->user());

        if (! $sent) {
            return redirect()->intended('/dashboard/quotations');
        }

        return response()->json(['status' => 'verification-link-sent']);
    }
}
