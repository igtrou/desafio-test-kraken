<?php

namespace App\Infrastructure\Auth;

use App\Domain\Contracts\WebSessionStatePort;
use Illuminate\Contracts\Session\Session;

/**
 * Encapsulates session state mutations required by authentication use cases.
 */
class WebSessionState implements WebSessionStatePort
{
    public function regenerate(): void
    {
        $this->session()->regenerate();
    }

    public function invalidate(): void
    {
        $this->session()->invalidate();
    }

    public function regenerateToken(): void
    {
        $this->session()->regenerateToken();
    }

    private function session(): Session
    {
        return request()->session();
    }
}
