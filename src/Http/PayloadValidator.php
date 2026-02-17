<?php

declare(strict_types=1);

namespace Semitexa\Core\Http;

use Semitexa\Core\Contract\ValidatablePayload;
use Semitexa\Core\Request;

/**
 * Validates a hydrated Payload DTO by calling its validate() method.
 * All Payload DTOs must implement ValidatablePayload.
 */
class PayloadValidator
{
    public static function validate(object $dto, Request $httpRequest): PayloadValidationResult
    {
        if (!$dto instanceof ValidatablePayload) {
            throw new \InvalidArgumentException(
                'Payload DTO must implement ' . ValidatablePayload::class . ', got ' . get_class($dto)
            );
        }

        return $dto->validate();
    }
}
