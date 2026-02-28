<?php

declare(strict_types=1);

namespace Semitexa\Core\Acl;

interface HasRolesInterface
{
    /** @return string[] */
    public function getRoleSlugs(): array;
}
