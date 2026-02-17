<?php

declare(strict_types=1);

namespace Semitexa\Core\Console\Command;

use Semitexa\Core\Util\ProjectRoot;
use Symfony\Component\Console\Command\Command;

/**
 * Base command with helper methods
 */
abstract class BaseCommand extends Command
{
    protected function getProjectRoot(): string
    {
        return ProjectRoot::get();
    }
}

