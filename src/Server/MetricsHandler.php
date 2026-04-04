<?php

declare(strict_types=1);

namespace Semitexa\Core\Server;

use Semitexa\Core\Http\HttpStatus;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server;

/**
 * Exposes Swoole server stats at /metrics for observability.
 *
 * Includes: active connections, worker memory, coroutine count, request totals.
 */
final class MetricsHandler
{
    private Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function handle(SwooleRequest $request, SwooleResponse $response): bool
    {
        /** @var array<string, mixed> $serverVars */
        $serverVars = $request->server ?? [];
        if (($serverVars['request_uri'] ?? '') !== '/metrics') {
            return false;
        }

        /** @var array<string, mixed> $stats */
        $stats = $this->server->stats();
        $stats['worker_memory_usage'] = memory_get_usage(true);
        $stats['worker_memory_peak'] = memory_get_peak_usage(true);

        if (class_exists(\Swoole\Coroutine::class, false)) {
            /** @var array<string, int> $coStats */
            $coStats = \Swoole\Coroutine::stats();
            $stats['coroutine_num'] = $coStats['coroutine_num'] ?? 0;
        }

        $response->status(HttpStatus::Ok->value);
        $response->header('Content-Type', 'application/json');
        $response->end(json_encode($stats, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        return true;
    }
}
