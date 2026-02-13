<?php

declare(strict_types=1);

namespace Semitexa\Core\Log;

/**
 * Log levels (lower value = more verbose). Used to compare minimum level.
 */
final class LogLevel
{
    public const DEBUG = 0;
    public const INFO = 1;
    public const NOTICE = 2;
    public const WARNING = 3;
    public const ERROR = 4;
    public const CRITICAL = 5;

    private const NAME_TO_VALUE = [
        'debug' => self::DEBUG,
        'info' => self::INFO,
        'notice' => self::NOTICE,
        'warning' => self::WARNING,
        'error' => self::ERROR,
        'critical' => self::CRITICAL,
    ];

    public static function toValue(string $name): int
    {
        $key = strtolower(trim($name));
        return self::NAME_TO_VALUE[$key] ?? self::DEBUG;
    }

    public static function isValid(string $name): bool
    {
        return isset(self::NAME_TO_VALUE[strtolower(trim($name))]);
    }
}
