<?php

namespace App\Services\Auth;

use App\Domain\Audit\AuditEntityReference;
use App\Domain\Contracts\AuditLoggerPort;
use App\Domain\Contracts\PasswordHasherPort;
use App\Domain\Contracts\UserRepositoryPort;
use App\Domain\Exceptions\DomainValidationException;

/**
 * Orquestra emissao e revogacao de tokens API.
 */
class AuthTokenService
{
    /**
     * Injeta repositorio de usuarios para operacoes de token.
     */
    /**
     * Executa a rotina principal do metodo __construct.
     */
    public function __construct(
        private readonly UserRepositoryPort $userRepository,
        private readonly PasswordHasherPort $passwordHasher,
        private readonly AuditLoggerPort $auditLogger,
    ) {}

    /**
     * Valida credenciais e emite um novo token pessoal para o dispositivo informado.
     *
     * @param  string  $email  E-mail usado para autenticacao.
     * @param  string  $password  Senha em texto plano informada pelo cliente.
     * @param  string|null  $deviceName  Nome logico do cliente/dispositivo.
     * @return array{
     *     user_id: int,
     *     token: string,
     *     token_id: int,
     *     device_name: string
     * }
     *
     * @throws DomainValidationException
     */
    /**
     * Executa a rotina principal do metodo issue.
     */
    public function issue(string $email, string $password, ?string $deviceName = null): array
    {
        $user = $this->userRepository->findByEmail($email);
        $storedPassword = $user?->passwordHash ?? '';

        if ($user === null || $storedPassword === '' || ! $this->passwordHasher->check($password, $storedPassword)) {
            throw DomainValidationException::withErrors([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $resolvedDeviceName = $deviceName ?? 'api-client';
        $issuedToken = $this->userRepository->issueToken($user->id, $resolvedDeviceName);

        return [
            'user_id' => $user->id,
            'token' => $issuedToken->token,
            'token_id' => $issuedToken->tokenId,
            'device_name' => $resolvedDeviceName,
        ];
    }

    /**
     * Revoga o token atual, retornando metadados para resposta e auditoria.
     *
     * @return array{token_id: int|null, token_name: string|null}
     */
    /**
     * Executa a rotina principal do metodo revokeCurrentToken.
     */
    public function revokeCurrentToken(?int $tokenId, ?string $tokenName): array
    {
        $revocation = [
            'token_id' => $tokenId,
            'token_name' => $tokenName,
        ];

        $this->userRepository->revokeTokenById($tokenId);

        return $revocation;
    }

    /**
     * Registra auditoria da emissao de token API com metadados operacionais.
     *
     * @param  array{
     *     user_id: int,
     *     token: string,
     *     token_id: int,
     *     device_name: string
     * }  $issueResult
     * @param  array<string, mixed>  $auditContext
     */
    /**
     * Executa a rotina principal do metodo logIssuedToken.
     */
    public function logIssuedToken(array $issueResult, array $auditContext = []): void
    {
        $userReference = AuditEntityReference::user($issueResult['user_id']);

        $this->auditLogger->log(
            description: 'API token created',
            subject: $userReference,
            causer: $userReference,
            context: $auditContext,
            properties: [
                'user_id' => $issueResult['user_id'],
                'token_id' => $issueResult['token_id'],
                'token_name' => $issueResult['device_name'],
            ],
            event: 'token.created',
        );
    }

    /**
     * Registra auditoria da revogacao de token API.
     *
     * @param  array{token_id: int|null, token_name: string|null}  $revocation
     * @param  array<string, mixed>  $auditContext
     */
    /**
     * Executa a rotina principal do metodo logRevokedToken.
     */
    public function logRevokedToken(
        ?int $userId,
        array $revocation,
        array $auditContext = []
    ): void {
        $userReference = $userId !== null
            ? AuditEntityReference::user($userId)
            : null;

        $this->auditLogger->log(
            description: 'API token revoked',
            subject: $userReference,
            causer: $userReference,
            context: $auditContext,
            properties: [
                'user_id' => $userId,
                'token_id' => $revocation['token_id'],
                'token_name' => $revocation['token_name'],
            ],
            event: 'token.revoked',
        );
    }
}
