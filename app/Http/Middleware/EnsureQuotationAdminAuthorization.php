<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autoriza operacoes administrativas de cotacoes por Sanctum ou role JWT validada no gateway.
 */
class EnsureQuotationAdminAuthorization
{
    /**
     * Garante permissao administrativa para operacoes de exclusao de cotacoes.
     *
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('sanctum');
        $user = Auth::guard('sanctum')->user();

        if ($user !== null) {
            $request->setUserResolver(static fn () => $user);

            if ((bool) $user->is_admin) {
                return $next($request);
            }

            throw new AuthorizationException('You are not allowed to perform this action.');
        }

        if ($this->hasTrustedGatewayModeratorRole($request)) {
            $request->attributes->set('gateway_admin_authorized', true);

            return $next($request);
        }

        if ($this->hasTrustedGatewayJwt($request)) {
            throw new AuthorizationException('You are not allowed to perform this action.');
        }

        throw new AuthenticationException('Unauthenticated.', ['sanctum']);
    }

    /**
     * Valida se o gateway autenticou JWT e propagou role de moderacao.
     */
    private function hasTrustedGatewayModeratorRole(Request $request): bool
    {
        if (! $this->hasTrustedGatewayJwt($request)) {
            return false;
        }

        $rolesHeader = (string) config('gateway.jwt_roles_header', 'X-Auth-Roles');
        $moderatorRole = strtolower((string) config('gateway.jwt_moderator_role', 'moderator'));
        $rawRoles = strtolower((string) $request->header($rolesHeader, ''));

        if ($rawRoles === '') {
            return false;
        }

        $normalizedRoles = preg_replace('/[^a-z0-9,_-]+/', ',', $rawRoles);
        $roles = array_values(array_filter(array_map('trim', explode(',', (string) $normalizedRoles))));

        return in_array($moderatorRole, $roles, true);
    }

    /**
     * Confirma se a requisicao contem marca de JWT validado e origem confiavel.
     */
    private function hasTrustedGatewayJwt(Request $request): bool
    {
        if (! (bool) config('gateway.trust_jwt_assertion', true)) {
            return false;
        }

        if ($request->attributes->get('gateway_request_verified') !== true) {
            return false;
        }

        $jwtAssertionHeader = (string) config('gateway.jwt_assertion_header', 'X-Gateway-Auth');
        $jwtAssertionValue = strtolower((string) config('gateway.jwt_assertion_value', 'jwt'));
        $gatewayAssertion = strtolower((string) $request->header($jwtAssertionHeader, ''));

        return $gatewayAssertion !== '' && hash_equals($jwtAssertionValue, $gatewayAssertion);
    }
}
