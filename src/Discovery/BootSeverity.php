<?php

declare(strict_types=1);

namespace Semitexa\Core\Discovery;

enum BootSeverity: string
{
    case Skip = 'skip';
    case InvalidUsage = 'invalid_usage';
}
