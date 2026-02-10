<?php

declare(strict_types=1);

namespace Semitexa\Core\Attributes;

use Attribute;
use Semitexa\Core\Queue\HandlerExecution;

/**
 * Marks a class as an HTTP payload (request) handler.
 * Match by (payload, resource): this handler runs for the given Payload and builds the given Resource.
 *
 * @see DocumentedAttributeInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
class AsPayloadHandler implements DocumentedAttributeInterface
{
    use DocumentedAttributeTrait;

    public readonly ?string $doc;

    public function __construct(
        public string $payload,
        public string $resource,
        public HandlerExecution|string|null $execution = null,
        public ?string $transport = null,
        public ?string $queue = null,
        public ?int $priority = null,
        ?string $doc = null,
    ) {
        $this->doc = $doc;
    }

    public function getDocPath(): string
    {
        return $this->doc ?? 'packages/semitexa/core/docs/attributes/AsPayloadHandler.md';
    }
}
