<?php

declare(strict_types=1);

namespace Semitexa\Core\Session;

/**
 * Backend for session storage. Implementations: Swoole Table (in-memory, worker-shared)
 * or Redis (shared across all workers, persistent).
 */
interface SessionHandlerInterface
{
    /** @return array<string, mixed> Session data or empty array if not found/expired. */
    public function read(string $sessionId): array;

    /** @param array<string, mixed> $data */
    public function write(string $sessionId, array $data, int $lifetimeSeconds = 3600): void;

    public function destroy(string $sessionId): void;
}
