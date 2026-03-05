<?php

declare(strict_types=1);

namespace Semitexa\Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class RequiresPermission
{
    public function __construct(public string $permission) {}
}
