<?php

declare(strict_types=1);

namespace Semitexa\Core\Http;

use Semitexa\Core\Response;
use Swoole\Http\Response as SwooleResponse;

final class SwooleResponseEmitter implements ResponseEmitterInterface
{
    public function emit(Response $response, mixed $transport): void
    {
        if (!$transport instanceof SwooleResponse) {
            throw new \InvalidArgumentException(
                'SwooleResponseEmitter expects a Swoole\Http\Response, got ' . get_debug_type($transport)
            );
        }

        $transport->status($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $line) {
                    $transport->header($name, (string) $line);
                }
            } else {
                $transport->header($name, (string) $value);
            }
        }

        if (!$response->isAlreadySent()) {
            $transport->end($response->getContent());
        }
    }
}
