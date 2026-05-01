<?php

declare(strict_types=1);

namespace Semitexa\Core\Resource;

use Semitexa\Core\Attribute\AsService;

/**
 * Phase 3e: pure Accept-header → RenderProfile resolver.
 *
 * Implements RFC 7231 §5.3.2 quality value parsing with the following
 * Resource-DTO contract additions:
 *
 *   - Each `RenderProfile` carries a fixed media-type binding (see
 *     `MEDIA_TYPE_TO_PROFILE`). `application/json` → Json,
 *     `application/ld+json` → JsonLd, `application/graphql-response+json`
 *     → GraphQL, `text/html` → Html, `application/openapi+json` → OpenApi.
 *   - `application/*` matches the first declared profile whose media type
 *     starts with `application/`.
 *   - `*\/*` (and missing / empty Accept) selects the first declared
 *     profile.
 *   - `q=0` excludes a media type even if it would otherwise match.
 *   - When multiple supported types share the highest q, the resolver
 *     picks the one declared first by the route — preserving
 *     deterministic, route-controlled defaults.
 *
 * No IO, no Request access, no per-request state.
 */
#[AsService]
final class AcceptHeaderResolver
{
    /** @var array<string, RenderProfile> media-type → profile */
    public const MEDIA_TYPE_TO_PROFILE = [
        'application/json'                   => RenderProfile::Json,
        'application/ld+json'                => RenderProfile::JsonLd,
        'application/graphql-response+json'  => RenderProfile::GraphQL,
        'text/html'                          => RenderProfile::Html,
        'application/openapi+json'           => RenderProfile::OpenApi,
    ];

    /**
     * Resolve the best matching profile for the request, given the route's
     * declared profile list. Returns null when nothing in the Accept header
     * is acceptable — caller is expected to throw 406.
     *
     * @param list<RenderProfile> $declaredProfiles ordered as the route declares
     */
    public function resolve(?string $acceptHeader, array $declaredProfiles): ?RenderProfile
    {
        if ($declaredProfiles === []) {
            return null;
        }

        $accept = trim((string) $acceptHeader);
        if ($accept === '') {
            // Missing / empty Accept → first declared profile.
            return $declaredProfiles[0];
        }

        $entries = $this->parseAcceptHeader($accept);
        if ($entries === []) {
            return $declaredProfiles[0];
        }

        // Build candidates: for each declared profile, the highest q the
        // Accept header offers. q=0 excludes; absent → no candidate.
        $candidates = [];
        foreach ($declaredProfiles as $declarationIndex => $profile) {
            $q = $this->highestQForProfile($profile, $entries);
            if ($q === null || $q <= 0.0) {
                continue;
            }
            $candidates[] = [
                'profile'           => $profile,
                'q'                 => $q,
                'declarationIndex'  => $declarationIndex,
            ];
        }

        if ($candidates === []) {
            return null;
        }

        // Highest q wins; ties broken by declaration order (lower index wins).
        usort($candidates, static function (array $a, array $b): int {
            $cmp = $b['q'] <=> $a['q'];
            return $cmp !== 0 ? $cmp : $a['declarationIndex'] <=> $b['declarationIndex'];
        });

        return $candidates[0]['profile'];
    }

    /**
     * Parse an Accept header into an ordered list of `[type, subtype, q, originalIndex]`
     * entries. Media-type parameters other than `q` are ignored.
     *
     * @return list<array{type: string, subtype: string, q: float, originalIndex: int}>
     */
    private function parseAcceptHeader(string $accept): array
    {
        $entries = [];
        $parts   = preg_split('/\s*,\s*/', $accept) ?: [];
        $i = 0;
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $tokens = preg_split('/\s*;\s*/', $part) ?: [];
            $mediaType = strtolower((string) array_shift($tokens));
            if ($mediaType === '') {
                continue;
            }

            $q = 1.0;
            foreach ($tokens as $param) {
                if (preg_match('/^q\s*=\s*([0-9](?:\.[0-9]{1,3})?)\s*$/i', $param, $m)) {
                    $q = (float) $m[1];
                }
                // other parameters (charset, version, …) are ignored for matching
            }

            $slash = strpos($mediaType, '/');
            if ($slash === false) {
                continue;
            }

            $entries[] = [
                'type'          => substr($mediaType, 0, $slash),
                'subtype'       => substr($mediaType, $slash + 1),
                'q'             => $q,
                'originalIndex' => $i++,
            ];
        }

        return $entries;
    }

    /**
     * @param list<array{type: string, subtype: string, q: float, originalIndex: int}> $entries
     */
    private function highestQForProfile(RenderProfile $profile, array $entries): ?float
    {
        $best = null;

        foreach ($entries as $entry) {
            $matches = $this->entryMatchesProfile($profile, $entry);
            if (!$matches) {
                continue;
            }
            if ($best === null || $entry['q'] > $best) {
                $best = $entry['q'];
            }
        }

        return $best;
    }

    /**
     * @param array{type: string, subtype: string, q: float, originalIndex: int} $entry
     */
    private function entryMatchesProfile(RenderProfile $profile, array $entry): bool
    {
        $entryType    = $entry['type'];
        $entrySubtype = $entry['subtype'];

        // */*  → matches anything.
        if ($entryType === '*' && $entrySubtype === '*') {
            return true;
        }

        $profileMediaType = self::profileMediaType($profile);
        if ($profileMediaType === null) {
            return false;
        }
        [$pType, $pSubtype] = explode('/', $profileMediaType, 2);

        // application/*  → matches the same top-level type, any subtype.
        if ($entrySubtype === '*') {
            return $entryType === $pType;
        }

        // exact match (case-insensitive on type/subtype).
        return $entryType === $pType && $entrySubtype === $pSubtype;
    }

    public static function profileMediaType(RenderProfile $profile): ?string
    {
        foreach (self::MEDIA_TYPE_TO_PROFILE as $mediaType => $candidate) {
            if ($candidate === $profile) {
                return $mediaType;
            }
        }
        return null;
    }

    /**
     * @param list<RenderProfile> $profiles
     * @return list<string>
     */
    public static function mediaTypesForProfiles(array $profiles): array
    {
        $types = [];
        foreach ($profiles as $profile) {
            $type = self::profileMediaType($profile);
            if ($type !== null) {
                $types[] = $type;
            }
        }
        return $types;
    }
}
