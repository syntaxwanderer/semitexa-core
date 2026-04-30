<?php

declare(strict_types=1);

namespace Semitexa\Core\Resource;

use Semitexa\Core\Attribute\AsService;
use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Core\Resource\Exception\NonExpandableIncludeException;
use Semitexa\Core\Resource\Exception\UnknownIncludeException;
use Semitexa\Core\Resource\Exception\UnsatisfiedResourceIncludeException;
use Semitexa\Core\Resource\Metadata\ResourceFieldMetadata;
use Semitexa\Core\Resource\Metadata\ResourceMetadataRegistry;
use Semitexa\Core\Resource\Metadata\ResourceObjectMetadata;

/**
 * Validates an `IncludeSet` against the metadata graph rooted at a given
 * Resource DTO.
 *
 * Phase 2 supports dot-notation tokens (e.g. `addresses.country`).
 *
 * Phase 6c additionally requires that every requested expandable
 * relation be **satisfiable**, i.e. have either:
 *
 *   1. a `#[ResolveWith]` resolver in metadata
 *      (`ResourceFieldMetadata::$resolverClass !== null`) — the future
 *      Phase 6d expansion pipeline will load it; OR
 *   2. a route-level `#[HandlerProvidesResourceIncludes]` declaration
 *      that lists the requested token — the handler eagerly embeds it
 *      itself.
 *
 * Phase 6c does **not** instantiate any resolver; it only consults
 * metadata + the `HandlerProvidedIncludeRegistry`. There is no DB,
 * ORM, Request, renderer, or `IriBuilder` access.
 */
#[AsService]
final class IncludeValidator
{
    #[InjectAsReadonly]
    protected ResourceMetadataRegistry $registry;

    #[InjectAsReadonly]
    protected HandlerProvidedIncludeRegistry $handlerProvidedIncludes;

    /** Bypass property injection for unit tests. */
    public static function forTesting(
        ResourceMetadataRegistry $registry,
        ?HandlerProvidedIncludeRegistry $handlerProvidedIncludes = null,
    ): self {
        $v = new self();
        $v->registry = $registry;
        $v->handlerProvidedIncludes = $handlerProvidedIncludes
            ?? self::emptyHandlerProvidedRegistry();
        return $v;
    }

    public function validate(
        IncludeSet $includes,
        ResourceObjectMetadata $rootMetadata,
        ?string $payloadClass = null,
    ): void {
        if ($includes->isEmpty()) {
            return;
        }

        $handlerProvidedTokens = $payloadClass !== null
            ? $this->handlerProvidedIncludes->tokensFor($payloadClass)
            : [];

        foreach ($includes->tokens as $token) {
            $this->validateToken($token, $rootMetadata, $handlerProvidedTokens);
        }
    }

    /**
     * @param list<string> $handlerProvidedTokens normalized; lookup is
     *                                              case-insensitive
     *                                              because tokens are
     *                                              normalized identically
     *                                              upstream
     */
    private function validateToken(
        string $token,
        ResourceObjectMetadata $metadata,
        array $handlerProvidedTokens,
    ): void {
        $segments = explode('.', $token);
        $current  = $metadata;
        $rootType = $metadata->type;
        /** @var ResourceFieldMetadata|null $leafField */
        $leafField = null;
        $leafResource = $rootType;

        foreach ($segments as $i => $segment) {
            $field = $current->getField($segment);
            if ($field === null) {
                throw new UnknownIncludeException($token, $current->type);
            }
            if (!$field->isRelation()) {
                throw new UnknownIncludeException($token, $current->type);
            }
            if (!$field->expandable) {
                throw new NonExpandableIncludeException($token, $current->type);
            }

            // Last segment: capture leaf for the satisfiability check.
            if ($i === count($segments) - 1) {
                $leafField    = $field;
                $leafResource = $current->type;
                break;
            }

            $next = $this->resolveTargetMetadata($field);
            if ($next === null) {
                // No further metadata to walk into — token claims nesting that doesn't exist.
                throw new UnknownIncludeException($token, $current->type);
            }
            $current = $next;
        }

        \assert($leafField !== null);

        // Phase 6c satisfiability: a valid expandable relation must have
        // either a resolver or be declared handler-provided. The same
        // satisfiability rule applies to every render profile because
        // the rule lives here, in the single chokepoint.
        $hasResolver        = $leafField->resolverClass !== null;
        $isHandlerProvided  = in_array($token, $handlerProvidedTokens, true);

        if (!$hasResolver && !$isHandlerProvided) {
            throw new UnsatisfiedResourceIncludeException(
                resourceType: $leafResource,
                token: $token,
                relationName: $leafField->name,
                resolverMissing: true,
                handlerContractMissing: true,
            );
        }
    }

    private function resolveTargetMetadata(ResourceFieldMetadata $field): ?ResourceObjectMetadata
    {
        if ($field->target !== null) {
            return $this->registry->get($field->target);
        }

        // Polymorphic union — for include validation we accept the token as long as ALL
        // declared targets agree on the next segment. Phase 2 keeps this conservative:
        // a nested include only validates against the first registered union target.
        if ($field->unionTargets !== null && $field->unionTargets !== []) {
            return $this->registry->get($field->unionTargets[0]);
        }

        return null;
    }

    private static function emptyHandlerProvidedRegistry(): HandlerProvidedIncludeRegistry
    {
        // Tests that don't exercise the handler-provided contract get a
        // pre-built empty registry — no ClassDiscovery touch.
        return HandlerProvidedIncludeRegistry::withDeclarations([]);
    }
}
