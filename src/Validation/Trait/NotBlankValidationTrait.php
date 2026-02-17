<?php

declare(strict_types=1);

namespace Semitexa\Core\Validation\Trait;

/**
 * Validation: string must not be blank (after trim).
 * Use in Payload::validate(): $this->validateNotBlank('fieldName', $this->fieldName, $errors);
 *
 */
trait NotBlankValidationTrait
{
    /**
     * @param array<string, list<string>> $errors
     */
    protected function validateNotBlank(string $fieldName, string $value, array &$errors, string $message = 'Must not be blank.'): void
    {
        if (trim($value) === '') {
            $errors[$fieldName] = $errors[$fieldName] ?? [];
            $errors[$fieldName][] = $message;
        }
    }
}
