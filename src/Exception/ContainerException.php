<?php

declare(strict_types=1);

namespace Semitexa\Core\Exception;

use Psr\Container\ContainerExceptionInterface;

/**
 * Service resolution failures in the DI container.
 */
class ContainerException extends SemitexaException implements ContainerExceptionInterface
{
}
