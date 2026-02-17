<?php

declare(strict_types=1);

namespace Semitexa\Core\Http;

use Semitexa\Core\Request;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;

/**
 * Hydrates Request DTO from HTTP Request using setter convention.
 *
 * For each key in raw data (JSON/POST/query + path params), calls set{CamelCase}($value)
 * if the method exists. Value is cast to the setter's parameter type before calling.
 * Path params are keyed by route param name (e.g. 'id' -> setId($value)).
 */
class RequestDtoHydrator
{
    public static function hydrate(object $dto, Request $httpRequest): object
    {
        $pathParams = self::extractPathParams($dto, $httpRequest);
        $data = self::collectData($httpRequest);
        $data = array_merge($data, $pathParams);

        $reflection = new ReflectionClass($dto);

        foreach ($data as $key => $value) {
            $setterName = self::keyToSetterName($key);
            if (!method_exists($dto, $setterName)) {
                continue;
            }

            $method = $reflection->getMethod($setterName);
            if ($method->getNumberOfRequiredParameters() !== 1) {
                continue;
            }

            $param = $method->getParameters()[0];
            $type = $param->getType();
            $typedValue = self::castValue($value, $type);
            $method->invoke($dto, $typedValue);
        }

        return $dto;
    }

    /**
     * Convert data key (snake_case or camelCase) to setter method name.
     */
    private static function keyToSetterName(string $key): string
    {
        $camel = self::snakeToCamel($key);
        return 'set' . ucfirst($camel);
    }

    private static function snakeToCamel(string $key): string
    {
        $parts = explode('_', $key);
        $first = array_shift($parts);
        if ($first === null) {
            return $key;
        }
        foreach ($parts as $i => $part) {
            $parts[$i] = ucfirst($part);
        }
        return $first . implode('', $parts);
    }

    /**
     * Extract path parameters from URL; keys are route param names (e.g. 'id').
     */
    private static function extractPathParams(object $dto, Request $httpRequest): array
    {
        $reflection = new ReflectionClass($dto);
        $requestAttrs = $reflection->getAttributes(\Semitexa\Core\Attributes\AsPayload::class);
        if (empty($requestAttrs)) {
            return [];
        }

        try {
            $requestAttr = $requestAttrs[0]->newInstance();
            $routePattern = $requestAttr->path ?? null;
        } catch (\Throwable) {
            return [];
        }

        if ($routePattern === null || $routePattern === false || !is_string($routePattern) || $routePattern === '') {
            return [];
        }

        if (strpos($routePattern, '{') === false) {
            return [];
        }

        if (!preg_match_all('/\{([^}]+)\}/', $routePattern, $paramMatches)) {
            return [];
        }

        $pathParams = [];
        foreach ($paramMatches[1] as $index => $paramName) {
            $pathParams[$paramName] = $index;
        }

        $regexPattern = preg_quote($routePattern, '#');
        $regexPattern = preg_replace('#\\\{([^}]+)\\\}#', '([^/]+)', $regexPattern);
        $regexPattern = '#^' . $regexPattern . '$#';

        if (!preg_match($regexPattern, $httpRequest->getPath(), $matches)) {
            return [];
        }

        $params = [];
        foreach ($pathParams as $paramName => $groupIndex) {
            $captureIndex = $groupIndex + 1;
            if (isset($matches[$captureIndex])) {
                $params[$paramName] = $matches[$captureIndex];
            }
        }

        return $params;
    }

    public static function getRawData(Request $httpRequest, object $dto): array
    {
        return array_merge(self::collectData($httpRequest), self::extractPathParams($dto, $httpRequest));
    }

    private static function collectData(Request $httpRequest): array
    {
        $data = [];

        if ($httpRequest->isJson() && $httpRequest->getContent()) {
            $jsonData = $httpRequest->getJsonBody();
            if ($jsonData !== null) {
                $data = array_merge($data, $jsonData);
            } else {
                $content = $httpRequest->getContent();
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data = array_merge($data, $decoded);
                }
            }
        } else {
            $data = array_merge($data, $httpRequest->post);
        }

        foreach ($httpRequest->query as $key => $value) {
            if (!array_key_exists($key, $data)) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    private static function castValue(mixed $value, ?\ReflectionType $type): mixed
    {
        if ($type === null) {
            return $value;
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $t) {
                if ($t->getName() !== 'null') {
                    return self::castToType($value, $t->getName());
                }
            }
            return $value;
        }

        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();
            if ($type->allowsNull() && ($value === null || $value === '')) {
                return null;
            }
            return self::castToType($value, $typeName);
        }

        return $value;
    }

    private static function castToType(mixed $value, string $type): mixed
    {
        if ($value === null || $value === '') {
            return match ($type) {
                'int', 'float' => 0,
                'bool' => false,
                'string' => '',
                'array' => [],
                default => null,
            };
        }

        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => self::castToBool($value),
            'string' => (string) $value,
            'array' => is_array($value) ? $value : [$value],
            default => $value,
        };
    }

    private static function castToBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            $lower = strtolower($value);
            return in_array($lower, ['1', 'true', 'yes', 'on'], true);
        }
        return (bool) $value;
    }
}
