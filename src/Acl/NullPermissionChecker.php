<?php

declare(strict_types=1);

namespace Semitexa\Core\Acl;

use Semitexa\Core\Auth\AuthenticatableInterface;

final class NullPermissionChecker implements PermissionCheckerInterface
{
    public function can(AuthenticatableInterface $user, string $ability, ?object $subject = null): bool
    {
        return false;
    }

    public function cannot(AuthenticatableInterface $user, string $ability, ?object $subject = null): bool
    {
        return true;
    }
}
