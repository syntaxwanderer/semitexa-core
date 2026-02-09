<?php

declare(strict_types=1);

namespace Semitexa\Core\Http;

/**
 * Result of payload validation. If valid, handler receives clean DTO; if invalid, return 422 with errors.
 */
final class PayloadValidationResult
{
    /** @param array<string, list<string>> $errors field name => list of messages */
    public function __construct(
        private readonly bool $valid,
        private readonly array $errors = []
    ) {
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    /** @return array<string, list<string>> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
