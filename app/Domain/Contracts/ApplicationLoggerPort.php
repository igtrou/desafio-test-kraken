<?php

namespace App\Domain\Contracts;

interface ApplicationLoggerPort
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function info(string $message, array $context = []): void;

    /**
     * @param  array<string, mixed>  $context
     */
    public function warning(string $message, array $context = []): void;
}
