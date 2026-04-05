<?php

declare(strict_types=1);

namespace Semitexa\Core\Exception;

/**
 * Base exception for all framework-level (non-domain) errors.
 */
abstract class SemitexaException extends \RuntimeException
{
    public function __construct(
        string $message,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
