<?php

declare(strict_types=1);

namespace Semitexa\Core\Container\BuildPhase;

/**
 * A single phase in the container build sequence.
 *
 * Phases execute sequentially during worker startup. Each phase reads from
 * and writes to a shared BuildContext. No DI is available — phases receive
 * explicit dependencies through their constructors.
 *
 * @internal Used only by ContainerBootstrapper.
 */
interface BuildPhaseInterface
{
    /**
     * Execute this build phase, reading/writing to the shared context.
     */
    public function execute(BuildContext $context): void;

    /**
     * Human-readable phase name for diagnostics.
     */
    public function name(): string;
}
