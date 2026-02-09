# Events (single mechanism for sync and async work)

In Semitexa, **events** are the single mechanism for deferred or asynchronous work. Under the hood they are implemented as **queues**: you can run handlers synchronously (in-memory) or asynchronously (e.g. RabbitMQ). There is no separate “queue” vs “event” API — it’s one mechanism.

## Configuration

- **EVENTS_ASYNC** (`.env`): `0` (default) = in-memory (sync); `1` (or `true`/`yes`) = use RabbitMQ for async.
- **EVENTS_TRANSPORT**: Override transport: `in-memory` or `rabbitmq`.
- **EVENTS_QUEUE_DEFAULT**: Default queue name pattern when not set per handler.

When **EVENTS_ASYNC=1**, the app uses RabbitMQ. If you run with Docker (`docker compose up`), RabbitMQ is started automatically and the app connects to the `rabbitmq` service.

## Sync vs async per handler (event)

Every declared handler (e.g. `#[AsPayloadHandler(for: SomeRequest::class)]`) has an **execution** option:

- **Sync** (default): handler runs in the same process and blocks the HTTP response until it finishes.
- **Async**: handler is enqueued; the HTTP response is sent immediately and the handler runs later (via a worker or in the background).

Set it in the attribute:

```php
#[AsPayloadHandler(for: SomeRequest::class, execution: HandlerExecution::Async)]
```

So each “event” (handler) can be run synchronously or asynchronously; the framework does not force one mode globally.

## Running the worker (async events)

When using async, run a worker to process the queue:

```bash
bin/semitexa queue:work
```

Optional arguments: transport name and queue name (defaults come from `QueueConfig` / env).

## Summary

- **Events** = single mechanism; implemented as queues (sync in-memory or async RabbitMQ).
- **EVENTS_ASYNC=1** → use RabbitMQ; in Docker, RabbitMQ is started with the stack.
- **Per handler:** `execution: sync | async` in `AsPayloadHandler`.
