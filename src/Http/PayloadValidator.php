<?php

declare(strict_types=1);

namespace Semitexa\Core\Http;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use Semitexa\Core\Request;

/**
 * Validates a hydrated Payload DTO: strict type check (reject invalid input) + optional constraint attributes.
 * Handlers then work only with clean, validated data.
 */
class PayloadValidator
{
    public static function validate(object $dto, Request $httpRequest): PayloadValidationResult
    {
        $rawData = RequestDtoHydrator::getRawData($httpRequest, $dto);
        $errors = [];
        $reflection = new ReflectionClass($dto);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();
            if (!array_key_exists($name, $rawData)) {
                continue;
            }
            $rawValue = $rawData[$name];
            $type = $property->getType();

            if ($type !== null && !self::rawValueValidForType($rawValue, $type)) {
                $typeName = $type instanceof ReflectionNamedType ? $type->getName() : (string) $type;
                $errors[$name][] = self::messageForType($typeName, $rawValue);
            }

            if (isset($errors[$name])) {
                continue;
            }

            $currentValue = $property->isInitialized($dto) ? $property->getValue($dto) : null;
            foreach (self::checkConstraintAttributes($property, $currentValue) as $message) {
                $errors[$name][] = $message;
            }
        }

        return new PayloadValidationResult(empty($errors), $errors);
    }

    private static function rawValueValidForType(mixed $rawValue, \ReflectionType $type): bool
    {
        if ($rawValue === null || $rawValue === '') {
            if ($type instanceof ReflectionNamedType && $type->allowsNull()) {
                return true;
            }
            if ($type instanceof ReflectionUnionType) {
                foreach ($type->getTypes() as $t) {
                    if ($t->getName() === 'null') {
                        return true;
                    }
                }
            }
            return false;
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $t) {
                if ($t->getName() !== 'null' && self::rawValueValidForNamedType($rawValue, $t->getName())) {
                    return true;
                }
            }
            return false;
        }

        if ($type instanceof ReflectionNamedType) {
            return self::rawValueValidForNamedType($rawValue, $type->getName());
        }

        return true;
    }

    private static function rawValueValidForNamedType(mixed $rawValue, string $typeName): bool
    {
        $s = is_string($rawValue) ? trim($rawValue) : $rawValue;
        return match ($typeName) {
            'int' => is_int($rawValue) || (is_string($rawValue) && $s !== '' && (string) (int) $s === $s),
            'float' => is_float($rawValue) || is_int($rawValue) || (is_string($rawValue) && $s !== '' && is_numeric($s)),
            'bool' => is_bool($rawValue) || is_int($rawValue) || (is_string($rawValue) && in_array(strtolower($rawValue), ['0', '1', 'true', 'false', 'yes', 'no', 'on', 'off'], true)),
            'string' => is_string($rawValue) || is_int($rawValue) || is_float($rawValue) || is_bool($rawValue),
            'array' => is_array($rawValue),
            default => true,
        };
    }

    private static function messageForType(string $typeName, mixed $rawValue): string
    {
        $repr = is_scalar($rawValue) ? (string) $rawValue : gettype($rawValue);
        return "Expected {$typeName}, got invalid value.";
    }

    /**
     * @return list<string>
     */
    private static function checkConstraintAttributes(ReflectionProperty $property, mixed $value): array
    {
        $messages = [];

        foreach ($property->getAttributes() as $attr) {
            $name = $attr->getName();
            if ($name === 'Semitexa\Core\Request\Attribute\NotBlank') {
                if (is_string($value) && trim($value) === '') {
                    $messages[] = 'Must not be blank.';
                }
                continue;
            }
            if ($name === 'Semitexa\Core\Request\Attribute\Email') {
                if (is_string($value) && $value !== '' && !self::isEmailLike($value)) {
                    $messages[] = 'Invalid email format.';
                }
                continue;
            }
            if ($name === 'Semitexa\Core\Request\Attribute\Length') {
                try {
                    $instance = $attr->newInstance();
                    $min = $instance->min ?? null;
                    $max = $instance->max ?? null;
                    $len = is_string($value) ? strlen($value) : (is_array($value) ? count($value) : 0);
                    if ($min !== null && $len < $min) {
                        $messages[] = "Length must be at least {$min}.";
                    }
                    if ($max !== null && $len > $max) {
                        $messages[] = "Length must be at most {$max}.";
                    }
                } catch (\Throwable) {
                }
            }
        }

        return $messages;
    }

    private static function isEmailLike(string $value): bool
    {
        return (bool) preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $value);
    }
}
