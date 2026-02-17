<?php

declare(strict_types=1);

namespace Semitexa\Core\Contract;

use Semitexa\Core\Http\PayloadValidationResult;

/**
 * Every Payload DTO (Request, Session, Cookie, etc.) must implement this interface.
 * Validation runs after hydration; framework calls validate() and uses the result (e.g. 422 on failure).
 */
interface ValidatablePayload
{
    public function validate(): PayloadValidationResult;
}
