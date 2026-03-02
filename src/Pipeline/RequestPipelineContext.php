<?php

declare(strict_types=1);

namespace Semitexa\Core\Pipeline;

use Psr\Container\ContainerInterface;
use Semitexa\Core\Auth\AuthResult;
use Semitexa\Core\Request;

class RequestPipelineContext
{
    public ?object $resourceDto;
    public ?AuthResult $authResult = null;
    public ?string $lastHandlerClass = null;

    public function __construct(
        public readonly object $requestDto,
        public readonly array $route,
        public readonly Request $request,
        ?object $resourceDto = null,
        /** Optional: set by Application so pipeline listeners (e.g. AuthCheckListener) can resolve request-scoped services. */
        public readonly ?ContainerInterface $requestScopedContainer = null,
        /** Optional: set by Application when auth package is present so AuthCheckListener can run auth. */
        public readonly ?object $authBootstrapper = null,
    ) {
        $this->resourceDto = $resourceDto;
    }
}
