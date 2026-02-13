<?php

namespace App\Domain\Contracts;

use App\Domain\Auth\UserIdentity;

interface AuthLifecycleEventsPort
{
    public function dispatchRegistered(UserIdentity $user): void;

    public function dispatchPasswordReset(UserIdentity $user): void;

    public function dispatchVerified(UserIdentity $user): void;
}
