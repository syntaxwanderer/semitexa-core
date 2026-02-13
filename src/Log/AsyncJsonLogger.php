<?php

declare(strict_types=1);

namespace Semitexa\Core\Log;

use Semitexa\Core\Environment;

/**
 * Logger that writes JSON lines to a file. Under Swoole, writes are deferred so the request is not blocked.
 */
final class AsyncJsonLogger implements LoggerInterface
{
    private const DEFAULT_LOG_FILE = 'var/log/app.log';

    private int $minLevel;
    private string $logFile;
    /** @var list<array{level: string, message: string, context: array, timestamp: string}> */
    private array $buffer = [];
    private bool $deferScheduled = false;

    public function __construct(?Environment $environment = null)
    {
        $env = $environment ?? Environment::create();
        $levelName = Environment::getEnvValue('LOG_LEVEL');
        if ($levelName === null || $levelName === '' || !LogLevel::isValid($levelName)) {
            $levelName = $env->isDev() ? 'info' : 'warning';
        }
        $this->minLevel = LogLevel::toValue($levelName);
        $logFile = Environment::getEnvValue('LOG_FILE');
        $this->logFile = $logFile !== null && $logFile !== '' ? $logFile : self::DEFAULT_LOG_FILE;
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    private function log(string $level, string $message, array $context): void
    {
        if (LogLevel::toValue($level) < $this->minLevel) {
            return;
        }
        $entry = [
            'timestamp' => date('c'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
        $this->buffer[] = $entry;

        // In CLI (e.g. queue worker) there is no Swoole event loop, so defer would never run — flush immediately.
        $useDefer = php_sapi_name() !== 'cli'
            && extension_loaded('swoole')
            && class_exists(\Swoole\Event::class)
            && method_exists(\Swoole\Event::class, 'defer');

        if ($useDefer) {
            if (!$this->deferScheduled) {
                $this->deferScheduled = true;
                \Swoole\Event::defer(function (): void {
                    $this->flush();
                    $this->deferScheduled = false;
                });
            }
        } else {
            $this->flush();
        }
    }

    /**
     * Write buffered entries to the log file (JSON lines). Call explicitly when not using defer.
     */
    public function flush(): void
    {
        if ($this->buffer === []) {
            return;
        }
        $entries = $this->buffer;
        $this->buffer = [];

        $root = $this->resolveProjectRoot();
        $path = $root . '/' . ltrim($this->logFile, '/');
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $line = '';
        foreach ($entries as $entry) {
            $line .= json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
        }
        @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }

    private function resolveProjectRoot(): string
    {
        $dir = __DIR__;
        while ($dir !== '' && $dir !== '/') {
            if (file_exists($dir . '/composer.json') && is_dir($dir . '/src/modules')) {
                return $dir;
            }
            $dir = dirname($dir);
        }
        return getcwd() ?: __DIR__;
    }
}
