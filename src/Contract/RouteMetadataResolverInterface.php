<?php

declare(strict_types=1);

namespace Semitexa\Core\Contract;

use Semitexa\Core\Discovery\DiscoveredRoute;
use Semitexa\Core\Discovery\ResolvedRouteMetadata;

/**
 * Converts a DiscoveredRoute into a typed, immutable ResolvedRouteMetadata DTO.
 *
 * Core registered DefaultRouteMetadataResolver as the fallback.
 * Packages such as semitexa-api override this binding via
 * #[SatisfiesServiceContract(of: RouteMetadataResolverInterface::class)] to enrich
 * the metadata with external API markers, version info, and other extension data
 * — without touching discovery internals.
 */
interface RouteMetadataResolverInterface
{
    /**
     * @param DiscoveredRoute $route Discovered route from RouteRegistry
     */
    public function resolve(DiscoveredRoute $route): ResolvedRouteMetadata;
}
