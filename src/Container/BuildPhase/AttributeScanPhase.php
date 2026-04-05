<?php

declare(strict_types=1);

namespace Semitexa\Core\Container\BuildPhase;

use Semitexa\Core\Discovery\AttributeDiscovery;
use Semitexa\Core\Discovery\HandlerRegistry;
use Semitexa\Core\Discovery\PayloadPartRegistry;
use Semitexa\Core\Discovery\RouteRegistry;

/**
 * Scans classmap for routing/handler/resource attributes and builds the route,
 * handler, and payload part registries.
 *
 * Preconditions: context->classDiscovery, context->moduleRegistry must be set.
 * Postconditions: context->routeRegistry, handlerRegistry, payloadPartRegistry,
 *                 context->attributeDiscovery are set and initialized.
 */
final class AttributeScanPhase implements BuildPhaseInterface
{
    public function execute(BuildContext $context): void
    {
        assert($context->classDiscovery !== null, 'ClassmapLoadPhase must run before AttributeScanPhase');
        assert($context->moduleRegistry !== null, 'ModuleDiscoveryPhase must run before AttributeScanPhase');

        $routeRegistry = new RouteRegistry();
        $handlerRegistry = new HandlerRegistry();
        $payloadPartRegistry = new PayloadPartRegistry();

        $attributeDiscovery = new AttributeDiscovery(
            $context->classDiscovery,
            $context->moduleRegistry,
            $routeRegistry,
            $handlerRegistry,
            $payloadPartRegistry,
        );
        $attributeDiscovery->initialize();

        $context->routeRegistry = $routeRegistry;
        $context->handlerRegistry = $handlerRegistry;
        $context->payloadPartRegistry = $payloadPartRegistry;
        $context->attributeDiscovery = $attributeDiscovery;
    }

    public function name(): string
    {
        return 'AttributeScan';
    }
}
