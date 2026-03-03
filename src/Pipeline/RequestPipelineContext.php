<?php

declare(strict_types=1);

namespace Semitexa\Core\Pipeline;

use Semitexa\Core\Auth\AuthResult;
use Semitexa\Core\Request;

/**
 * Single mutable context object shared across all pipeline phases.
 *
 * Phases execute sequentially in one request scope: Access needs auth result,
 * Handle needs access result. A single context that accumulates state is the natural model.
 */
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
        /** Set by Application when auth package is present so AuthCheckListener can delegate to AuthBootstrapper. */
        public readonly ?object $authBootstrapper = null,
    ) {
        $this->resourceDto = $resourceDto;
    }
}
