<?php

declare(strict_types=1);

namespace Semitexa\Core\Http;

use Semitexa\Core\Response;

interface ResponseEmitterInterface
{
    /**
     * Emit a framework Response to the underlying transport.
     *
     * @param Response $response The framework response to emit
     * @param mixed $transport The transport-specific response object (e.g. Swoole\Http\Response)
     */
    public function emit(Response $response, mixed $transport): void;
}
