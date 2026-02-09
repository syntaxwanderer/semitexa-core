<?php

declare(strict_types=1);

namespace Semitexa\Core\Request\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Length
{
    public function __construct(
        public readonly ?int $min = null,
        public readonly ?int $max = null
    ) {
    }
}
