<?php

declare(strict_types=1);

namespace Semitexa\Core\Discovery;

final readonly class BootWarning
{
    public function __construct(
        public BootSeverity $severity,
        public string $component,
        public string $message,
        public ?\Throwable $cause = null,
    ) {}
}
