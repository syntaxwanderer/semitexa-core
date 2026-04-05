<?php

declare(strict_types=1);

namespace Semitexa\Core\Container\BuildPhase;

use Semitexa\Core\Container\GraphBuilder;

/**
 * Builds readonly (worker-scoped) service instances in topological dependency order.
 *
 * Preconditions: context->idToClass, executionScopedClasses, injections, instanceStore must be populated.
 * Postconditions: instanceStore->readonly populated with all readonly service instances.
 */
final class ReadonlyBuildPhase implements BuildPhaseInterface
{
    public function __construct(
        private readonly GraphBuilder $graphBuilder,
    ) {}

    public function execute(BuildContext $context): void
    {
        $resolveToClass = $context->resolveToClass(...);

        $this->graphBuilder->buildReadonlyGraph(
            $context->idToClass,
            $context->executionScopedClasses,
            $context->injections,
            $context->instanceStore->readonly,
            $resolveToClass,
        );
    }

    public function name(): string
    {
        return 'ReadonlyBuild';
    }
}
