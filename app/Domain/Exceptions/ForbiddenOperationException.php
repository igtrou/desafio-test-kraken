<?php

namespace App\Domain\Exceptions;

use RuntimeException;

/**
 * Raised when an operation is not allowed in the current business context.
 */
class ForbiddenOperationException extends RuntimeException
{
}
