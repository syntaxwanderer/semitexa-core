<?php

declare(strict_types=1);

namespace Semitexa\Core\Container\BuildPhase;

use Semitexa\Core\Discovery\ClassDiscovery;

/**
 * Loads the Composer classmap for attribute-based class discovery.
 *
 * Preconditions: none (first phase).
 * Postconditions: context->classDiscovery is set and initialized.
 */
final class ClassmapLoadPhase implements BuildPhaseInterface
{
    public function execute(BuildContext $context): void
    {
        $classDiscovery = new ClassDiscovery();
        $classDiscovery->initialize();
        $context->classDiscovery = $classDiscovery;
    }

    public function name(): string
    {
        return 'ClassmapLoad';
    }
}
