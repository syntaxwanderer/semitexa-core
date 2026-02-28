<?php

declare(strict_types=1);

namespace Semitexa\Core\Tenant;

use Semitexa\Core\Request;
use Semitexa\Core\Tenant\Layer\TenantLayerInterface;
use Semitexa\Core\Tenant\Layer\TenantLayerValueInterface;

/**
 * Contract for a layer-specific tenant resolution strategy.
 *
 * Each implementation resolves one tenant layer (Organization, Locale,
 * Environment, Theme, …) from the incoming request and returns the
 * corresponding value object, or null when the layer cannot be determined.
 *
 * Implementations live in semitexa-tenancy (or application code) and
 * depend only on semitexa-core contracts.
 */
interface TenantLayerStrategyInterface
{
    /**
     * The layer this strategy resolves.
     */
    public function layer(): TenantLayerInterface;

    /**
     * Attempt to resolve the layer value from the request.
     * Returns null when the layer is not present / cannot be determined.
     */
    public function resolve(Request $request): ?TenantLayerValueInterface;
}
