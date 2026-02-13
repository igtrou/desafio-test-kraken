<?php

namespace App\Domain\Exceptions;

use RuntimeException;

/**
 * Raised when a use case fails due to input/business validation constraints.
 */
class DomainValidationException extends RuntimeException
{
    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public function __construct(
        public readonly array $errors,
        string $message = 'Validation failed.'
    ) {
        parent::__construct($message);
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public static function withErrors(array $errors, string $message = 'Validation failed.'): self
    {
        return new self($errors, $message);
    }
}
