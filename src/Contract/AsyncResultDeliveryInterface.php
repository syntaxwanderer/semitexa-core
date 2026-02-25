<?php

declare(strict_types=1);

namespace Semitexa\Core\Contract;

/**
 * Delivers async handler result to the client (e.g. via SSE).
 * Implementations may buffer by session_id if the client is not connected yet.
 */
interface AsyncResultDeliveryInterface
{
    public function deliver(string $sessionId, object $responseDto, string $handlerClass = ''): void;
}
