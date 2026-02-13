<?php

namespace App\Infrastructure\Auth;

use App\Domain\Auth\IssuedAuthToken;
use App\Domain\Auth\UserAccount;
use App\Domain\Contracts\UserRepositoryPort;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use RuntimeException;

/**
 * Encapsula operacoes de persistencia de usuarios usadas pelos servicos de autenticacao.
 */
class UserRepository implements UserRepositoryPort
{
    /**
     * Busca usuario por e-mail.
     */
    public function findByEmail(string $email): ?UserAccount
    {
        $user = User::query()->where('email', $email)->first();

        return $user !== null ? $this->toUserAccount($user) : null;
    }

    /**
     * Busca usuario por id para fluxos de auditoria.
     */
    public function findById(int $userId): ?UserAccount
    {
        $user = User::query()->find($userId);

        return $user !== null ? $this->toUserAccount($user) : null;
    }

    /**
     * Cria um novo usuario na base.
     */
    public function create(string $name, string $email, string $hashedPassword): UserAccount
    {
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
        ]);

        return $this->toUserAccount($user);
    }

    /**
     * Emite token pessoal para o usuario informado.
     */
    public function issueToken(int $userId, string $deviceName): IssuedAuthToken
    {
        $user = User::query()->find($userId);

        if ($user === null) {
            throw new RuntimeException("User [{$userId}] not found while issuing token.");
        }

        $token = $user->createToken($deviceName);

        return new IssuedAuthToken(
            token: $token->plainTextToken,
            tokenId: (int) $token->accessToken->id,
        );
    }

    /**
     * Revoga um token de acesso pessoal a partir do identificador informado.
     */
    public function revokeTokenById(?int $tokenId): void
    {
        if ($tokenId === null) {
            return;
        }

        PersonalAccessToken::query()
            ->whereKey($tokenId)
            ->delete();
    }

    /**
     * Converte model Eloquent para contrato tipado de usuario.
     */
    private function toUserAccount(User $user): UserAccount
    {
        return new UserAccount(
            id: (int) $user->id,
            name: (string) $user->name,
            email: (string) $user->email,
            passwordHash: (string) $user->password,
            isAdmin: (bool) $user->is_admin,
        );
    }
}
