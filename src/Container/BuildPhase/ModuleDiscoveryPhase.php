<?php

declare(strict_types=1);

namespace Semitexa\Core\Container\BuildPhase;

use Semitexa\Core\ModuleRegistry;

/**
 * Discovers modules and builds the module hierarchy via topological sort.
 *
 * Preconditions: none (independent of ClassmapLoad).
 * Postconditions: context->moduleRegistry is set and initialized.
 */
final class ModuleDiscoveryPhase implements BuildPhaseInterface
{
    public function execute(BuildContext $context): void
    {
        $moduleRegistry = new ModuleRegistry();
        $moduleRegistry->initialize();
        $context->moduleRegistry = $moduleRegistry;
    }

    public function name(): string
    {
        return 'ModuleDiscovery';
    }
}
