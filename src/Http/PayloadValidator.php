<?php

declare(strict_types=1);

namespace Semitexa\Core\Http;

use Semitexa\Core\Contract\ValidatablePayload;
use Semitexa\Core\Request;

/**
 * Validates a hydrated Payload DTO when it implements ValidatablePayload.
 * Payloads that do not implement the interface are treated as valid (e.g. simple GET page payloads).
 */
class PayloadValidator
{
    public static function validate(object $dto, Request $httpRequest): PayloadValidationResult
    {
        if (!$dto instanceof ValidatablePayload) {
            return new PayloadValidationResult(true, []);
        }

        return $dto->validate();
    }
}
