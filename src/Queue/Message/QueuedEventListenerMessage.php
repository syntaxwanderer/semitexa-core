<?php

declare(strict_types=1);

namespace Semitexa\Core\Queue\Message;

use JsonException;

class QueuedEventListenerMessage implements \JsonSerializable
{
    public const TYPE = 'event';

    public function __construct(
        public string $listenerClass,
        public string $eventClass,
        public array $eventPayload,
        public string $queuedAt = '',
    ) {
        $this->queuedAt = $queuedAt ?: date(DATE_ATOM);
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => self::TYPE,
            'listenerClass' => $this->listenerClass,
            'eventClass' => $this->eventClass,
            'eventPayload' => $this->eventPayload,
            'queuedAt' => $this->queuedAt,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws JsonException
     */
    public static function fromJson(string $payload): self
    {
        $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        return new self(
            listenerClass: $data['listenerClass'],
            eventClass: $data['eventClass'],
            eventPayload: $data['eventPayload'] ?? [],
            queuedAt: $data['queuedAt'] ?? date(DATE_ATOM),
        );
    }
}
