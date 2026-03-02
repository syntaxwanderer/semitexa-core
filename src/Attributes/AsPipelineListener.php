<?php

declare(strict_types=1);

namespace Semitexa\Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AsPipelineListener
{
    public function __construct(
        public string $phase,
        public int $priority = 0,
    ) {
    }
}
