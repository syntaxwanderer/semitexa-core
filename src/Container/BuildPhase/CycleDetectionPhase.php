<?php

declare(strict_types=1);

namespace Semitexa\Core\Container\BuildPhase;

use Semitexa\Core\Container\CycleDetector;

/**
 * Validates that the dependency graph has no circular dependencies.
 *
 * Preconditions: context->idToClass, executionScopedClasses, injections must be populated.
 * Postconditions: none (validation only — throws on cycle).
 */
final class CycleDetectionPhase implements BuildPhaseInterface
{
    public function __construct(
        private readonly CycleDetector $cycleDetector,
    ) {}

    public function execute(BuildContext $context): void
    {
        $resolveToClass = $context->resolveToClass(...);

        $this->cycleDetector->assertNoCycles(
            $context->idToClass,
            $context->executionScopedClasses,
            $context->injections,
            $resolveToClass,
        );
    }

    public function name(): string
    {
        return 'CycleDetection';
    }
}
