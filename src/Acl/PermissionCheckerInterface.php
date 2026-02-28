<?php

declare(strict_types=1);

namespace Semitexa\Core\Acl;

use Semitexa\Core\Auth\AuthenticatableInterface;

interface PermissionCheckerInterface
{
    public function can(
        AuthenticatableInterface $user,
        string $ability,
        ?object $subject = null,
    ): bool;

    public function cannot(
        AuthenticatableInterface $user,
        string $ability,
        ?object $subject = null,
    ): bool;
}
