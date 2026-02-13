<?php

namespace Tests\Unit\Services\Auth;

use App\Domain\Auth\UserIdentity;
use App\Domain\Contracts\AuthLifecycleEventsPort;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Contracts\Auth\Authenticatable;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class EmailVerificationServiceTest extends TestCase
{
    /**
     * Limpa o cenario apos a execucao do teste.
     */
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_verify_returns_false_when_mark_email_as_verified_fails(): void
    {
        $authLifecycleEvents = Mockery::mock(AuthLifecycleEventsPort::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('dispatchVerified');
        });
        $service = new EmailVerificationService($authLifecycleEvents);
        $user = new FakeVerifiableUser(
            markResult: false,
            verified: false
        );

        $result = $service->verify($user);

        $this->assertFalse($result);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_verify_returns_true_and_dispatches_event_when_mark_email_succeeds(): void
    {
        $authLifecycleEvents = Mockery::mock(AuthLifecycleEventsPort::class, function (MockInterface $mock): void {
            $mock->shouldReceive('dispatchVerified')
                ->once()
                ->withArgs(static fn (UserIdentity $user): bool => $user->id === 1);
        });
        $service = new EmailVerificationService($authLifecycleEvents);
        $user = new FakeVerifiableUser(
            markResult: true,
            verified: false
        );

        $result = $service->verify($user);

        $this->assertTrue($result);
    }
}

final class FakeVerifiableUser implements Authenticatable
{
    public function __construct(
        private readonly bool $markResult,
        private bool $verified
    ) {}

    /**
     * Verifica o estado da condicao avaliada.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->verified;
    }

    /**
     * Marca o estado do registro atual.
     */
    public function markEmailAsVerified(): bool
    {
        if (! $this->markResult) {
            return false;
        }

        if ($this->verified) {
            return false;
        }

        $this->verified = true;

        return true;
    }

    /**
     * Envia dados para o destino configurado.
     */
    public function sendEmailVerificationNotification(): void
    {
        // Nao utilizado nestes cenarios de teste.
    }

    /**
     * Retorna um valor calculado para o fluxo atual.
     */
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    /**
     * Retorna um valor calculado para o fluxo atual.
     */
    public function getAuthIdentifier(): int
    {
        return 1;
    }

    /**
     * Retorna um valor calculado para o fluxo atual.
     */
    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    /**
     * Retorna um valor calculado para o fluxo atual.
     */
    public function getAuthPassword(): string
    {
        return 'secret';
    }

    /**
     * Retorna um valor calculado para o fluxo atual.
     */
    public function getRememberToken(): ?string
    {
        return null;
    }

    /**
     * Define valores de configuracao para o fluxo atual.
     */
    public function setRememberToken($value): void
    {
        // Sem estado de remember token para este fake.
    }

    /**
     * Retorna um valor calculado para o fluxo atual.
     */
    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }
}
