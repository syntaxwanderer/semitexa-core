<?php

declare(strict_types=1);

namespace Semitexa\Core\Discovery;

/**
 * Stores payload/resource part (trait) compositions discovered at boot time.
 * Provides part lookup by class for payload/resource hydration.
 *
 * Populated during AttributeDiscovery::initialize(), sealed after boot.
 * Readonly for the worker's lifetime — safe to share across coroutines.
 */
final class PayloadPartRegistry
{
    /** @var array<string, list<string>> baseClass => [traitFQN, ...] */
    private array $payloadParts = [];

    /** @var array<string, list<string>> baseClass => [traitFQN, ...] */
    private array $resourceParts = [];

    /** @var array<string, string> className => attribute base class */
    private array $payloadBaseMap = [];

    /** @var array<string, string> className => attribute base class */
    private array $resourceBaseMap = [];

    /**
     * Register a payload part (trait) for a base class.
     */
    public function registerPayloadPart(string $baseClass, string $traitClass): void
    {
        $this->payloadParts[$baseClass][] = $traitClass;
    }

    /**
     * Register a resource part (trait) for a base class.
     */
    public function registerResourcePart(string $baseClass, string $traitClass): void
    {
        $this->resourceParts[$baseClass][] = $traitClass;
    }

    /**
     * Register a payload class's attribute base chain mapping.
     */
    public function registerPayloadBase(string $className, string $baseClass): void
    {
        $this->payloadBaseMap[$className] = $baseClass;
    }

    /**
     * Register a resource class's attribute base chain mapping.
     */
    public function registerResourceBase(string $className, string $baseClass): void
    {
        $this->resourceBaseMap[$className] = $baseClass;
    }

    /**
     * Get trait list for a payload class.
     * Matches via attribute base chain and PHP inheritance.
     *
     * @return list<string>
     */
    public function getPayloadPartsForClass(string $requestClass): array
    {
        $chain = self::buildBaseChain($requestClass, $this->payloadBaseMap);
        $traits = [];
        foreach ($this->payloadParts as $base => $traitList) {
            if (in_array($base, $chain, true) || is_subclass_of($requestClass, $base)) {
                array_push($traits, ...$traitList);
            }
        }
        return $traits;
    }

    /**
     * Get trait list for a resource class.
     * Matches via attribute base chain and PHP inheritance.
     *
     * @return list<string>
     */
    public function getResourcePartsForClass(string $responseClass): array
    {
        $chain = self::buildBaseChain($responseClass, $this->resourceBaseMap);
        $traits = [];
        foreach ($this->resourceParts as $base => $traitList) {
            if (in_array($base, $chain, true) || is_subclass_of($responseClass, $base)) {
                array_push($traits, ...$traitList);
            }
        }
        return $traits;
    }

    /**
     * Walk the attribute base chain for a class.
     *
     * @param array<string, string> $baseMap
     * @return list<string>
     */
    private static function buildBaseChain(string $className, array $baseMap): array
    {
        $chain = [];
        $current = $className;
        while ($current !== null) {
            $chain[] = $current;
            $current = $baseMap[$current] ?? null;
        }
        return $chain;
    }
}
