<?php

declare(strict_types=1);

namespace Semitexa\Core\Log;

/**
 * Logger interface for Semitexa.
 * All implementations should support JSON output and async writing when running under Swoole.
 */
interface LoggerInterface
{
    public function error(string $message, array $context = []): void;

    public function critical(string $message, array $context = []): void;

    public function warning(string $message, array $context = []): void;

    public function info(string $message, array $context = []): void;

    public function notice(string $message, array $context = []): void;

    public function debug(string $message, array $context = []): void;
}
