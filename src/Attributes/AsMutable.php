<?php

declare(strict_types=1);

namespace Semitexa\Core\Attributes;

use Attribute;

/**
 * @deprecated Use #[ExecutionScoped] instead. This attribute is scheduled for removal.
 *
 * Previously marked a class as request-scoped (mutable).
 * The container will clone a prototype of this class per request
 * and inject execution context values.
 *
 * Migration: Replace #[AsMutable] with #[ExecutionScoped] on the class.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AsMutable
{
}
