<?php

namespace App\Http\Controllers\Api;

use App\Actions\Auth\IssueAuthTokenAction;
use App\Actions\Auth\RevokeAuthTokenAction;
use App\Data\AuthTokenRevocationInputData;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\BuildsAuditContext;
use App\Http\Requests\AuthTokenStoreRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Controla emissao e revogacao de tokens de autenticacao da API.
 */
class AuthTokenController extends Controller
{
    use BuildsAuditContext;

    /**
     * Emite um novo token usando credenciais validadas e registra auditoria.
     *
     * @throws ValidationException
     */
    public function store(
        AuthTokenStoreRequest $request,
        IssueAuthTokenAction $issueAuthToken
    ): JsonResponse {
        $response = $issueAuthToken(
            $request->validated(),
            $this->buildAuditContext($request)
        );

        return response()->json($response->toArray(), $response->statusCode);
    }

    /**
     * Revoga o token atual do usuario autenticado e retorna status padronizado.
     */
    public function destroy(
        Request $request,
        RevokeAuthTokenAction $revokeAuthToken
    ): JsonResponse {
        $user = $request->user();
        $currentToken = $user?->currentAccessToken();
        $response = $revokeAuthToken(
            new AuthTokenRevocationInputData(
                userId: is_numeric($user?->id) ? (int) $user?->id : null,
                tokenId: $currentToken?->id !== null ? (int) $currentToken->id : null,
                tokenName: $currentToken?->name !== null ? (string) $currentToken->name : null,
            ),
            $this->buildAuditContext($request)
        );

        return response()->json($response->toArray(), $response->statusCode);
    }
}
