<?php

declare(strict_types=1);

namespace Semitexa\Core\Tests\Unit\PHPStan\Fixtures;

use Semitexa\Core\Attribute\AsService;
use Semitexa\Core\Attribute\InjectAsReadonly;

/**
 * Fixture: constructor usages that InjectionViaConstructorRule must NOT flag.
 *
 * This file is documentation-by-code. It pins down the intent of the One Way
 * DI rule: constructor-based injection is forbidden, constructors in general
 * are not.
 */

/**
 * ALLOWED — parameterless __construct on a container-managed class.
 *
 * The container instantiates via newInstanceWithoutConstructor(), so this
 * constructor is never called during DI. Declaring it is inert but permitted,
 * e.g. for documentation or for manual construction in tests.
 */
#[AsService]
final class ContainerManagedWithEmptyConstructor
{
    #[InjectAsReadonly]
    protected \Psr\Log\LoggerInterface $logger;

    public function __construct()
    {
    }
}

/**
 * ALLOWED — value object with a parameterised constructor.
 *
 * Not container-managed; the One Way rule does not apply. Constructors are
 * the natural place for invariant enforcement on self-contained objects.
 */
final readonly class Money
{
    public function __construct(
        public int $amountMinor,
        public string $currency,
    ) {
        if ($amountMinor < 0) {
            throw new \InvalidArgumentException('amount must be non-negative');
        }
        if (strlen($currency) !== 3) {
            throw new \InvalidArgumentException('currency must be ISO 4217');
        }
    }
}

/**
 * ALLOWED — DTO / payload-shaped class with a parameterised constructor.
 *
 * Not container-managed.
 */
final class UserRegisteredEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
        public readonly \DateTimeImmutable $registeredAt,
    ) {
    }
}

/**
 * ALLOWED — plain PHP class without framework attributes.
 *
 * Completely outside the rule's target set; constructor usage is
 * unrestricted.
 */
final class LocalHelper
{
    public function __construct(private readonly string $prefix)
    {
    }

    public function decorate(string $value): string
    {
        return $this->prefix . $value;
    }
}
