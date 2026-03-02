<?php

declare(strict_types=1);

namespace Semitexa\Core\Pipeline;

use Psr\Container\ContainerInterface;
use Semitexa\Core\Event\EventDispatcherInterface;
use Semitexa\Core\Events\HandlerCompleted;
use Semitexa\Core\Queue\HandlerExecution;
use Semitexa\Core\Queue\QueueDispatcher;

final class PipelineExecutor
{
    public function __construct(
        private readonly ContainerInterface $requestScopedContainer,
        private readonly ContainerInterface $container,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getPhases(): array
    {
        return [AuthCheck::class, AccessCheck::class, HandleRequest::class];
    }

    public function execute(RequestPipelineContext $context): void
    {
        foreach ($this->getPhases() as $phaseClass) {
            $this->dispatchPhase($phaseClass, $context);
        }

        $this->dispatchHandlerCompleted($context);
    }

    private function dispatchPhase(string $phaseClass, RequestPipelineContext $context): void
    {
        $listeners = PipelineListenerRegistry::getListeners($phaseClass);
        foreach ($listeners as $meta) {
            $instance = $this->requestScopedContainer->get($meta['class']);
            $instance->handle($context);
        }

        if ($phaseClass === HandleRequest::class) {
            $this->executeRouteHandlers($context);
        }
    }

    private function executeRouteHandlers(RequestPipelineContext $context): void
    {
        $handlers = $context->route['handlers'] ?? [];
        $sessionId = $this->getSessionIdForAsyncDelivery($context->request);

        foreach ($handlers as $handlerMeta) {
            $handlerClass = is_array($handlerMeta) ? ($handlerMeta['class'] ?? null) : $handlerMeta;
            if (!$handlerClass) {
                continue;
            }

            $execution = $handlerMeta['execution'] ?? HandlerExecution::Sync->value;
            if ($execution === HandlerExecution::Async->value) {
                QueueDispatcher::enqueue(
                    is_array($handlerMeta) ? $handlerMeta : ['class' => $handlerClass, 'payload' => $context->route['class'] ?? ''],
                    $context->requestDto,
                    $context->resourceDto,
                    $sessionId
                );
                continue;
            }

            if (!class_exists($handlerClass)) {
                continue;
            }

            try {
                $handler = $this->requestScopedContainer->get($handlerClass);
            } catch (\Throwable $e) {
                throw new \RuntimeException("Failed to resolve handler {$handlerClass}: " . $e->getMessage(), 0, $e);
            }

            if (method_exists($handler, 'handle') && is_object($handler)) {
                $context->resourceDto = $handler->handle($context->requestDto, $context->resourceDto);
                $context->lastHandlerClass = $handlerClass;
            }
        }
    }

    private function dispatchHandlerCompleted(RequestPipelineContext $context): void
    {
        if (!$context->lastHandlerClass) {
            return;
        }

        $handle = method_exists($context->resourceDto, 'getRenderHandle')
            ? $context->resourceDto->getRenderHandle()
            : null;

        if (!$handle) {
            return;
        }

        try {
            $events = $this->container->get(EventDispatcherInterface::class);
        } catch (\Throwable) {
            return;
        }

        if (!$events instanceof EventDispatcherInterface) {
            return;
        }

        $events->dispatch(new HandlerCompleted(
            $context->lastHandlerClass,
            $context->resourceDto,
            $handle,
        ));
    }

    private function getSessionIdForAsyncDelivery(\Semitexa\Core\Request $request): string
    {
        $id = $request->getCookie('semitexa_sse_session', '');
        if ($id !== '') {
            return $id;
        }
        $id = $request->getCookie('PHPSESSID', '');
        if ($id !== '') {
            return $id;
        }
        return $request->getQuery('session_id', '');
    }
}
