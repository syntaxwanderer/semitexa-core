<?php

declare(strict_types=1);

namespace Semitexa\Core\Container\Exception;

use Semitexa\Core\Exception\ContainerException;

/**
 * Thrown when an execution-scoped service is accessed before SessionPhase completes.
 */
final class ExecutionContextNotReadyException extends ContainerException
{
    public function __construct(string $serviceId)
    {
        parent::__construct(
            "Execution-scoped service '{$serviceId}' accessed before execution context was set. "
            . 'Ensure SessionPhase completes before resolving execution-scoped services.',
        );
    }
}
