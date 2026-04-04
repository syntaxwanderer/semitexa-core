<?php

declare(strict_types=1);

namespace Semitexa\Core\Support;

/**
 * Per-coroutine key/value storage with CLI fallback.
 *
 * In Swoole: stores values in Coroutine::getContext() — auto-cleaned when the coroutine ends.
 * In CLI/tests: stores values in a process-local static array.
 *
 * Use for request-scoped cross-cutting data that multiple services need within one request
 * but that doesn't flow through DI (rendered slots, error stacks, etc.).
 */
final class CoroutineLocal
{
    /** @var array<string, mixed> */
    private static array $cliStore = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::inCoroutine()) {
            return \Swoole\Coroutine::getContext()[$key] ?? $default;
        }

        return self::$cliStore[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        if (self::inCoroutine()) {
            \Swoole\Coroutine::getContext()[$key] = $value;
            return;
        }

        self::$cliStore[$key] = $value;
    }

    public static function has(string $key): bool
    {
        if (self::inCoroutine()) {
            return isset(\Swoole\Coroutine::getContext()[$key]);
        }

        return isset(self::$cliStore[$key]);
    }

    public static function remove(string $key): void
    {
        if (self::inCoroutine()) {
            unset(\Swoole\Coroutine::getContext()[$key]);
            return;
        }

        unset(self::$cliStore[$key]);
    }

    /**
     * Reset CLI fallback store. For testing only.
     */
    public static function resetCliStore(): void
    {
        self::$cliStore = [];
    }

    private static function inCoroutine(): bool
    {
        return class_exists(\Swoole\Coroutine::class, false)
            && \Swoole\Coroutine::getCid() >= 0;
    }
}
