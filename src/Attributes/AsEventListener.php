<?php

declare(strict_types=1);

namespace Semitexa\Core\Attributes;

use Attribute;
use Semitexa\Core\Event\EventExecution;

/**
 * Marks a class as an event listener for the given event type.
 * Place in Application/Handler/Event/. Implement handle(EventType $event): void.
 * Execution is taken from the event class #[AsEvent(execution: ...)]; optional override here.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class AsEventListener
{
    public function __construct(
        public string $event,
        public EventExecution|string|null $execution = null,
        public ?string $transport = null,
        public ?string $queue = null,
        public ?int $priority = null,
    ) {
    }
}
