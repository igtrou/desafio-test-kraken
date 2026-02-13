<?php

namespace App\Domain\Contracts;

interface QuotationCollectExecutionLoggerPort
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function started(array $context): void;

    /**
     * @param  array<string, mixed>  $context
     */
    public function finished(array $context): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function latest(int $limit = 20): array;
}
