<?php

declare(strict_types=1);

namespace Semitexa\Core\Container\BuildPhase;

use Semitexa\Core\Container\GraphBuilder;

/**
 * Builds resolver instances (generated registry resolvers with constructor injection).
 *
 * Preconditions: context->contractDetails, idToClass, instanceStore->readonly, prototypes, injections must be populated.
 * Postconditions: resolver instances added to instanceStore->readonly.
 */
final class ResolverBuildPhase implements BuildPhaseInterface
{
    public function __construct(
        private readonly GraphBuilder $graphBuilder,
    ) {}

    public function execute(BuildContext $context): void
    {
        $this->graphBuilder->buildResolvers(
            $context->contractDetails,
            $context->idToClass,
            $context->instanceStore->readonly,
            $context->instanceStore->prototypes,
            $context->injections,
        );
    }

    public function name(): string
    {
        return 'ResolverBuild';
    }
}
