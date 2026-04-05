<?php

declare(strict_types=1);

namespace Semitexa\Core\Container\BuildPhase;

use Semitexa\Core\Container\InjectionAnalyzer;

/**
 * Collects injection metadata (#[InjectAsReadonly], #[InjectAsMutable], etc.)
 * for all registered classes.
 *
 * Preconditions: context->idToClass, context->executionScopedClasses must be populated.
 * Postconditions: context->injections populated.
 */
final class InjectionAnalysisPhase implements BuildPhaseInterface
{
    public function __construct(
        private readonly InjectionAnalyzer $injectionAnalyzer,
    ) {}

    public function execute(BuildContext $context): void
    {
        $context->injections = $this->injectionAnalyzer->collectInjections(
            $context->idToClass,
            $context->executionScopedClasses,
        );
    }

    public function name(): string
    {
        return 'InjectionAnalysis';
    }
}
