<?php

namespace App\Domain\Contracts;

use App\Domain\Audit\AuditEntityReference;

interface AuditLoggerPort
{
    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $properties
     */
    public function log(
        string $description,
        ?AuditEntityReference $subject = null,
        ?AuditEntityReference $causer = null,
        array $context = [],
        array $properties = [],
        string $event = 'custom',
        string $logName = 'audit'
    ): void;
}
